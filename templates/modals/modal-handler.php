<?php
// 包含 WordPress 核心文件或使用 filter_var
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

if (isset($_GET['modal'])) {
    // 使用 WordPress 函数或 PHP 内置函数清理输入
    $modal_name = sanitize_text_field($_GET['modal']); // 或使用 filter_var($_GET['modal'], FILTER_SANITIZE_STRING);

    // 正确拼接文件路径，不重复 'templates/modals/'
    $modal_file = plugin_dir_path(__FILE__) . $modal_name . '.php';


    if (file_exists($modal_file)) {
        include $modal_file;
    } else {
        echo 'Modal file not found at: ' . $modal_file;
    }
} else {
    echo 'Invalid request, modal parameter missing.';
}
