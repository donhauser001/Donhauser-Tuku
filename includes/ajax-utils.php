<?php
/**
 * 工具与通用相关函数
 */

if (!defined('ABSPATH')) {
    exit;
}

// 递归删除目录及其内容（通用）
if (!function_exists('delete_directory')) {
    function delete_directory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                delete_directory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

// 前端图片瀑布流脚本引入
function enqueue_masonry_script()
{
    wp_enqueue_script('masonry-js', 'https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js', array('jquery'), '4.2.2', true);
    wp_enqueue_script('imagesloaded-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/4.1.4/imagesloaded.pkgd.min.js', array('jquery'), '4.1.4', true);
    wp_add_inline_script('imagesloaded-js', <<<'JS'
        jQuery(document).ready(function($) {
            var $grid = $('#albumImagesGrid, #likedImagesGrid ,#searchResultsGrid');
            if ($grid.length) {
                $grid.imagesLoaded(function() {
                    $grid.masonry({
                        itemSelector: '.dhs-album-image-item',
                        percentPosition: true,
                        columnWidth: '.dhs-album-image-item',
                        gutter: 15
                    });
                });
            }
        });
JS
    );
}
add_action('wp_enqueue_scripts', 'enqueue_masonry_script');

// 用户密码验证（通用）
function verify_user_password()
{
    check_ajax_referer('dhs_nonce', '_wpnonce');
    $user_id = intval($_POST['user_id']);
    $password = sanitize_text_field($_POST['password']);
    $user = get_user_by('id', $user_id);
    if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('密码错误。');
    }
}
add_action('wp_ajax_verify_user_password', 'verify_user_password');
