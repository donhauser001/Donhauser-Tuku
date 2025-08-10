<?php
/**
 * 标签相关的AJAX处理函数
 */

if (!defined('ABSPATH')) {
    exit;
}

function update_image_tags_callback()
{
    check_ajax_referer('dhs_nonce', '_ajax_nonce');
    $image_id = intval($_POST['image_id']);
    $new_tags = json_decode(stripslashes($_POST['tags']), true);
    if (!$image_id || empty($new_tags)) {
        wp_send_json_error(array('message' => '无效的图片ID或标签为空'));
        return;
    }
    global $wpdb;
    $current_tags = $wpdb->get_col($wpdb->prepare(
        "SELECT t.tag_name FROM {$wpdb->prefix}dhs_gallery_tags t
         INNER JOIN {$wpdb->prefix}dhs_gallery_image_tag it ON t.id = it.tag_id
         WHERE it.image_id = %d",
        $image_id
    ));
    $tags_to_delete = array_diff($current_tags, $new_tags);
    if (!empty($tags_to_delete)) {
        foreach ($tags_to_delete as $tag_name) {
            $tag_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
                $tag_name
            ));
            if ($tag_id) {
                $wpdb->delete("{$wpdb->prefix}dhs_gallery_image_tag", array('image_id' => $image_id, 'tag_id' => $tag_id), array('%d', '%d'));
            }
        }
    }
    $tags_to_add = array_diff($new_tags, $current_tags);
    foreach ($tags_to_add as $tag_name) {
        $existing_tag_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
            $tag_name
        ));
        if (!$existing_tag_id) {
            $wpdb->insert("{$wpdb->prefix}dhs_gallery_tags", array('tag_name' => $tag_name), array('%s'));
            $tag_id = $wpdb->insert_id;
        } else {
            $tag_id = $existing_tag_id;
        }
        $wpdb->insert(
            "{$wpdb->prefix}dhs_gallery_image_tag",
            array('image_id' => $image_id, 'tag_id' => $tag_id),
            array('%d', '%d')
        );
    }
    $updated_tags = $wpdb->get_col($wpdb->prepare(
        "SELECT t.tag_name FROM {$wpdb->prefix}dhs_gallery_tags t
         INNER JOIN {$wpdb->prefix}dhs_gallery_image_tag it ON t.id = it.tag_id
         WHERE it.image_id = %d",
        $image_id
    ));
    wp_send_json_success(array('tags' => $updated_tags));
}
add_action('wp_ajax_update_image_tags', 'update_image_tags_callback');

function delete_image_tag_callback()
{
    check_ajax_referer('dhs_nonce', '_ajax_nonce');
    $image_id = intval($_POST['image_id']);
    $tag_name = sanitize_text_field($_POST['tag']);
    if (!$image_id || empty($tag_name)) {
        wp_send_json_error(array('message' => '无效的图片ID或标签名为空'));
        return;
    }
    global $wpdb;
    $tag_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
        $tag_name
    ));
    if (!$tag_id) {
        wp_send_json_error(array('message' => '标签不存在'));
        return;
    }
    $result = $wpdb->delete(
        "{$wpdb->prefix}dhs_gallery_image_tag",
        array('image_id' => $image_id, 'tag_id' => $tag_id),
        array('%d', '%d')
    );
    if ($result === false) {
        wp_send_json_error(array('message' => '删除标签时出错'));
    } else {
        wp_send_json_success();
    }
}
add_action('wp_ajax_delete_image_tag', 'delete_image_tag_callback');

function apply_tag_to_all_callback()
{
    check_ajax_referer('dhs_nonce', '_ajax_nonce');
    $album_id = intval($_POST['album_id']);
    $tag_name = sanitize_text_field($_POST['tag']);
    if (!$album_id || empty($tag_name)) {
        wp_send_json_error(array('message' => '无效的相册ID或标签名为空'));
        return;
    }
    global $wpdb;
    $tag_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
        $tag_name
    ));
    if (!$tag_id) {
        $wpdb->insert("{$wpdb->prefix}dhs_gallery_tags", array('tag_name' => $tag_name), array('%s'));
        $tag_id = $wpdb->insert_id;
    }
    $image_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d",
        $album_id
    ));
    if (empty($image_ids)) {
        wp_send_json_error(array('message' => '该相册中没有图片'));
        return;
    }
    foreach ($image_ids as $image_id) {
        $existing_assoc = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_image_tag WHERE image_id = %d AND tag_id = %d",
            $image_id, $tag_id
        ));
        if (!$existing_assoc) {
            $wpdb->insert(
                "{$wpdb->prefix}dhs_gallery_image_tag",
                array('image_id' => $image_id, 'tag_id' => $tag_id),
                array('%d', '%d')
            );
        }
    }
    wp_send_json_success();
}
add_action('wp_ajax_apply_tag_to_all', 'apply_tag_to_all_callback');

// 标签管理 CRUD
function dhs_add_tag()
{
    if (!check_ajax_referer('dhs_nonce', 'nonce', false)) {
        wp_send_json_error('安全验证失败');
        wp_die();
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
        wp_die();
    }
    $tag_name = sanitize_text_field($_POST['tag_name']);
    if (empty($tag_name)) {
        wp_send_json_error('标签名称不能为空');
        wp_die();
    }
    if (strlen($tag_name) > 50) {
        wp_send_json_error('标签名称不能超过50个字符');
        wp_die();
    }
    global $wpdb;
    $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
    $existing_tag = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tags_table} WHERE LOWER(tag_name) = LOWER(%s)",
        $tag_name
    ));
    if ($existing_tag) {
        wp_send_json_error('标签已存在');
        wp_die();
    }
    $result = $wpdb->insert($tags_table, ['tag_name' => $tag_name], ['%s']);
    if ($result === false) {
        wp_send_json_error('添加标签失败：' . $wpdb->last_error);
        wp_die();
    }
    wp_send_json_success(['tag_id' => $wpdb->insert_id, 'tag_name' => $tag_name]);
}
add_action('wp_ajax_dhs_add_tag', 'dhs_add_tag');

function dhs_update_tag()
{
    if (!check_ajax_referer('dhs_nonce', 'nonce', false)) {
        wp_send_json_error('安全验证失败');
        wp_die();
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
        wp_die();
    }
    $tag_id = intval($_POST['tag_id']);
    $tag_name = sanitize_text_field($_POST['tag_name']);
    if (empty($tag_id) || empty($tag_name)) {
        wp_send_json_error('标签ID和名称不能为空');
        wp_die();
    }
    if (strlen($tag_name) > 50) {
        wp_send_json_error('标签名称不能超过50个字符');
        wp_die();
    }
    global $wpdb;
    $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
    $existing_tag = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tags_table} WHERE LOWER(tag_name) = LOWER(%s) AND id != %d",
        $tag_name,
        $tag_id
    ));
    if ($existing_tag) {
        wp_send_json_error('标签已存在');
        wp_die();
    }
    $result = $wpdb->update($tags_table, ['tag_name' => $tag_name], ['id' => $tag_id], ['%s'], ['%d']);
    if ($result === false) {
        wp_send_json_error('更新标签失败：' . $wpdb->last_error);
        wp_die();
    }
    wp_send_json_success(['tag_id' => $tag_id, 'tag_name' => $tag_name]);
}
add_action('wp_ajax_dhs_update_tag', 'dhs_update_tag');

function dhs_delete_tag()
{
    if (!check_ajax_referer('dhs_nonce', 'nonce', false)) {
        wp_send_json_error('安全验证失败');
        wp_die();
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
        wp_die();
    }
    $tag_id = intval($_POST['tag_id']);
    if (empty($tag_id)) {
        wp_send_json_error('标签ID不能为空');
        wp_die();
    }
    global $wpdb;
    $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
    $image_tag_table = $wpdb->prefix . 'dhs_gallery_image_tag';
    $usage_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$image_tag_table} WHERE tag_id = %d",
        $tag_id
    ));
    if ($usage_count > 0) {
        wp_send_json_error('该标签正在使用中，无法删除');
        wp_die();
    }
    $result = $wpdb->delete($tags_table, ['id' => $tag_id], ['%d']);
    if ($result === false) {
        wp_send_json_error('删除标签失败：' . $wpdb->last_error);
        wp_die();
    }
    wp_send_json_success(['tag_id' => $tag_id]);
}
add_action('wp_ajax_dhs_delete_tag', 'dhs_delete_tag');

function dhs_clear_all_tags()
{
    if (!check_ajax_referer('dhs_nonce', 'nonce', false)) {
        wp_send_json_error('安全验证失败');
        wp_die();
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
        wp_die();
    }
    global $wpdb;
    $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
    $image_tag_table = $wpdb->prefix . 'dhs_gallery_image_tag';
    $wpdb->query('START TRANSACTION');
    try {
        $delete_image_tags = $wpdb->query("DELETE FROM {$image_tag_table}");
        if ($delete_image_tags === false) {
            throw new Exception('删除图片标签关联失败：' . $wpdb->last_error);
        }
        $delete_tags = $wpdb->query("DELETE FROM {$tags_table}");
        if ($delete_tags === false) {
            throw new Exception('删除标签失败：' . $wpdb->last_error);
        }
        $wpdb->query('COMMIT');
        wp_send_json_success([
            'message' => '所有标签已成功清空',
            'deleted_tags' => $delete_tags,
            'deleted_image_tags' => $delete_image_tags
        ]);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error('清空标签失败：' . $e->getMessage());
    }
    wp_die();
}
add_action('wp_ajax_dhs_clear_all_tags', 'dhs_clear_all_tags');
