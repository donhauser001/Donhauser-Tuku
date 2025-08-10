<?php
/**
 * 相册相关的AJAX处理函数
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取相册列表的 AJAX 处理函数
function dhs_get_album_list()
{
    if (!check_ajax_referer('dhs_nonce', '_wpnonce', false)) {
        wp_send_json_error('Nonce verification failed');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'dhs_gallery_albums';
    $albums = $wpdb->get_results("SELECT id, album_name FROM $table_name");

    if ($wpdb->last_error) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
        wp_die();
    }

    if (!empty($albums)) {
        wp_send_json_success($albums);
    } else {
        wp_send_json_error('No albums found');
    }

    wp_die();
}
add_action('wp_ajax_dhs_get_album_list', 'dhs_get_album_list');
add_action('wp_ajax_nopriv_dhs_get_album_list', 'dhs_get_album_list');

// 创建相册的 AJAX 处理函数
function dhs_create_album()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    global $wpdb;
    $album_name = sanitize_text_field($_POST['album']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

    if (empty($album_name) || empty($category_id)) {
        wp_send_json_error('相册名称和分类是必需的');
        wp_die();
    }

    // 插入相册数据
    $result = $wpdb->insert($wpdb->prefix . 'dhs_gallery_albums', [
        'album_name' => $album_name,
        'category_id' => $category_id,
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    ]);

    if ($result) {
        wp_send_json_success(['album_id' => $wpdb->insert_id]);
    } else {
        wp_send_json_error('创建相册失败，错误信息: ' . $wpdb->last_error);
    }

    wp_die();
}
add_action('wp_ajax_dhs_create_album', 'dhs_create_album');

// 删除相册的 AJAX 处理函数
function delete_album_callback()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    $album_id = intval($_POST['album_id']);
    if (!$album_id) {
        wp_send_json_error('无效的相册ID');
        wp_die();
    }

    global $wpdb;
    $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
    $images_table = $wpdb->prefix . 'dhs_gallery_images';

    // 开始事务
    $wpdb->query('START TRANSACTION');

    try {
        // 获取相册信息
        $album = $wpdb->get_row($wpdb->prepare("SELECT * FROM $albums_table WHERE id = %d", $album_id));
        if (!$album) {
            throw new Exception('相册不存在');
        }

        // 删除相册中的所有图片记录
        $deleted_images = $wpdb->delete($images_table, ['album_id' => $album_id]);
        if ($deleted_images === false) {
            throw new Exception('删除图片记录失败: ' . $wpdb->last_error);
        }

        // 删除相册记录
        $deleted_album = $wpdb->delete($albums_table, ['id' => $album_id]);
        if ($deleted_album === false) {
            throw new Exception('删除相册记录失败: ' . $wpdb->last_error);
        }

        // 删除相册文件夹
        $album_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id;
        if (is_dir($album_path)) {
            delete_directory($album_path);
        }

        // 提交事务
        $wpdb->query('COMMIT');
        wp_send_json_success('相册删除成功');

    } catch (Exception $e) {
        // 回滚事务
        $wpdb->query('ROLLBACK');
        wp_send_json_error('删除相册失败: ' . $e->getMessage());
    }

    wp_die();
}
add_action('wp_ajax_delete_album', 'delete_album_callback');

// 获取相册详情的 AJAX 处理函数
function get_album_details_callback()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    $album_id = intval($_POST['album_id']);
    if (!$album_id) {
        wp_send_json_error('无效的相册ID');
        wp_die();
    }

    global $wpdb;
    $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
    $categories_table = $wpdb->prefix . 'dhs_gallery_categories';

    $album = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, c.category_name 
         FROM $albums_table a 
         LEFT JOIN $categories_table c ON a.category_id = c.id 
         WHERE a.id = %d",
        $album_id
    ));

    if ($album) {
        wp_send_json_success($album);
    } else {
        wp_send_json_error('相册不存在');
    }

    wp_die();
}
add_action('wp_ajax_get_album_details', 'get_album_details_callback');

// 更新相册设置的 AJAX 处理函数
function update_album_settings()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    $album_id = intval($_POST['album_id']);
    $album_name = sanitize_text_field($_POST['album_name']);
    $category_id = intval($_POST['category_id']);

    if (!$album_id || empty($album_name)) {
        wp_send_json_error('相册ID和名称是必需的');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'dhs_gallery_albums';

    $result = $wpdb->update(
        $table_name,
        [
            'album_name' => $album_name,
            'category_id' => $category_id,
            'updated_at' => current_time('mysql')
        ],
        ['id' => $album_id],
        ['%s', '%d', '%s'],
        ['%d']
    );

    if ($result !== false) {
        wp_send_json_success('相册设置更新成功');
    } else {
        wp_send_json_error('更新相册设置失败: ' . $wpdb->last_error);
    }

    wp_die();
}
add_action('wp_ajax_update_album_settings', 'update_album_settings');

// 更新相册封面的 AJAX 处理函数（与原逻辑一致，接收封面路径）
function update_album_cover_callback()
{
    // 检查用户权限
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }

    // 验证 nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        return;
    }

    // 获取提交的数据
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    $cover_image = isset($_POST['cover_image']) ? $_POST['cover_image'] : '';

    if ($album_id === 0 || empty($cover_image)) {
        wp_send_json_error('无效的相册ID或封面图片路径');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'dhs_gallery_albums';

    // 更新数据库时使用原始的文件名，不进行不必要的过滤
    $result = $wpdb->update(
        $table_name,
        array('cover_image' => esc_sql($cover_image)),
        array('id' => $album_id),
        array('%s'),
        array('%d')
    );

    if ($result !== false) {
        wp_send_json_success('相册封面已更新');
    } else {
        error_log('数据库更新失败：' . $wpdb->last_error);
        wp_send_json_error('更新数据库时发生错误');
    }
}
add_action('wp_ajax_update_album_cover', 'update_album_cover_callback');

// 删除相册条目的 AJAX 处理函数
function delete_album_entries()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    $album_id = intval($_POST['album_id']);
    if (!$album_id) {
        wp_send_json_error('无效的相册ID');
        wp_die();
    }

    global $wpdb;
    $images_table = $wpdb->prefix . 'dhs_gallery_images';

    // 删除相册中的所有图片记录
    $result = $wpdb->delete($images_table, ['album_id' => $album_id]);

    if ($result !== false) {
        wp_send_json_success('相册条目删除成功');
    } else {
        wp_send_json_error('删除相册条目失败: ' . $wpdb->last_error);
    }

    wp_die();
}
add_action('wp_ajax_delete_album_entries', 'delete_album_entries');
