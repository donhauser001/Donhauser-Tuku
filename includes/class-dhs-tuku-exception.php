<?php
/**
 * DHS图库异常处理类
 */

/**
 * 基础异常类
 */
class DHS_Tuku_Exception extends Exception
{
    protected $error_code;
    protected $user_message;
    
    public function __construct($message = '', $error_code = 0, $user_message = '', Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->error_code = $error_code;
        $this->user_message = $user_message ?: $message;
    }
    
    public function getErrorCode()
    {
        return $this->error_code;
    }
    
    public function getUserMessage()
    {
        return $this->user_message;
    }
    
    /**
     * 记录异常到日志
     */
    public function logException()
    {
        DHS_Tuku_Logger::error($this->getMessage(), [
            'exception_class' => get_class($this),
            'error_code' => $this->error_code,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ]);
    }
}

/**
 * 文件上传异常
 */
class DHS_Tuku_Upload_Exception extends DHS_Tuku_Exception
{
    const ERROR_FILE_TOO_LARGE = 1001;
    const ERROR_INVALID_FILE_TYPE = 1002;
    const ERROR_UPLOAD_FAILED = 1003;
    const ERROR_SECURITY_CHECK_FAILED = 1004;
    
    protected static $error_messages = [
        self::ERROR_FILE_TOO_LARGE => '文件太大',
        self::ERROR_INVALID_FILE_TYPE => '文件类型不支持',
        self::ERROR_UPLOAD_FAILED => '文件上传失败',
        self::ERROR_SECURITY_CHECK_FAILED => '文件安全检查失败'
    ];
    
    public function __construct($error_code, $details = '', Exception $previous = null)
    {
        $message = self::$error_messages[$error_code] ?? '未知上传错误';
        $full_message = $details ? "{$message}: {$details}" : $message;
        
        parent::__construct($full_message, $error_code, $message, $previous);
    }
}

/**
 * 数据库异常
 */
class DHS_Tuku_Database_Exception extends DHS_Tuku_Exception
{
    const ERROR_CONNECTION_FAILED = 2001;
    const ERROR_QUERY_FAILED = 2002;
    const ERROR_DATA_NOT_FOUND = 2003;
    const ERROR_CONSTRAINT_VIOLATION = 2004;
    
    protected static $error_messages = [
        self::ERROR_CONNECTION_FAILED => '数据库连接失败',
        self::ERROR_QUERY_FAILED => '数据库查询失败',
        self::ERROR_DATA_NOT_FOUND => '数据不存在',
        self::ERROR_CONSTRAINT_VIOLATION => '数据完整性约束违反'
    ];
    
    public function __construct($error_code, $query = '', Exception $previous = null)
    {
        $message = self::$error_messages[$error_code] ?? '未知数据库错误';
        $full_message = $query ? "{$message} - Query: {$query}" : $message;
        
        parent::__construct($full_message, $error_code, $message, $previous);
    }
}

/**
 * 权限异常
 */
class DHS_Tuku_Permission_Exception extends DHS_Tuku_Exception
{
    const ERROR_NOT_LOGGED_IN = 3001;
    const ERROR_INSUFFICIENT_PERMISSIONS = 3002;
    const ERROR_ACCESS_DENIED = 3003;
    const ERROR_RESOURCE_NOT_OWNED = 3004;
    
    protected static $error_messages = [
        self::ERROR_NOT_LOGGED_IN => '请先登录',
        self::ERROR_INSUFFICIENT_PERMISSIONS => '权限不足',
        self::ERROR_ACCESS_DENIED => '访问被拒绝',
        self::ERROR_RESOURCE_NOT_OWNED => '您没有权限操作此资源'
    ];
    
    public function __construct($error_code, $resource = '', Exception $previous = null)
    {
        $message = self::$error_messages[$error_code] ?? '未知权限错误';
        $full_message = $resource ? "{$message}: {$resource}" : $message;
        
        parent::__construct($full_message, $error_code, $message, $previous);
    }
}

/**
 * 验证异常
 */
class DHS_Tuku_Validation_Exception extends DHS_Tuku_Exception
{
    const ERROR_REQUIRED_FIELD = 4001;
    const ERROR_INVALID_FORMAT = 4002;
    const ERROR_VALUE_TOO_LONG = 4003;
    const ERROR_VALUE_TOO_SHORT = 4004;
    const ERROR_INVALID_CHARACTERS = 4005;
    
    protected static $error_messages = [
        self::ERROR_REQUIRED_FIELD => '必填字段不能为空',
        self::ERROR_INVALID_FORMAT => '格式不正确',
        self::ERROR_VALUE_TOO_LONG => '值太长',
        self::ERROR_VALUE_TOO_SHORT => '值太短',
        self::ERROR_INVALID_CHARACTERS => '包含非法字符'
    ];
    
    private $field_name;
    
    public function __construct($error_code, $field_name = '', $details = '', Exception $previous = null)
    {
        $this->field_name = $field_name;
        
        $message = self::$error_messages[$error_code] ?? '验证失败';
        $full_message = $field_name ? "{$field_name}: {$message}" : $message;
        
        if ($details) {
            $full_message .= " ({$details})";
        }
        
        parent::__construct($full_message, $error_code, $message, $previous);
    }
    
    public function getFieldName()
    {
        return $this->field_name;
    }
}

/**
 * 异常处理器
 */
class DHS_Tuku_Exception_Handler
{
    /**
     * 处理异常并返回适当的响应
     */
    public static function handle_exception(Exception $e)
    {
        // 记录异常
        if ($e instanceof DHS_Tuku_Exception) {
            $e->logException();
        } else {
            DHS_Tuku_Logger::error('Unhandled exception: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // 根据异常类型返回不同的响应
        if (wp_doing_ajax()) {
            self::handle_ajax_exception($e);
        } else {
            self::handle_web_exception($e);
        }
    }
    
    /**
     * 处理AJAX异常
     */
    private static function handle_ajax_exception(Exception $e)
    {
        $response = [
            'success' => false,
            'message' => self::get_user_friendly_message($e)
        ];
        
        // 在调试模式下包含更多信息
        if (WP_DEBUG) {
            $response['debug'] = [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
        
        wp_send_json_error($response);
    }
    
    /**
     * 处理Web异常
     */
    private static function handle_web_exception(Exception $e)
    {
        $message = self::get_user_friendly_message($e);
        
        // 显示错误消息
        add_action('wp_footer', function() use ($message) {
            echo '<div class="dhs-tuku-error-notice" style="
                position: fixed; 
                top: 20px; 
                right: 20px; 
                background: #dc3545; 
                color: white; 
                padding: 15px; 
                border-radius: 5px; 
                z-index: 9999;
                max-width: 300px;
            ">';
            echo '<strong>错误:</strong> ' . esc_html($message);
            echo '<button onclick="this.parentElement.remove()" style="
                float: right; 
                background: none; 
                border: none; 
                color: white; 
                font-size: 18px; 
                cursor: pointer;
                margin-left: 10px;
            ">×</button>';
            echo '</div>';
        });
    }
    
    /**
     * 获取用户友好的错误消息
     */
    private static function get_user_friendly_message(Exception $e)
    {
        if ($e instanceof DHS_Tuku_Exception) {
            return $e->getUserMessage();
        }
        
        // 对于非DHS异常，提供通用消息
        return __('操作失败，请稍后重试', 'dhs-tuku');
    }
    
    /**
     * 包装可能抛出异常的操作
     */
    public static function safe_execute(callable $callback, $default_return = null)
    {
        try {
            return $callback();
        } catch (Exception $e) {
            self::handle_exception($e);
            return $default_return;
        }
    }
    
    /**
     * 检查并抛出权限异常
     */
    public static function check_permission($condition, $error_code = DHS_Tuku_Permission_Exception::ERROR_ACCESS_DENIED, $resource = '')
    {
        if (!$condition) {
            throw new DHS_Tuku_Permission_Exception($error_code, $resource);
        }
    }
    
    /**
     * 检查并抛出验证异常
     */
    public static function validate($condition, $error_code, $field_name = '', $details = '')
    {
        if (!$condition) {
            throw new DHS_Tuku_Validation_Exception($error_code, $field_name, $details);
        }
    }
}
