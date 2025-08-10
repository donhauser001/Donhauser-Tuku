<?php
/**
 * AJAX 模块加载器
 */

if (!defined('ABSPATH')) {
    exit;
}

$includes = [
    'ajax-utils.php',
    'ajax-albums.php',
    'ajax-categories.php',
    'ajax-images.php',
    'ajax-tags.php',
    'ajax-favorites.php',
    'ajax-ai.php',
];

foreach ($includes as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}
