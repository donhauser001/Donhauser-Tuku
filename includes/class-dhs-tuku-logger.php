<?php
/**
 * DHS图库日志记录类
 * 统一管理错误和操作日志
 */

class DHS_Tuku_Logger
{
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_DEBUG = 'debug';

    private static $log_levels = [
        self::LOG_LEVEL_ERROR => 1,
        self::LOG_LEVEL_WARNING => 2,
        self::LOG_LEVEL_INFO => 3,
        self::LOG_LEVEL_DEBUG => 4
    ];

    /**
     * 记录错误日志
     */
    public static function error($message, $context = [])
    {
        self::log(self::LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * 记录警告日志
     */
    public static function warning($message, $context = [])
    {
        self::log(self::LOG_LEVEL_WARNING, $message, $context);
    }

    /**
     * 记录信息日志
     */
    public static function info($message, $context = [])
    {
        self::log(self::LOG_LEVEL_INFO, $message, $context);
    }

    /**
     * 记录调试日志
     */
    public static function debug($message, $context = [])
    {
        self::log(self::LOG_LEVEL_DEBUG, $message, $context);
    }

    /**
     * 统一日志记录方法
     */
    private static function log($level, $message, $context = [])
    {
        // 检查日志级别
        $current_level = get_option('dhs_tuku_log_level', self::LOG_LEVEL_ERROR);
        if (self::$log_levels[$level] > self::$log_levels[$current_level]) {
            return;
        }

        // 格式化日志消息
        $formatted_message = self::format_message($level, $message, $context);

        // 记录到WordPress错误日志
        if (WP_DEBUG_LOG) {
            error_log('[DHS-TUKU] ' . $formatted_message);
        }

        // 记录到数据库（仅错误和警告）
        if (in_array($level, [self::LOG_LEVEL_ERROR, self::LOG_LEVEL_WARNING])) {
            self::log_to_database($level, $message, $context);
        }

        // 记录到文件（如果启用）
        if (get_option('dhs_tuku_file_logging', false)) {
            self::log_to_file($formatted_message);
        }
    }

    /**
     * 格式化日志消息
     */
    private static function format_message($level, $message, $context = [])
    {
        $timestamp = current_time('mysql');
        $user_id = get_current_user_id();
        $ip = self::get_client_ip();

        $formatted = sprintf(
            '[%s] [%s] [User:%d] [IP:%s] %s',
            $timestamp,
            strtoupper($level),
            $user_id,
            $ip,
            $message
        );

        if (!empty($context)) {
            $formatted .= ' | Context: ' . wp_json_encode($context);
        }

        return $formatted;
    }

    /**
     * 记录到数据库
     */
    private static function log_to_database($level, $message, $context = [])
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dhs_gallery_logs';
        
        // 确定资源类型和ID
        $resource_type = $context['resource_type'] ?? 'system';
        $resource_id = $context['resource_id'] ?? 0;
        $action = $context['action'] ?? 'log';

        try {
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => get_current_user_id(),
                    'resource_type' => $resource_type,
                    'resource_id' => $resource_id,
                    'action' => $action,
                    'log_message' => $message,
                    'log_time' => current_time('mysql'),
                    'ip_address' => self::get_client_ip(),
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
                ],
                ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
            );
        } catch (Exception $e) {
            // 数据库记录失败时记录到错误日志
            error_log('[DHS-TUKU] Failed to log to database: ' . $e->getMessage());
        }
    }

    /**
     * 记录到文件
     */
    private static function log_to_file($message)
    {
        $log_dir = WP_CONTENT_DIR . '/dhs-tuku-logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        $log_file = $log_dir . '/dhs-tuku-' . date('Y-m-d') . '.log';
        
        // 写入日志文件
        file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);

        // 清理旧日志文件（保留30天）
        self::cleanup_old_logs($log_dir);
    }

    /**
     * 清理旧日志文件
     */
    private static function cleanup_old_logs($log_dir)
    {
        $files = glob($log_dir . '/dhs-tuku-*.log');
        $cutoff_time = time() - (30 * 24 * 60 * 60); // 30天前

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }

    /**
     * 获取客户端IP
     */
    private static function get_client_ip()
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * 记录用户操作
     */
    public static function log_user_action($action, $resource_type, $resource_id, $details = '')
    {
        $message = sprintf(
            'User action: %s on %s (ID: %d)',
            $action,
            $resource_type,
            $resource_id
        );

        if ($details) {
            $message .= ' - ' . $details;
        }

        self::info($message, [
            'action' => $action,
            'resource_type' => $resource_type,
            'resource_id' => $resource_id
        ]);
    }

    /**
     * 记录文件上传
     */
    public static function log_file_upload($filename, $album_id, $file_size, $success = true)
    {
        $message = sprintf(
            'File upload %s: %s (Size: %s) to album %d',
            $success ? 'successful' : 'failed',
            $filename,
            size_format($file_size),
            $album_id
        );

        if ($success) {
            self::info($message, [
                'action' => 'upload',
                'resource_type' => 'image',
                'resource_id' => $album_id
            ]);
        } else {
            self::error($message, [
                'action' => 'upload',
                'resource_type' => 'image',
                'resource_id' => $album_id
            ]);
        }
    }

    /**
     * 记录安全事件
     */
    public static function log_security_event($event_type, $details = '')
    {
        $message = sprintf('Security event: %s', $event_type);
        
        if ($details) {
            $message .= ' - ' . $details;
        }

        self::warning($message, [
            'action' => 'security',
            'resource_type' => 'system',
            'resource_id' => 0
        ]);
    }

    /**
     * 获取日志统计
     */
    public static function get_log_stats()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dhs_gallery_logs';
        
        $stats = $wpdb->get_results("
            SELECT 
                action,
                COUNT(*) as count,
                DATE(log_time) as log_date
            FROM {$table_name} 
            WHERE log_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY action, DATE(log_time)
            ORDER BY log_time DESC
        ");

        return $stats;
    }

    /**
     * 清理旧日志记录
     */
    public static function cleanup_old_database_logs()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dhs_gallery_logs';
        $retention_days = get_option('dhs_tuku_log_retention', 90);
        
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$table_name} 
            WHERE log_time < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $retention_days));

        self::info("Cleaned up {$deleted} old log records");
        
        return $deleted;
    }
}
