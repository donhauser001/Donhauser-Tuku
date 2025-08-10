<?php
/**
 * 分类相关的AJAX处理函数
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取分类列表的 AJAX 处理函数
function dhs_get_category_list()
{
    check_ajax_referer('dhs_nonce', '_wpnonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'dhs_gallery_categories';
    $categories = $wpdb->get_results("SELECT id, category_name FROM $table_name");

    if (!empty($categories)) {
        wp_send_json_success($categories);
    } else {
        wp_send_json_error('No categories found');
    }

    wp_die();
}
add_action('wp_ajax_dhs_get_category_list', 'dhs_get_category_list');
add_action('wp_ajax_nopriv_dhs_get_category_list', 'dhs_get_category_list');

// 创建分类的 AJAX 处理函数
function dhs_create_category()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'dhs_gallery_categories';

    // 获取 POST 数据
    $category_name = sanitize_text_field($_POST['category_name']);
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    // 检查 parent_id 是否为有效的分类 ID，或者将其设为 NULL
    if ($parent_id === 0 || !$wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE id = %d", $parent_id))) {
        $parent_id = null;
    }

    // 验证分类名称
    if (empty($category_name)) {
        wp_send_json_error('分类名称不能为空');
        wp_die();
    }

    // 检查分类名称是否已存在
    $existing_category = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE category_name = %s",
        $category_name
    ));

    if ($existing_category) {
        wp_send_json_error('分类名称已存在');
        wp_die();
    }

    // 插入新分类
    $result = $wpdb->insert(
        $table_name,
        [
            'category_name' => $category_name,
            'parent_id' => $parent_id,
            'created_at' => current_time('mysql')
        ],
        ['%s', '%d', '%s']
    );

    if ($result) {
        wp_send_json_success([
            'message' => '分类创建成功',
            'category_id' => $wpdb->insert_id
        ]);
    } else {
        wp_send_json_error('创建分类失败: ' . $wpdb->last_error);
    }

    wp_die();
}
add_action('wp_ajax_dhs_create_category', 'dhs_create_category');

// 获取分类详情的 AJAX 处理函数（原 hook: get_category_details）
function dhs_get_category_details_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    $category_id = intval($_POST['category_id']);
    if (!$category_id) {
        wp_send_json_error('无效的分类ID');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'dhs_gallery_categories';

    $category = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $category_id
    ));

    if ($category) {
        wp_send_json_success($category);
    } else {
        wp_send_json_error('分类不存在');
    }

    wp_die();
}
add_action('wp_ajax_get_category_details', 'dhs_get_category_details_callback');

// 编辑分类的 AJAX 处理函数（原 hook: edit_category）
function dhs_edit_category_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    global $wpdb;
    $category_id = intval($_POST['category_id']);
    $category_name = sanitize_text_field($_POST['category_name']);
    $parent_category = intval($_POST['parent_category']) === 0 ? null : intval($_POST['parent_category']);

    $updated = $wpdb->update(
        $wpdb->prefix . 'dhs_gallery_categories',
        [
            'category_name' => $category_name,
            'parent_id' => $parent_category,
        ],
        ['id' => $category_id],
        ['%s', '%d'],
        ['%d']
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => '分类已更新']);
    } else {
        wp_send_json_error(['message' => '更新失败']);
    }
}
add_action('wp_ajax_edit_category', 'dhs_edit_category_callback');

// 删除分类的 AJAX 处理函数（原 hook: delete_category）
function dhs_delete_category_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error('Nonce 验证失败');
        wp_die();
    }

    global $wpdb;
    $category_id = intval($_POST['category_id']);

    $child_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_categories WHERE parent_id = %d",
        $category_id
    ));
    if ($child_count > 0) {
        wp_send_json_error(['message' => '无法删除：请先删除子分类']);
    }

    $deleted = $wpdb->delete($wpdb->prefix . 'dhs_gallery_categories', ['id' => $category_id], ['%d']);
    if ($deleted) {
        wp_send_json_success(['message' => '分类已删除']);
    } else {
        wp_send_json_error(['message' => '删除分类失败']);
    }
}
add_action('wp_ajax_delete_category', 'dhs_delete_category_callback');
