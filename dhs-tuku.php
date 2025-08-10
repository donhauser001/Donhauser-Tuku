<?php
/*
Plugin Name: Donhauser-Tuku
Description: 一个为设计师提供图片和相册管理的图库插件，支持AI智能标签生成。
Version: 1.2.0
Author: aiden
Text Domain: dhs-tuku
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // 防止直接访问
}

// 定义插件常量
define('DHS_TUKU_VERSION', '1.2.0');
define('DHS_TUKU_PLUGIN_FILE', __FILE__);
define('DHS_TUKU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DHS_TUKU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DHS_TUKU_ASSETS_URL', DHS_TUKU_PLUGIN_URL . 'assets/');

// 自动加载插件类
spl_autoload_register(function ($class) {
    if (strpos($class, 'DHS_Tuku') !== false) {
        $class_file = 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';
        $file_path = DHS_TUKU_PLUGIN_DIR . 'includes/' . $class_file;
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

// 主插件类
class DHS_Tuku_Main
{
    private static $instance = null;
    
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        register_activation_hook(DHS_TUKU_PLUGIN_FILE, ['DHS_Tuku_Init', 'activate']);
        register_deactivation_hook(DHS_TUKU_PLUGIN_FILE, ['DHS_Tuku_Init', 'deactivate']);
    }
    
    public function init()
    {
        // 检查WordPress版本
        if (!$this->check_requirements()) {
            return;
        }
        
        // 初始化核心组件
        DHS_Tuku_Init::init();
        DHS_Tuku_Shortcodes::init();
        DHS_Tuku_Assets::init();
        // 加载管理界面
        if (is_admin()) {
            DHS_Tuku_Admin::init();
        }
    }
    
    public function load_textdomain()
    {
        load_plugin_textdomain('dhs-tuku', false, dirname(plugin_basename(DHS_TUKU_PLUGIN_FILE)) . '/languages');
    }
    
    private function check_requirements()
    {
        global $wp_version;
        
        if (version_compare($wp_version, '5.0', '<')) {
            add_action('admin_notices', [$this, 'wp_version_notice']);
            return false;
        }
        
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', [$this, 'php_version_notice']);
            return false;
        }
        
        return true;
    }
    
    public function wp_version_notice()
    {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('DHS图库插件需要WordPress 5.0或更高版本。', 'dhs-tuku') . 
             '</p></div>';
    }
    
    public function php_version_notice()
    {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('DHS图库插件需要PHP 7.4或更高版本。', 'dhs-tuku') . 
             '</p></div>';
    }
}

// 启动插件
DHS_Tuku_Main::get_instance();

// 使用新的 AJAX 模块加载器
require_once DHS_TUKU_PLUGIN_DIR . 'includes/ajax-loader.php';

// 菜单函数 - 向后兼容
function dhs_tuku_menu()
{
    ob_start();
    include DHS_TUKU_PLUGIN_DIR . 'templates/menu.php';
    return ob_get_clean();
}

// 获取安全的URL（自动检测HTTPS）
function dhs_tuku_get_secure_url($path)
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    // 确保路径以 / 开头
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    return $protocol . '://' . $host . $path;
}

// 禁用 Gravatar，避免网络连接问题
function dhs_tuku_disable_gravatar($avatar, $id_or_email, $size, $default, $alt) {
    // 使用简单的默认头像
    $default_avatar = DHS_TUKU_PLUGIN_URL . 'assets/images/default-avatar.png';
    
    // 如果默认头像文件不存在，创建一个简单的SVG头像
    if (!file_exists(DHS_TUKU_PLUGIN_DIR . 'assets/images/default-avatar.png')) {
        $svg_avatar = "data:image/svg+xml;base64," . base64_encode('
        <svg width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="50" fill="#f0f0f0"/>
            <circle cx="50" cy="40" r="15" fill="#ccc"/>
            <ellipse cx="50" cy="75" rx="25" ry="15" fill="#ccc"/>
        </svg>');
        return '<img src="' . $svg_avatar . '" alt="' . $alt . '" width="' . $size . '" height="' . $size . '" class="avatar avatar-' . $size . ' photo" />';
    }
    
    return '<img src="' . $default_avatar . '" alt="' . $alt . '" width="' . $size . '" height="' . $size . '" class="avatar avatar-' . $size . ' photo" />';
}

// 全局禁用 Gravatar，避免网络连接问题
function dhs_tuku_init_disable_gravatar() {
    // 在所有页面禁用 Gravatar，避免网络连接错误
    add_filter('get_avatar', 'dhs_tuku_disable_gravatar', 10, 5);
    
    // 通过 WordPress 选项来禁用头像显示
    add_filter('pre_option_show_avatars', '__return_zero');
    
    // 阻止 Gravatar URL 的生成
    add_filter('get_avatar_url', 'dhs_tuku_replace_gravatar_url', 10, 3);
    
    // 完全禁用头像
    add_filter('avatar_defaults', 'dhs_tuku_remove_avatar_defaults');
}
add_action('init', 'dhs_tuku_init_disable_gravatar');

// 在头部添加CSS隐藏头像
function dhs_tuku_hide_avatars_css() {
    echo '<style type="text/css">
        .avatar, .comment-author img, .author-avatar img {
            display: none !important;
        }
        /* 替换为简单的用户图标 */
        .comment-author::before,
        .author-avatar::before {
            content: "👤";
            font-size: 24px;
            color: #ccc;
        }
    </style>';
}
add_action('wp_head', 'dhs_tuku_hide_avatars_css');
add_action('admin_head', 'dhs_tuku_hide_avatars_css');

// 阻止对 Gravatar 域名的 HTTP 请求
function dhs_tuku_block_gravatar_requests($pre, $parsed_args, $url) {
    // 检查是否是 Gravatar 请求
    if (strpos($url, 'gravatar.com') !== false || strpos($url, 'secure.gravatar.com') !== false) {
        // 返回一个空的响应，阻止实际的网络请求
        return new WP_Error('blocked_gravatar', 'Gravatar requests blocked to prevent connection errors');
    }
    return $pre;
}
add_filter('pre_http_request', 'dhs_tuku_block_gravatar_requests', 10, 3);

// 替换 Gravatar URL
function dhs_tuku_replace_gravatar_url($url, $id_or_email, $args) {
    // 返回本地默认头像或数据URI
    $svg_avatar = "data:image/svg+xml;base64," . base64_encode('
    <svg width="96" height="96" viewBox="0 0 96 96" xmlns="http://www.w3.org/2000/svg">
        <rect width="96" height="96" fill="#f0f0f0"/>
        <circle cx="48" cy="35" r="12" fill="#ccc"/>
        <ellipse cx="48" cy="70" rx="20" ry="12" fill="#ccc"/>
    </svg>');
    return $svg_avatar;
}

// 移除默认头像选项
function dhs_tuku_remove_avatar_defaults($defaults) {
    return array();
}
