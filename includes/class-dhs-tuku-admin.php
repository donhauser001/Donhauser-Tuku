<?php

/**
 * DHS图库后台管理类
 */

class DHS_Tuku_Admin
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_notices', [__CLASS__, 'admin_notices']);

        // AJAX: 测试 AI 连接
        add_action('wp_ajax_dhs_test_ai_connection', [__CLASS__, 'ajax_test_ai_connection']);
        add_action('wp_ajax_dhs_batch_generate_ai_tags', [__CLASS__, 'ajax_stub_batch_ai']);
        add_action('wp_ajax_dhs_batch_generate_auto_tags', [__CLASS__, 'ajax_stub_batch_auto']);
    }

    /**
     * 添加管理菜单
     */
    public static function add_admin_menu()
    {
        add_menu_page(
            __('DHS图库', 'dhs-tuku'),
            __('DHS图库', 'dhs-tuku'),
            'manage_options',
            'dhs-tuku',
            [__CLASS__, 'admin_page'],
            'dashicons-images-alt2',
            30
        );

        add_submenu_page(
            'dhs-tuku',
            __('图库设置', 'dhs-tuku'),
            __('设置', 'dhs-tuku'),
            'manage_options',
            'dhs-tuku-settings',
            [__CLASS__, 'settings_page']
        );

        // 已合并统计到主页面，移除单独的“统计”子菜单

        add_submenu_page(
            'dhs-tuku',
            __('标签管理', 'dhs-tuku'),
            __('标签管理', 'dhs-tuku'),
            'manage_options',
            'dhs-tuku-tag-manager',
            [__CLASS__, 'tag_manager_page']
        );
    }

    /**
     * AJAX: 测试 AI 连接（后台）
     */
    public static function ajax_test_ai_connection()
    {
        check_ajax_referer('dhs_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('权限不足');
        }

        // 优先检测 LM Studio /v1/models
        $api_url = get_option('dhs_lmstudio_api_url', 'http://localhost:1234/v1/chat/completions');
        $models_url = '';
        if (preg_match('#/v1/#', $api_url)) {
            $parts = preg_split('#/v1/#', $api_url, 2);
            $base  = rtrim($parts[0], '/');
            $models_url = $base . '/v1/models';
        } else {
            $models_url = 'http://localhost:1234/v1/models';
        }

        $resp = wp_remote_get($models_url, [ 'timeout' => 5, 'sslverify' => false ]);
        if (!is_wp_error($resp) && (int) wp_remote_retrieve_response_code($resp) === 200) {
            wp_send_json_success();
        }

        // 若 LM Studio 不通，再检测 OpenAI（仅检查是否配置了 key）
        $openai = get_option('dhs_openai_api_key', '');
        if (!empty($openai)) {
            wp_send_json_success(['fallback' => 'openai']);
        }

        wp_send_json_error('LM Studio 不可用，且未配置 OpenAI Key');
    }

    /**
     * AI标签生成处理入口（重定向到前台处理函数）
     */
    public static function ajax_stub_batch_ai()
    {
        // 验证nonce和权限后，重定向到前台的成功实现
        if (!wp_verify_nonce($_POST['nonce'], 'dhs_nonce')) {
            wp_send_json_error(['message' => '安全验证失败']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '权限不足']);
            return;
        }
        
        // 重新设置参数以匹配前台实现的期望
        $_POST['_ajax_nonce'] = $_POST['nonce'];
        $_POST['album_id'] = 0; // 0表示处理所有图片，不限于特定相册
        
        // 调用前台的成功实现
        batch_generate_ai_tags_callback();
    }

    /**
     * 自动标签生成处理入口（重定向到前台处理函数）
     */
    public static function ajax_stub_batch_auto()
    {
        // 验证nonce和权限后，重定向到前台的成功实现
        if (!wp_verify_nonce($_POST['nonce'], 'dhs_nonce')) {
            wp_send_json_error(['message' => '安全验证失败']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '权限不足']);
            return;
        }
        
        // 重新设置参数以匹配前台实现的期望
        $_POST['_ajax_nonce'] = $_POST['nonce'];
        $_POST['album_id'] = 0; // 0表示处理所有图片，不限于特定相册
        
        // 调用前台的成功实现
        batch_generate_auto_tags_callback();
    }

    /**
     * 注册设置
     */
    public static function register_settings()
    {
        register_setting('dhs_tuku_settings', 'dhs_tuku_options');

        add_settings_section(
            'dhs_tuku_general',
            __('常规设置', 'dhs-tuku'),
            null,
            'dhs-tuku-settings'
        );

        add_settings_field(
            'max_upload_size',
            __('最大上传文件大小 (MB)', 'dhs-tuku'),
            [__CLASS__, 'max_upload_size_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_general'
        );

        add_settings_field(
            'allowed_file_types',
            __('允许的文件类型', 'dhs-tuku'),
            [__CLASS__, 'allowed_file_types_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_general'
        );

        add_settings_field(
            'enable_cache',
            __('启用缓存', 'dhs-tuku'),
            [__CLASS__, 'enable_cache_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_general'
        );

        // AI服务设置（LM Studio 优先）
        add_settings_section(
            'dhs_tuku_ai',
            __('AI服务设置（本地优先）', 'dhs-tuku'),
            [__CLASS__, 'ai_section_callback'],
            'dhs-tuku-settings'
        );

        // LM Studio 配置
        add_settings_field(
            'lmstudio_api_url',
            __('LM Studio 接口地址', 'dhs-tuku'),
            [__CLASS__, 'lmstudio_api_url_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_ai'
        );
        add_settings_field(
            'lmstudio_model',
            __('LM Studio 模型名称', 'dhs-tuku'),
            [__CLASS__, 'lmstudio_model_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_ai'
        );
        add_settings_field(
            'lmstudio_api_key',
            __('LM Studio API Key（可选）', 'dhs-tuku'),
            [__CLASS__, 'lmstudio_api_key_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_ai'
        );

        // 启用与语言
        add_settings_field(
            'enable_ai_tags',
            __('启用AI智能标签', 'dhs-tuku'),
            [__CLASS__, 'enable_ai_tags_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_ai'
        );
        add_settings_field(
            'ai_tag_language',
            __('AI标签语言', 'dhs-tuku'),
            [__CLASS__, 'ai_tag_language_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_ai'
        );

        // OpenAI（兜底，可选）
        add_settings_field(
            'openai_api_key',
            __('OpenAI API密钥（可选兜底）', 'dhs-tuku'),
            [__CLASS__, 'openai_api_key_callback'],
            'dhs-tuku-settings',
            'dhs_tuku_ai'
        );

        // 注册设置项
        register_setting('dhs_tuku_settings', 'dhs_openai_api_key');
        register_setting('dhs_tuku_settings', 'dhs_enable_ai_tags');
        register_setting('dhs_tuku_settings', 'dhs_ai_tag_language');
        register_setting('dhs_tuku_settings', 'dhs_lmstudio_api_url');
        register_setting('dhs_tuku_settings', 'dhs_lmstudio_model');
        register_setting('dhs_tuku_settings', 'dhs_lmstudio_api_key');
    }

    /**
     * 主管理页面
     */
    public static function admin_page()
    {
        global $wpdb;

        // 获取统计数据
        $stats = self::get_stats();

?>
        <div class="wrap">
            <h1><?php echo esc_html__('DHS图库管理', 'dhs-tuku'); ?></h1>

            <div class="dhs-admin-dashboard">
                <div class="dhs-stats-grid">
                    <div class="dhs-stat-box">
                        <h3><?php echo esc_html__('总相册数', 'dhs-tuku'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['total_albums']); ?></div>
                    </div>

                    <div class="dhs-stat-box">
                        <h3><?php echo esc_html__('总图片数', 'dhs-tuku'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['total_images']); ?></div>
                    </div>

                    <div class="dhs-stat-box">
                        <h3><?php echo esc_html__('总用户数', 'dhs-tuku'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['active_users']); ?></div>
                    </div>

                    <div class="dhs-stat-box">
                        <h3><?php echo esc_html__('存储使用', 'dhs-tuku'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['storage_used']); ?></div>
                    </div>
                </div>

                <div class="dhs-admin-actions">
                    <h2><?php echo esc_html__('快速操作', 'dhs-tuku'); ?></h2>

                    <button type="button" class="button button-primary" id="clear-cache">
                        <?php echo esc_html__('清理缓存', 'dhs-tuku'); ?>
                    </button>

                    <button type="button" class="button" id="regenerate-thumbnails">
                        <?php echo esc_html__('重新生成缩略图', 'dhs-tuku'); ?>
                    </button>

                    <button type="button" class="button" id="optimize-database">
                        <?php echo esc_html__('优化数据库', 'dhs-tuku'); ?>
                    </button>
                </div>

                <div class="dhs-recent-activity">
                    <h2><?php echo esc_html__('最近活动', 'dhs-tuku'); ?></h2>
                    <?php self::display_recent_activity(); ?>
                </div>

                <div class="dhs-stats-detailed" style="margin-top: 30px;">
                    <h2><?php echo esc_html__('详细统计', 'dhs-tuku'); ?></h2>
                    <p><?php echo esc_html__('详细统计功能开发中...', 'dhs-tuku'); ?></p>
                </div>
            </div>
        </div>

        <style>
            .dhs-admin-dashboard {
                margin-top: 20px;
            }

            .dhs-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }

            .dhs-stat-box {
                background: #fff;
                padding: 20px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                text-align: center;
            }

            .dhs-stat-box h3 {
                margin: 0 0 10px 0;
                color: #1d2327;
            }

            .stat-number {
                font-size: 2em;
                font-weight: bold;
                color: #2271b1;
            }

            .dhs-admin-actions {
                background: #fff;
                padding: 20px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                margin-bottom: 30px;
            }

            .dhs-admin-actions button {
                margin-right: 10px;
            }

            .dhs-recent-activity {
                background: #fff;
                padding: 20px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                $('#clear-cache').on('click', function() {
                    if (confirm('<?php echo esc_js(__("确定要清理所有缓存吗？", "dhs-tuku")); ?>')) {
                        // AJAX清理缓存
                        $.post(ajaxurl, {
                            action: 'dhs_clear_cache',
                            _wpnonce: '<?php echo wp_create_nonce("dhs_admin_nonce"); ?>'
                        }, function(response) {
                            if (response.success) {
                                alert('<?php echo esc_js(__("缓存已清理", "dhs-tuku")); ?>');
                            } else {
                                alert('<?php echo esc_js(__("清理失败", "dhs-tuku")); ?>');
                            }
                        });
                    }
                });
            });
        </script>
    <?php
    }

    /**
     * 设置页面
     */
    public static function settings_page()
    {
    ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DHS图库设置', 'dhs-tuku'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('dhs_tuku_settings');
                do_settings_sections('dhs-tuku-settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * 统计页面
     */
    public static function stats_page()
    {
    ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DHS图库统计', 'dhs-tuku'); ?></h1>

            <div class="dhs-stats-detailed">
                <!-- 详细统计内容 -->
                <p><?php echo esc_html__('详细统计功能开发中...', 'dhs-tuku'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * 获取统计数据
     */
    private static function get_stats()
    {
        global $wpdb;

        $total_albums = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_albums");
        $total_images = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images WHERE status = 'active'");
        $active_users = $wpdb->get_var("SELECT COUNT(DISTINCT created_by) FROM {$wpdb->prefix}dhs_gallery_albums");

        // 计算存储使用量
        $upload_dir = wp_upload_dir();
        $tuku_dir = $upload_dir['basedir'] . '/tuku';
        $storage_used = self::calculate_directory_size($tuku_dir);

        return [
            'total_albums' => $total_albums ?: 0,
            'total_images' => $total_images ?: 0,
            'active_users' => $active_users ?: 0,
            'storage_used' => self::format_bytes($storage_used)
        ];
    }

    /**
     * 计算目录大小
     */
    private static function calculate_directory_size($directory)
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * 格式化字节数
     */
    private static function format_bytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 显示最近活动
     */
    private static function display_recent_activity()
    {
        global $wpdb;

        $recent_albums = $wpdb->get_results("
            SELECT album_name, created_at, created_by 
            FROM {$wpdb->prefix}dhs_gallery_albums 
            ORDER BY created_at DESC 
            LIMIT 5
        ");

        if ($recent_albums) {
            echo '<ul>';
            foreach ($recent_albums as $album) {
                $user = get_user_by('id', $album->created_by);
                $user_name = $user ? $user->display_name : __('未知用户', 'dhs-tuku');

                printf(
                    '<li>%s - %s (%s)</li>',
                    esc_html($album->album_name),
                    esc_html($user_name),
                    esc_html(date('Y-m-d H:i', strtotime($album->created_at)))
                );
            }
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__('暂无活动记录', 'dhs-tuku') . '</p>';
        }
    }

    /**
     * 管理员通知
     */
    public static function admin_notices()
    {
        // 检查ImageMagick扩展
        if (!extension_loaded('imagick')) {
        ?>
            <div class="notice notice-warning">
                <p>
                    <?php echo esc_html__('建议安装ImageMagick扩展以获得更好的图片处理性能。', 'dhs-tuku'); ?>
                </p>
            </div>
        <?php
        }

        // 检查上传目录权限
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
        ?>
            <div class="notice notice-error">
                <p>
                    <?php echo esc_html__('上传目录不可写，请检查文件权限。', 'dhs-tuku'); ?>
                </p>
            </div>
        <?php
        }
    }

    // 设置字段回调函数
    public static function max_upload_size_callback()
    {
        $options = get_option('dhs_tuku_options');
        $value = $options['max_upload_size'] ?? 50;
        echo "<input type='number' name='dhs_tuku_options[max_upload_size]' value='" . esc_attr($value) . "' min='1' max='1000' />";
    }

    public static function allowed_file_types_callback()
    {
        $options = get_option('dhs_tuku_options');
        $value = $options['allowed_file_types'] ?? 'jpg,jpeg,png,gif,webp,psd,ai,svg';
        echo "<input type='text' name='dhs_tuku_options[allowed_file_types]' value='" . esc_attr($value) . "' size='50' />";
        echo "<p class='description'>" . esc_html__('用逗号分隔多个文件类型', 'dhs-tuku') . "</p>";
    }

    public static function enable_cache_callback()
    {
        $options = get_option('dhs_tuku_options');
        $value = $options['enable_cache'] ?? 1;
        echo "<input type='checkbox' name='dhs_tuku_options[enable_cache]' value='1' " . checked(1, $value, false) . " />";
        echo "<label>" . esc_html__('启用缓存可以提高性能', 'dhs-tuku') . "</label>";
    }

    /**
     * AI设置部分介绍
     */
    public static function ai_section_callback()
    {
        echo '<p>' . esc_html__('优先使用本地 LM Studio 的 OpenAI 兼容接口进行图片理解与标签生成；如未可用，将尝试使用 OpenAI（如已设置），最终回退基础算法。', 'dhs-tuku') . '</p>';

        // 显示 LM Studio 当前配置摘要
        $api_url = get_option('dhs_lmstudio_api_url', 'http://localhost:1234/v1/chat/completions');
        $model   = get_option('dhs_lmstudio_model', 'llava');
        echo '<p><strong>LM Studio:</strong> ' . esc_html($api_url) . ' | ' . esc_html($model) . '</p>';
    }

    /**
     * OpenAI API密钥设置
     */
    public static function openai_api_key_callback()
    {
        $value = get_option('dhs_openai_api_key', '');
        $display_value = !empty($value) ? substr($value, 0, 8) . '...' . substr($value, -4) : '';

        echo '<div class="api-key-field">';
        echo '<input type="password" id="openai_api_key" name="dhs_openai_api_key" value="' . esc_attr($value) . '" size="60" placeholder="sk-...（可留空）" autocomplete="off" />';
        echo '<button type="button" id="toggle-api-key" class="button button-secondary" style="margin-left: 10px;">显示</button>';
        echo '<p class="description">';
        echo esc_html__('可选项：当 LM Studio 不可用时作为兜底。', 'dhs-tuku');
        if (!empty($value)) {
            echo '<br><strong>当前密钥:</strong> ' . esc_html($display_value);
        }
        echo '</p>';
        echo '</div>';

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggleBtn = document.getElementById("toggle-api-key");
            const apiKeyField = document.getElementById("openai_api_key");
            if (toggleBtn && apiKeyField) {
                toggleBtn.addEventListener("click", function() {
                    if (apiKeyField.type === "password") { apiKeyField.type = "text"; toggleBtn.textContent = "隐藏"; }
                    else { apiKeyField.type = "password"; toggleBtn.textContent = "显示"; }
                });
            }
        });
        </script>';
    }

    /**
     * 启用AI标签设置
     */
    public static function enable_ai_tags_callback()
    {
        $value = get_option('dhs_enable_ai_tags', 1);
        echo '<input type="checkbox" name="dhs_enable_ai_tags" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>' . esc_html__('启用AI智能标签生成（LM Studio 本地优先）', 'dhs-tuku') . '</label>';
        echo '<p class="description">' . esc_html__('禁用后将只使用基础算法生成标签（文件名、EXIF、颜色分析）', 'dhs-tuku') . '</p>';
    }

    /**
     * AI标签语言设置
     */
    public static function ai_tag_language_callback()
    {
        $value = get_option('dhs_ai_tag_language', 'chinese');
        echo '<select name="dhs_ai_tag_language">';
        echo '<option value="chinese" ' . selected($value, 'chinese', false) . '>' . esc_html__('中文', 'dhs-tuku') . '</option>';
        echo '<option value="english" ' . selected($value, 'english', false) . '>' . esc_html__('English', 'dhs-tuku') . '</option>';
        echo '<option value="auto" ' . selected($value, 'auto', false) . '>' . esc_html__('自动检测', 'dhs-tuku') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('选择AI生成标签的主要语言', 'dhs-tuku') . '</p>';
    }

    /**
     * LM Studio 接口地址字段
     */
    public static function lmstudio_api_url_callback()
    {
        $value = get_option('dhs_lmstudio_api_url', 'http://localhost:1234/v1/chat/completions');
        echo "<input type='text' name='dhs_lmstudio_api_url' value='" . esc_attr($value) . "' size='60' placeholder='http://localhost:1234/v1/chat/completions' />";
        echo "<p class='description'>" . esc_html__('LM Studio 的 OpenAI 兼容 Chat Completions 接口地址。默认：http://localhost:1234/v1/chat/completions', 'dhs-tuku') . "</p>";
    }

    /**
     * LM Studio 模型名称字段
     */
    public static function lmstudio_model_callback()
    {
        $current = get_option('dhs_lmstudio_model', 'llava');
        $api_url = get_option('dhs_lmstudio_api_url', 'http://localhost:1234/v1/chat/completions');

        // 尝试推断 /v1/models 地址
        $models_url = '';
        if (preg_match('#/v1/#', $api_url)) {
            $parts = preg_split('#/v1/#', $api_url, 2);
            $base  = rtrim($parts[0], '/');
            $models_url = $base . '/v1/models';
        } elseif (str_ends_with($api_url, '/chat/completions')) {
            $models_url = substr($api_url, 0, -strlen('/chat/completions')) . '/models';
        }
        if (empty($models_url)) {
            $models_url = 'http://localhost:1234/v1/models';
        }

        // 获取模型列表
        $models = [];
        $error  = '';
        $resp = wp_remote_get($models_url, [ 'timeout' => 3, 'sslverify' => false ]);
        if (!is_wp_error($resp)) {
            $code = (int) wp_remote_retrieve_response_code($resp);
            if ($code >= 200 && $code < 300) {
                $body = wp_remote_retrieve_body($resp);
                $json = json_decode($body, true);
                // OpenAI 兼容返回通常包含 data 数组
                if (isset($json['data']) && is_array($json['data'])) {
                    foreach ($json['data'] as $entry) {
                        if (isset($entry['id'])) {
                            $models[] = (string) $entry['id'];
                        }
                    }
                }
            } else {
                $error = 'HTTP ' . $code;
            }
        } else {
            $error = $resp->get_error_message();
        }

        // 渲染：下拉 + 文本框（联动），下拉为空时仅展示文本框
        if (!empty($models)) {
            echo "<select id='lmstudio_model_list' style='min-width:260px;'>";
            $found = false;
            foreach ($models as $id) {
                $selected = selected($current, $id, false);
                if ($selected) { $found = true; }
                echo "<option value='" . esc_attr($id) . "' $selected>" . esc_html($id) . "</option>";
            }
            // 如果当前值不在列表中，追加为自定义项
            if (!$found && !empty($current)) {
                echo "<option value='" . esc_attr($current) . "' selected>" . esc_html($current) . "</option>";
            }
            echo "</select> ";

            echo "<input type='text' id='lmstudio_model_input' name='dhs_lmstudio_model' value='" . esc_attr($current) . "' size='24' placeholder='llava / qwen-vl / ...' style='margin-left:8px;' />";
            echo "<p class='description'>" . esc_html__('从下拉列表选择或手动输入。列表来源于 LM Studio /v1/models。', 'dhs-tuku') . "</p>";

            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var sel = document.getElementById('lmstudio_model_list');
                var inp = document.getElementById('lmstudio_model_input');
                if (sel && inp) {
                    sel.addEventListener('change', function(){ inp.value = sel.value; });
                }
            });
            </script>";
        } else {
            echo "<input type='text' name='dhs_lmstudio_model' value='" . esc_attr($current) . "' size='30' placeholder='llava / qwen-vl / ...' />";
            echo "<p class='description'>" . esc_html__('无法自动获取模型列表，请确认 LM Studio 已启动并配置正确；也可手动填写模型名称。', 'dhs-tuku') . "</p>";
            if (!empty($error)) {
                echo "<p class='description' style='color:#a00;'>" . esc_html__('获取模型列表失败：', 'dhs-tuku') . esc_html($error) . "</p>";
            }
        }
    }

    /**
     * LM Studio API Key（可选）字段
     */
    public static function lmstudio_api_key_callback()
    {
        $value = get_option('dhs_lmstudio_api_key', '');
        echo "<input type='password' name='dhs_lmstudio_api_key' value='" . esc_attr($value) . "' size='40' placeholder='可留空' autocomplete='off' />";
        echo "<p class='description'>" . esc_html__('一般无需设置；仅当你的本地服务要求鉴权时填写。', 'dhs-tuku') . "</p>";
    }

    /**
     * 标签管理页面
     */
    public static function tag_manager_page()
    {
        // 处理AI标签设置保存
        if (isset($_POST['action']) && $_POST['action'] === 'save_ai_settings') {
            self::save_ai_settings();
        }
        
        // 处理标签操作
        if (isset($_POST['action']) && $_POST['action'] === 'manage_tags') {
            self::handle_tag_actions();
        }

        // 获取标签数据（包含分页信息）
        $tags_data = self::get_all_tags();
        $tags = $tags_data['items'];
        $pagination = [
            'total_items' => $tags_data['total_items'],
            'total_pages' => $tags_data['total_pages'],
            'current_page' => $tags_data['current_page'],
            'per_page' => $tags_data['per_page']
        ];

    ?>
        <div class="wrap tag-manager-admin-container">
            <h1><?php _e('标签管理', 'dhs-tuku'); ?></h1>

            <!-- 自动标签功能（只读摘要 + 操作按钮） -->
            <div class="card">
                <h2><?php _e('AI自动标签生成', 'dhs-tuku'); ?></h2>

                <?php
                    $ai_enabled = (int) get_option('dhs_enable_ai_tags', 1);
                    $lang_opt   = get_option('dhs_ai_tag_language', 'chinese');
                    // 兼容历史值
                    $lang_label = '中文';
                    if (in_array($lang_opt, ['english','en'], true)) { $lang_label = 'English'; }
                    if (in_array($lang_opt, ['auto'], true)) { $lang_label = '自动'; }

                    $lm_url   = get_option('dhs_lmstudio_api_url', 'http://localhost:1234/v1/chat/completions');
                    $lm_model = get_option('dhs_lmstudio_model', 'llava');
                    $lm_key   = get_option('dhs_lmstudio_api_key', '');
                    $openai   = get_option('dhs_openai_api_key', '');

                    $mask = function($s) {
                        if (!$s) return '（未设置）';
                        $len = strlen($s);
                        if ($len <= 8) return '****';
                        return substr($s, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($s, -4);
                    };
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">状态</th>
                        <td>
                            <span style="font-weight:600;color:<?php echo $ai_enabled ? '#0a7' : '#a00'; ?>;">
                                <?php echo $ai_enabled ? '已启用' : '已禁用'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">标签语言</th>
                        <td><?php echo esc_html($lang_label); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">LM Studio 接口</th>
                        <td><?php echo esc_html($lm_url); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">LM Studio 模型</th>
                        <td><?php echo esc_html($lm_model); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">LM Studio API Key</th>
                        <td><?php echo esc_html($mask($lm_key)); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">OpenAI API Key（兜底）</th>
                        <td><?php echo esc_html($mask($openai)); ?></td>
                    </tr>
                </table>

                <div class="ai-tags-actions" style="margin-top: 10px;">
                    <button type="button" class="button button-primary" onclick="testAIConnection()">
                        <i class="fas fa-test-tube"></i> 测试AI连接
                    </button>
                    <button type="button" class="dhs-btn dhs-btn-secondary" onclick="showBatchTagsModal()" style="display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-update" style="font-family: dashicons !important; font-size: 16px; line-height: 1; width: 16px; height: 16px; display: inline-block;"></span>
                        <?php _e('批量生成标签', 'dhs-tuku'); ?>
                    </button>
                </div>
            </div>

            

            <!-- 标签列表 -->
            <div class="card">
                <h2><?php _e('标签列表', 'dhs-tuku'); ?></h2>
                
                <!-- 清空所有标签按钮 -->
                <div class="clear-all-tags-section" >
                    <button type="button" class="button button-primary" style="background-color: #dc3545; border-color: #dc3545;" onclick="clearAllTags()">
                        <i class="fas fa-trash-alt"></i> <?php _e('清空所有标签', 'dhs-tuku'); ?>
                    </button>  <p style="margin-bottom: 15px; color: #856404;"><?php _e('警告：清空所有标签将删除所有标签和图片标签关联，此操作不可逆！', 'dhs-tuku'); ?></p>

                </div>
                
                <?php if (!empty($tags)): ?>
                    <!-- 表格顶部分页导航 -->
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <span class="displaying-num">
                                <?php printf(__('共 %s 个标签', 'dhs-tuku'), $pagination['total_items']); ?>
                            </span>
                        </div>
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div class="tablenav-pages">
                                <?php
                                $page_links = paginate_links([
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'current' => $pagination['current_page'],
                                    'total' => $pagination['total_pages'],
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'type' => 'plain'
                                ]);
                                echo $page_links;
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 标签列表表格 -->
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <?php
                                $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'tag_name';
                                $current_order = isset($_GET['order']) ? $_GET['order'] : 'asc';
                                
                                // 标签名称排序链接
                                $tag_name_order = ($current_orderby === 'tag_name' && $current_order === 'asc') ? 'desc' : 'asc';
                                $tag_name_url = add_query_arg(['orderby' => 'tag_name', 'order' => $tag_name_order]);
                                $tag_name_class = $current_orderby === 'tag_name' ? 'sorted ' . $current_order : 'sortable';
                                
                                // 使用次数排序链接
                                $usage_order = ($current_orderby === 'usage_count' && $current_order === 'asc') ? 'desc' : 'asc';
                                $usage_url = add_query_arg(['orderby' => 'usage_count', 'order' => $usage_order]);
                                $usage_class = $current_orderby === 'usage_count' ? 'sorted ' . $current_order : 'sortable';
                                ?>
                                <th class="<?php echo esc_attr($tag_name_class); ?>">
                                    <a href="<?php echo esc_url($tag_name_url); ?>">
                                        <?php _e('标签名称', 'dhs-tuku'); ?>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="<?php echo esc_attr($usage_class); ?>">
                                    <a href="<?php echo esc_url($usage_url); ?>">
                                        <?php _e('使用次数', 'dhs-tuku'); ?>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th><?php _e('操作', 'dhs-tuku'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tags as $tag): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($tag->tag_name); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($tag->usage_count); ?></td>
                                    <td>
                                        <button type="button" class="button edit-tag"
                                            data-tag-id="<?php echo esc_attr($tag->tag_id); ?>"
                                            data-tag-name="<?php echo esc_attr($tag->tag_name); ?>">
                                            <?php _e('编辑', 'dhs-tuku'); ?>
                                        </button>
                                        <button type="button" class="button button-link-delete delete-tag"
                                            data-tag-id="<?php echo esc_attr($tag->tag_id); ?>"
                                            data-tag-name="<?php echo esc_attr($tag->tag_name); ?>">
                                            <?php _e('删除', 'dhs-tuku'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- 表格底部分页导航 -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="tablenav bottom">
                            <div class="alignleft actions">
                                <span class="displaying-num">
                                    <?php printf(__('共 %s 个标签', 'dhs-tuku'), $pagination['total_items']); ?>
                                </span>
                            </div>
                            <div class="tablenav-pages">
                                <?php
                                $page_links = paginate_links([
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'current' => $pagination['current_page'],
                                    'total' => $pagination['total_pages'],
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'type' => 'plain'
                                ]);
                                echo $page_links;
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p><?php _e('暂无标签', 'dhs-tuku'); ?></p>
                <?php endif; ?>
            </div>

            <!-- 编辑标签模态框 -->
            <div id="edit-tag-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2><?php _e('编辑标签', 'dhs-tuku'); ?></h2>
                    <form method="post" action="" id="edit-tag-form">
                        <input type="hidden" name="action" value="manage_tags">
                        <input type="hidden" name="tag_action" value="edit">
                        <input type="hidden" name="tag_id" id="edit-tag-id">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('标签名称', 'dhs-tuku'); ?></th>
                                <td>
                                    <input type="text" name="tag_name" id="edit-tag-name" class="regular-text" required>
                                </td>
                            </tr>

                        </table>
                        <?php submit_button(__('更新标签', 'dhs-tuku')); ?>
                    </form>
                </div>
            </div>

            <style>
                .tag-manager-admin-container .card {
                    background: white;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    padding: 20px;
                    margin-bottom: 20px;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }

                .tag-manager-admin-container .card h2 {
                    margin-top: 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }

                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.4);
                }

                .modal-content {
                    background-color: #fefefe;
                    margin: 15% auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 50%;
                    border-radius: 5px;
                }

                .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                }

                .close:hover,
                .close:focus {
                    color: black;
                    text-decoration: none;
                    cursor: pointer;
                }

                .button-link-delete {
                    color: #a00;
                }

                .button-link-delete:hover {
                    color: #dc3232;
                }
            </style>

            <script>
                jQuery(document).ready(function($) {
                    // 编辑标签
                    $('.edit-tag').click(function() {
                        var tagId = $(this).data('tag-id');
                        var tagName = $(this).data('tag-name');

                        $('#edit-tag-id').val(tagId);
                        $('#edit-tag-name').val(tagName);
                        $('#edit-tag-modal').show();
                    });

                    // 关闭模态框
                    $('.close').click(function() {
                        $('#edit-tag-modal').hide();
                    });

                    // 点击模态框外部关闭
                    $(window).click(function(event) {
                        if (event.target == $('#edit-tag-modal')[0]) {
                            $('#edit-tag-modal').hide();
                        }
                    });

                    // 删除标签确认
                    $('.delete-tag').click(function() {
                        var tagId = $(this).data('tag-id');
                        var tagName = $(this).data('tag-name');

                        if (confirm('确定要删除标签 "' + tagName + '" 吗？此操作不可撤销。')) {
                            var form = $('<form method="post" action=""></form>');
                            form.append('<input type="hidden" name="action" value="manage_tags">');
                            form.append('<input type="hidden" name="tag_action" value="delete">');
                            form.append('<input type="hidden" name="tag_id" value="' + tagId + '">');
                            $('body').append(form);
                            form.submit();
                        }
                    });
                });
                

            </script>
            
            <!-- 清空所有标签的JavaScript函数 -->
            <script>
            // showNotice 函数定义
            function showNotice(message, type) {
                var noticeClass = 'notice ';
                switch(type) {
                    case 'success':
                        noticeClass += 'notice-success';
                        break;
                    case 'error':
                        noticeClass += 'notice-error';
                        break;
                    case 'warning':
                        noticeClass += 'notice-warning';
                        break;
                    case 'info':
                    default:
                        noticeClass += 'notice-info';
                        break;
                }
                
                var notice = jQuery('<div class="' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
                jQuery('.wrap').prepend(notice);
                
                setTimeout(function() {
                    notice.fadeOut(function() {
                        notice.remove();
                    });
                }, 5000);
            }
            
            function clearAllTags() {
                if (confirm('确定要清空所有标签吗？此操作将删除所有标签和图片标签关联，不可逆！')) {
                    // 显示加载状态
                    const button = event.target;
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 正在清空...';
                    button.disabled = true;
                    
                    // 发送AJAX请求
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'dhs_clear_all_tags',
                            nonce: '<?php echo wp_create_nonce('dhs_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('所有标签已成功清空！');
                                location.reload();
                            } else {
                                alert('清空标签失败：' + (response.data || '未知错误'));
                                button.innerHTML = originalText;
                                button.disabled = false;
                            }
                        },
                        error: function() {
                            alert('网络错误，请重试');
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }
                    });
                }
            }

            // AI标签功能
            function testAIConnection() {
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 测试中...';
                button.disabled = true;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dhs_test_ai_connection',
                        nonce: '<?php echo wp_create_nonce('dhs_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('AI连接测试成功！');
                        } else {
                            alert('AI连接测试失败：' + (response.data || '未知错误'));
                        }
                        button.innerHTML = originalText;
                        button.disabled = false;
                    },
                    error: function() {
                        alert('网络错误，请重试');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                });
            }

            // 已移除单张生成按钮

            // 全局变量用于跟踪处理状态
            let currentBatchState = {
                totalImages: 0,
                processedCount: 0,
                isRunning: false,
                selectedMethod: 'auto'
            };

            // 显示批量标签生成方式选择模态窗
            function showBatchTagsModal() {
                const modal = document.createElement('div');
                modal.id = 'batch-tags-modal';
                modal.className = 'ai-progress-modal';
                modal.innerHTML = `
                    <div class="ai-progress-content" style="max-width: 500px;">
                        <h3>选择标签生成方式</h3>
                        <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                                    <input type="radio" name="batch_tag_method" value="auto" checked style="margin-right: 8px;">
                                    <span style="color: #667eea;">🔧 自动生成标签</span>
                                    <small style="display: block; margin-left: 20px; color: #666;">基于文件名和相册信息生成标签（快速）</small>
                                </label>
                                <label style="display: block; cursor: pointer;">
                                    <input type="radio" name="batch_tag_method" value="ai" style="margin-right: 8px;">
                                    <span style="color: #e60023;">🤖 AI智能标签</span>
                                    <small style="display: block; margin-left: 20px; color: #666;">使用AI图像识别技术生成标签</small>
                                </label>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="button" class="button" onclick="closeBatchTagsModal()">取消</button>
                            <button type="button" class="button button-primary" onclick="startBatchGeneration()">开始生成</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            function closeBatchTagsModal() {
                const modal = document.getElementById('batch-tags-modal');
                if (modal) {
                    modal.remove();
                }
            }

            function startBatchGeneration() {
                const selectedMethod = document.querySelector('input[name="batch_tag_method"]:checked')?.value || 'auto';
                currentBatchState.selectedMethod = selectedMethod;
                
                closeBatchTagsModal();
                
                if (confirm('确定要批量生成标签吗？这将为所有图片生成' + (selectedMethod === 'ai' ? 'AI智能' : '自动') + '标签。')) {
                    batchGenerateAITags();
                }
            }

            function batchGenerateAITags() {
                // 创建进度显示模态框
                createProgressModal();
                
                // 首先获取需要处理的图片总数
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dhs_get_images_count',
                        nonce: '<?php echo wp_create_nonce('dhs_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            currentBatchState.totalImages = response.data.total;
                            currentBatchState.processedCount = 0;
                            currentBatchState.isRunning = true;
                            
                            if (currentBatchState.totalImages === 0) {
                                closeProgressModal();
                                showNotice('没有找到需要生成标签的图片', 'info');
                                return;
                            }
                            
                            const methodName = currentBatchState.selectedMethod === 'ai' ? 'AI智能标签' : '自动标签';
                            updateProgress(0, currentBatchState.totalImages, `开始生成${methodName}...`);
                            startBatchProcessing(currentBatchState.totalImages);
                        } else {
                            closeProgressModal();
                            showNotice('获取图片信息失败：' + (response.data || '未知错误'), 'error');
                        }
                    },
                    error: function() {
                        closeProgressModal();
                        showNotice('网络错误，请重试', 'error');
                    }
                });
            }

            function startBatchProcessing(totalImages) {
                const batchSize = 5; // 每批处理5张图片
                
                function processBatch(offset) {
                    // 根据选择的方法调用不同的action
                    const action = currentBatchState.selectedMethod === 'ai' ? 'dhs_batch_generate_ai_tags' : 'dhs_batch_generate_auto_tags';
                    
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: action,
                            offset: offset,
                            limit: batchSize,
                            nonce: '<?php echo wp_create_nonce('dhs_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                currentBatchState.processedCount += response.data.processed;
                                const percentage = Math.round((currentBatchState.processedCount / currentBatchState.totalImages) * 100);
                                
                                // 获取最后处理的文件名用于显示
                                let lastFile = null;
                                if (response.data.processed_files && response.data.processed_files.length > 0) {
                                    const lastProcessed = response.data.processed_files[response.data.processed_files.length - 1];
                                    lastFile = `${lastProcessed.name} (${lastProcessed.album_name})`;
                                }
                                
                                updateProgress(currentBatchState.processedCount, currentBatchState.totalImages, 
                                    `已处理 ${currentBatchState.processedCount}/${currentBatchState.totalImages} 张图片 (${percentage}%)`, lastFile);
                                
                                // 更新后台处理状态
                                if (backgroundProcessingState.isRunning) {
                                    backgroundProcessingState.processedCount = currentBatchState.processedCount;
                                    localStorage.setItem('dhsBackgroundProcessing', JSON.stringify(backgroundProcessingState));
                                }
                                
                                if (currentBatchState.processedCount < currentBatchState.totalImages) {
                                    // 继续处理下一批
                                    setTimeout(() => processBatch(offset + batchSize), 500);
                                } else {
                                    // 处理完成
                                    currentBatchState.isRunning = false;
                                    setTimeout(() => {
                                        closeProgressModal();
                                        clearBackgroundProcessing(); // 清除后台处理状态
                                        showNotice(`批量AI标签生成完成！共处理 ${currentBatchState.processedCount} 张图片`, 'success');
                                        // 刷新页面显示新生成的标签
                                        location.reload();
                                    }, 1000);
                                }
                            } else {
                                currentBatchState.isRunning = false;
                                closeProgressModal();
                                clearBackgroundProcessing(); // 清除后台处理状态
                                showNotice('批量处理失败：' + (response.data || '未知错误'), 'error');
                            }
                        },
                        error: function() {
                            currentBatchState.isRunning = false;
                            closeProgressModal();
                            clearBackgroundProcessing();
                            showNotice('网络错误，批量处理中断', 'error');
                        }
                    });
                }
                
                // 开始第一批处理
                processBatch(0);
            }

            function createProgressModal() {
                const modal = document.createElement('div');
                modal.id = 'ai-progress-modal';
                modal.className = 'ai-progress-modal';
                const methodName = currentBatchState.selectedMethod === 'ai' ? 'AI智能标签' : '自动标签';
                modal.innerHTML = `
                    <div class="ai-progress-content">
                        <h3>${methodName}生成进度</h3>
                        <div class="current-file" id="current-file" style="margin: 10px 0; font-size: 13px; color: #666; font-weight: 500;"></div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" id="progress-bar"></div>
                        </div>
                        <div class="progress-text" id="progress-text" style="margin-top: 8px;">正在初始化...</div>
                        <div class="progress-actions" style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="button" class="button" onclick="cancelBatchProcessing()">取消</button>
                            <button type="button" class="button button-primary" onclick="moveToBackground()" id="background-btn">后台处理</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            function updateProgress(current, total, message, currentFile = null) {
                const percentage = total > 0 ? (current / total) * 100 : 0;
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');
                const currentFileDiv = document.getElementById('current-file');
                
                if (progressBar) {
                    progressBar.style.width = percentage + '%';
                }
                if (progressText) {
                    progressText.textContent = message;
                }
                if (currentFileDiv) {
                    if (currentFile) {
                        currentFileDiv.innerHTML = `<span style="color: #444;">当前处理：</span><span style="color: #e60023;">${currentFile}</span>`;
                        currentFileDiv.style.display = 'block';
                    } else {
                        currentFileDiv.style.display = 'none';
                    }
                }
            }

            function closeProgressModal() {
                const modal = document.getElementById('ai-progress-modal');
                if (modal) {
                    modal.remove();
                }
            }

            function cancelBatchProcessing() {
                if (confirm('确定要取消批量处理吗？')) {
                    closeProgressModal();
                    clearBackgroundProcessing();
                }
            }
            
            // 后台处理相关功能
            let backgroundProcessingState = {
                isRunning: false,
                totalImages: 0,
                processedCount: 0,
                currentOffset: 0
            };
            
            function moveToBackground() {
                if (confirm('确定要将处理移至后台吗？您可以继续使用其他功能，处理将在后台继续进行。')) {
                    backgroundProcessingState.isRunning = true;
                    backgroundProcessingState.totalImages = currentBatchState.totalImages;
                    backgroundProcessingState.processedCount = currentBatchState.processedCount;
                    backgroundProcessingState.currentOffset = Math.floor(currentBatchState.processedCount / 5) * 5; // 根据当前进度计算offset
                    
                    closeProgressModal();
                    showBackgroundProcessingIndicator();
                    
                    // 在localStorage中保存状态
                    localStorage.setItem('dhsBackgroundProcessing', JSON.stringify(backgroundProcessingState));
                }
            }
            
            function showBackgroundProcessingIndicator() {
                // 在页面标题后添加进度指示器
                const titleElement = document.querySelector('.wrap h1');
                if (titleElement && !document.getElementById('background-indicator')) {
                    const indicator = document.createElement('span');
                    indicator.id = 'background-indicator';
                    indicator.innerHTML = ` <span style="color: #e60023; font-size: 14px;">(AI标签生成中... <a href="#" onclick="showBackgroundProgress()" style="color: #e60023;">查看进度</a>)</span>`;
                    titleElement.appendChild(indicator);
                }
            }
            
            function clearBackgroundProcessing() {
                backgroundProcessingState.isRunning = false;
                localStorage.removeItem('dhsBackgroundProcessing');
                
                const indicator = document.getElementById('background-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }
            
            function showBackgroundProgress() {
                if (backgroundProcessingState.isRunning) {
                    createProgressModal();
                    updateProgress(backgroundProcessingState.processedCount, backgroundProcessingState.totalImages, 
                        `已处理 ${backgroundProcessingState.processedCount}/${backgroundProcessingState.totalImages} 张图片`);
                }
            }
            
            // 页面加载时检查是否有后台处理
            jQuery(document).ready(function($) {
                const savedState = localStorage.getItem('dhsBackgroundProcessing');
                if (savedState) {
                    try {
                        backgroundProcessingState = JSON.parse(savedState);
                        if (backgroundProcessingState.isRunning) {
                            showBackgroundProcessingIndicator();
                        }
                    } catch (e) {
                        localStorage.removeItem('dhsBackgroundProcessing');
                    }
                }
            });
            </script>
        </div>

        <style>
        .tag-manager-admin-container .card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .tag-manager-admin-container .card h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            color: #23282d;
        }

        .ai-tags-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ai-tags-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .clear-all-tags-section {
            background-color: #fff3cd !important;
            border: 1px solid #ffeaa7 !important;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .clear-all-tags-section h3 {
            margin-top: 0;
            color: #856404;
        }

        .clear-all-tags-section p {
            color: #856404;
            margin-bottom: 15px;
        }

        .clear-all-tags-section .button {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }

        .clear-all-tags-section .button:hover {
            background-color: #c82333 !important;
            border-color: #bd2130 !important;
        }
        </style>
    <?php
    }

    /**
     * 处理标签操作
     */
    private static function handle_tag_actions()
    {
        $tag_action = $_POST['tag_action'] ?? '';

        switch ($tag_action) {
            // 后台不再支持新增标签
            case 'edit':
                self::edit_tag();
                break;
            case 'delete':
                self::delete_tag();
                break;
        }
    }

    /**
     * 添加标签
     */
    // 已移除后台添加标签功能

    /**
     * 编辑标签
     */
    private static function edit_tag()
    {
        $tag_id = intval($_POST['tag_id'] ?? 0);
        $tag_name = sanitize_text_field($_POST['tag_name'] ?? '');

        if (empty($tag_id) || empty($tag_name)) {
            add_settings_error('dhs_tuku_tags', 'tag_edit_invalid', __('无效的标签信息', 'dhs-tuku'), 'error');
            return;
        }

        global $wpdb;

        // 检查标签名称是否与其他标签重复
        $existing_tag = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s AND id != %d",
            $tag_name,
            $tag_id
        ));

        if ($existing_tag) {
            add_settings_error('dhs_tuku_tags', 'tag_name_exists', __('标签名称已存在', 'dhs-tuku'), 'error');
            return;
        }

        // 更新标签
        $result = $wpdb->update(
            $wpdb->prefix . 'dhs_gallery_tags',
            array(
                'tag_name' => $tag_name
            ),
            array('id' => $tag_id),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            add_settings_error('dhs_tuku_tags', 'tag_updated', __('标签更新成功', 'dhs-tuku'), 'success');
        } else {
            add_settings_error('dhs_tuku_tags', 'tag_update_failed', __('标签更新失败', 'dhs-tuku'), 'error');
        }
    }

    /**
     * 删除标签
     */
    private static function delete_tag()
    {
        $tag_id = intval($_POST['tag_id'] ?? 0);

        if (empty($tag_id)) {
            add_settings_error('dhs_tuku_tags', 'tag_delete_invalid', __('无效的标签ID', 'dhs-tuku'), 'error');
            return;
        }

        global $wpdb;

        // 开始事务
        $wpdb->query('START TRANSACTION');

        try {
            // 删除标签关联
            $wpdb->delete(
                $wpdb->prefix . 'dhs_gallery_image_tag',
                array('tag_id' => $tag_id),
                array('%d')
            );

            // 删除标签
            $result = $wpdb->delete(
                $wpdb->prefix . 'dhs_gallery_tags',
                array('id' => $tag_id),
                array('%d')
            );

            if ($result !== false) {
                $wpdb->query('COMMIT');
                add_settings_error('dhs_tuku_tags', 'tag_deleted', __('标签删除成功', 'dhs-tuku'), 'success');
            } else {
                throw new Exception('删除标签失败');
            }
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            add_settings_error('dhs_tuku_tags', 'tag_delete_failed', __('标签删除失败', 'dhs-tuku'), 'error');
        }
    }

    /**
     * 获取标签列表（带分页和排序）
     */
    private static function get_all_tags()
    {
        global $wpdb;
        $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
        $image_tag_table = $wpdb->prefix . 'dhs_gallery_image_tag';

        // 获取分页参数
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20; // 每页显示20个标签
        $offset = ($page - 1) * $per_page;

        // 获取排序参数
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'tag_name';
        $order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

        // 验证排序字段
        $allowed_orderby = ['tag_name', 'usage_count'];
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'tag_name';
        }

        // 构建查询
        $query = "
            SELECT t.id as tag_id, t.tag_name, COUNT(it.image_id) as usage_count
            FROM {$tags_table} t
            LEFT JOIN {$image_tag_table} it ON t.id = it.tag_id
            GROUP BY t.id, t.tag_name
            ORDER BY {$orderby} {$order}
            LIMIT {$per_page} OFFSET {$offset}
        ";

        $results = $wpdb->get_results($query);

        // 获取总数
        $count_query = "
            SELECT COUNT(DISTINCT t.id) as total
            FROM {$tags_table} t
        ";
        $total_items = $wpdb->get_var($count_query);

        return [
            'items' => $results,
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }

    /**
     * 保存AI标签设置
     */
    private static function save_ai_settings()
    {
        if (!current_user_can('manage_options')) {
            add_settings_error('dhs_tuku_ai', 'permission_denied', __('权限不足', 'dhs-tuku'), 'error');
            return;
        }

        // OpenAI（可选）
        if (isset($_POST['dhs_openai_api_key'])) {
            $api_key = sanitize_text_field($_POST['dhs_openai_api_key']);
            update_option('dhs_openai_api_key', $api_key);
        }

        // 启用状态
        $enable_ai = isset($_POST['dhs_enable_ai_tags']) ? 1 : 0;
        update_option('dhs_enable_ai_tags', $enable_ai);

        // 语言
        if (isset($_POST['dhs_ai_tag_language'])) {
            $language = sanitize_text_field($_POST['dhs_ai_tag_language']);
            update_option('dhs_ai_tag_language', $language);
        }

        // LM Studio 配置
        if (isset($_POST['dhs_lmstudio_api_url'])) {
            update_option('dhs_lmstudio_api_url', esc_url_raw($_POST['dhs_lmstudio_api_url']));
        }
        if (isset($_POST['dhs_lmstudio_model'])) {
            update_option('dhs_lmstudio_model', sanitize_text_field($_POST['dhs_lmstudio_model']));
        }
        if (isset($_POST['dhs_lmstudio_api_key'])) {
            update_option('dhs_lmstudio_api_key', sanitize_text_field($_POST['dhs_lmstudio_api_key']));
        }

        add_settings_error('dhs_tuku_ai', 'ai_settings_saved', __('AI标签设置已保存', 'dhs-tuku'), 'success');
    }
}
