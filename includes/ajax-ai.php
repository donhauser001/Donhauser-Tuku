<?php
/**
 * AI 标签相关的AJAX处理函数
 */

if (!defined('ABSPATH')) {
    exit;
}

// 旧的“自动标签”（可能使用 DHS_Tuku_Auto_Tagger）
add_action('wp_ajax_generate_auto_tags', 'generate_auto_tags_callback');
function generate_auto_tags_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }
    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => '权限不足']);
        return;
    }
    if (!isset($_POST['image_id'])) {
        wp_send_json_error(['message' => '缺少图片ID']);
        return;
    }
    $image_id = intval($_POST['image_id']);
    global $wpdb;
    $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE id = %d", $image_id));
    if (!$image) {
        wp_send_json_error(['message' => '图片不存在']);
        return;
    }
    $album_name = $wpdb->get_var($wpdb->prepare("SELECT album_name FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d", $image->album_id));
    $image_path_base = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/' . $image->name;
    $extensions = ['jpg', 'jpeg', 'png', 'tif', 'tiff'];
    $actual_path = null;
    foreach ($extensions as $ext) {
        $test_path = $image_path_base . '.' . $ext;
        if (file_exists($test_path)) { $actual_path = $test_path; break; }
    }
    if (!$actual_path) {
        wp_send_json_error(['message' => '找不到图片文件']);
        return;
    }
    try {
        $auto_tagger = DHS_Tuku_Auto_Tagger::get_instance();
        $auto_tags = $auto_tagger->generate_auto_tags($actual_path, $image->name, $album_name);
        if (empty($auto_tags)) {
            wp_send_json_success(['message' => '未能提取到标签','tags' => []]);
            return;
        }
        $auto_confirm = isset($_POST['auto_confirm']) && $_POST['auto_confirm'] === 'true';
        $success = $auto_tagger->save_auto_tags($image_id, $auto_tags, $auto_confirm);
        if ($success) {
            wp_send_json_success(['message' => '自动标签生成成功','tags' => $auto_tags,'count' => count($auto_tags)]);
        } else {
            wp_send_json_error(['message' => '标签保存失败']);
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => '自动标签生成失败: ' . $e->getMessage()]);
    }
}

add_action('wp_ajax_batch_generate_auto_tags', 'batch_generate_auto_tags_callback');
function batch_generate_auto_tags_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }
    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => '权限不足']);
        return;
    }
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    $limit = isset($_POST['limit']) ? min(intval($_POST['limit']), 50) : 10;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    global $wpdb;
    if ($album_id > 0) {
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d ORDER BY id LIMIT %d OFFSET %d",
            $album_id,$limit,$offset
        ));
    } else {
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dhs_gallery_images ORDER BY id LIMIT %d OFFSET %d",
            $limit,$offset
        ));
    }
    if (empty($images)) {
        wp_send_json_success(['message' => '没有更多图片需要处理','processed' => 0,'has_more' => false]);
        return;
    }
    $processed_count = 0; $total_tags = 0; $errors = [];
    $auto_tagger = DHS_Tuku_Auto_Tagger::get_instance();
    foreach ($images as $image) {
        try {
            $album_name = $wpdb->get_var($wpdb->prepare("SELECT album_name FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d", $image->album_id));
            $base = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/' . $image->name;
            $extensions = ['jpg','jpeg','png','tif','tiff'];
            $actual_path = null; foreach ($extensions as $ext) { $p = $base . '.' . $ext; if (file_exists($p)) { $actual_path = $p; break; } }
            if (!$actual_path) { $errors[] = "图片文件不存在: {$image->name}"; continue; }
            $auto_tags = $auto_tagger->generate_auto_tags($actual_path, $image->name, $album_name);
            if (!empty($auto_tags)) {
                $success = $auto_tagger->save_auto_tags($image->id, $auto_tags, true);
                if ($success) { $total_tags += count($auto_tags); $processed_count++; }
                else { $errors[] = "标签保存失败: {$image->name}"; }
            } else { $processed_count++; }
        } catch (Exception $e) {
            $errors[] = "处理失败: {$image->name} - " . $e->getMessage();
        }
    }
    $total_images = $album_id > 0
        ? $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d", $album_id))
        : $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images");
    $has_more = ($offset + $limit) < $total_images;
    wp_send_json_success([
        'message' => "已处理 {$processed_count} 张图片，生成 {$total_tags} 个标签",
        'processed' => $processed_count,
        'total_tags' => $total_tags,
        'errors' => $errors,
        'has_more' => $has_more,
        'next_offset' => $offset + $limit
    ]);
}

// 新的 AI/降级标签生成
add_action('wp_ajax_generate_ai_tags', 'generate_ai_tags_callback');
add_action('wp_ajax_nopriv_generate_ai_tags', 'generate_ai_tags_callback');
function generate_ai_tags_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => '请先登录']);
        return;
    }
    if (!isset($_POST['image_id'])) {
        wp_send_json_error(['message' => '缺少图片ID']);
        return;
    }
    $image_id = intval($_POST['image_id']);
    global $wpdb;
    $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE id = %d", $image_id));
    if (!$image) { wp_send_json_error(['message' => '图片不存在']); return; }
    $album = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d", $image->album_id));
    if (!$album) { wp_send_json_error(['message' => '相册不存在']); return; }
    $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';
    if (!file_exists($thumbnail_path)) { wp_send_json_error(['message' => '缩略图不存在: ' . $image->name]); return; }
    $image_path = $thumbnail_path;
    try {
        $ai_tagger = new DHS_Tuku_AI_Tagger();
        $ai_tags = [];
        $is_ai_available = $ai_tagger->is_ai_service_available();
        if ($is_ai_available) {
            try { $ai_tags = $ai_tagger->generate_ai_tags($image_path, $image->name, $album->album_name); } catch (Exception $e) { $ai_tags = []; }
        }
        if (empty($ai_tags)) {
            $ai_tags = $ai_tagger->generate_fallback_tags($image_path, $image->name, $album->album_name);
            $fallback_used = true;
        } else { $fallback_used = false; }
        if (empty($ai_tags)) { wp_send_json_error(['message' => '标签生成失败，请重试']); return; }
        $saved_tags = [];
        foreach ($ai_tags as $tag_name) {
            if (empty(trim($tag_name))) { continue; }
            $existing_tag = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s", $tag_name));
            if ($existing_tag) { $tag_id = $existing_tag->id; }
            else {
                $insert_result = $wpdb->insert($wpdb->prefix . 'dhs_gallery_tags', ['tag_name' => $tag_name], ['%s']);
                if ($insert_result === false) { continue; }
                $tag_id = $wpdb->insert_id;
            }
            $existing_relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_image_tag WHERE image_id = %d AND tag_id = %d", $image_id, $tag_id));
            if (!$existing_relation) {
                $relation_result = $wpdb->insert($wpdb->prefix . 'dhs_gallery_image_tag', ['image_id' => $image_id, 'tag_id' => $tag_id], ['%d', '%d']);
                if ($relation_result !== false) { $saved_tags[] = $tag_name; }
            } else { $saved_tags[] = $tag_name; }
        }
        if (!empty($saved_tags)) {
            $message = $fallback_used ? '智能标签生成成功（使用基础算法）' : 'AI标签生成成功';
            wp_send_json_success(['message' => $message,'tags' => $saved_tags,'count' => count($saved_tags),'fallback_used' => $fallback_used]);
        } else { wp_send_json_error(['message' => '标签保存失败']); }
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'AI标签生成失败: ' . $e->getMessage()]);
    }
}

add_action('wp_ajax_batch_generate_ai_tags', 'batch_generate_ai_tags_callback');
add_action('wp_ajax_nopriv_batch_generate_ai_tags', 'batch_generate_ai_tags_callback');

// 获取 LM Studio 模型列表
add_action('wp_ajax_dhs_get_lmstudio_models', 'get_lmstudio_models_callback');
function get_lmstudio_models_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => '权限不足']);
        return;
    }

    // 获取 LM Studio API 地址
    $api_url = get_option('dhs_lmstudio_api_url', 'http://localhost:1234/v1/chat/completions');
    
    // 构建模型列表 URL
    $models_url = '';
    if (preg_match('#/v1/#', $api_url)) {
        $parts = preg_split('#/v1/#', $api_url, 2);
        $base = rtrim($parts[0], '/');
        $models_url = $base . '/v1/models';
    } else {
        $models_url = 'http://localhost:1234/v1/models';
    }

    // 发起请求获取模型列表
    $response = wp_remote_get($models_url, [
        'timeout' => 5,
        'sslverify' => false
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => '无法连接到 LM Studio: ' . $response->get_error_message()]);
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        wp_send_json_error(['message' => 'LM Studio 响应错误: HTTP ' . $response_code]);
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['data']) || !is_array($data['data'])) {
        wp_send_json_error(['message' => '无效的模型列表响应格式']);
        return;
    }

    $models = [];
    foreach ($data['data'] as $model) {
        if (isset($model['id'])) {
            $models[] = [
                'id' => $model['id'],
                'name' => isset($model['name']) ? $model['name'] : $model['id']
            ];
        }
    }

    if (empty($models)) {
        wp_send_json_error(['message' => '未找到可用模型']);
        return;
    }

    wp_send_json_success([
        'models' => $models,
        'count' => count($models)
    ]);
}
function batch_generate_ai_tags_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) { wp_send_json_error(['message' => '安全验证失败']); return; }
    if (!is_user_logged_in()) { wp_send_json_error(['message' => '请先登录']); return; }
    $album_id = intval($_POST['album_id']);
    $limit = isset($_POST['limit']) ? min(intval($_POST['limit']), 20) : 5;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    global $wpdb;
    $ai_tagger = new DHS_Tuku_AI_Tagger();
    $ai_service_available = $ai_tagger->is_ai_service_available();
    try {
        if ($album_id > 0) {
            $images = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d ORDER BY id LIMIT %d OFFSET %d", $album_id, $limit, $offset));
        } else {
            $images = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_images ORDER BY id LIMIT %d OFFSET %d", $limit, $offset));
        }
        $processed_count = 0; $total_tags_generated = 0; $errors = []; $fallback_count = 0;
        foreach ($images as $image) {
            try {
                $album = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d", $image->album_id));
                if (!$album) { $errors[] = "图片 {$image->name}: 相册不存在"; continue; }
                $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';
                if (!file_exists($thumbnail_path)) { $errors[] = "图片 {$image->name}: 缩略图不存在"; continue; }
                $image_path = $thumbnail_path;
                $ai_tags = [];
                if ($ai_service_available) {
                    try { $ai_tags = $ai_tagger->generate_ai_tags($image_path, $image->name, $album->album_name); } catch (Exception $e) { $ai_tags = []; }
                }
                if (empty($ai_tags)) { $ai_tags = $ai_tagger->generate_fallback_tags($image_path, $image->name, $album->album_name); $fallback_count++; }
                if (empty($ai_tags)) { $errors[] = "图片 {$image->name}: 标签生成失败"; continue; }
                $saved_count = 0;
                foreach ($ai_tags as $tag_name) {
                    if (empty(trim($tag_name))) continue;
                    $existing_tag = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s", $tag_name));
                    if ($existing_tag) { $tag_id = $existing_tag->id; }
                    else { $ins = $wpdb->insert($wpdb->prefix . 'dhs_gallery_tags', ['tag_name' => $tag_name], ['%s']); if ($ins === false) { continue; } $tag_id = $wpdb->insert_id; }
                    $existing_relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_image_tag WHERE image_id = %d AND tag_id = %d", $image->id, $tag_id));
                    if (!$existing_relation) {
                        $relation_result = $wpdb->insert($wpdb->prefix . 'dhs_gallery_image_tag', ['image_id' => $image->id, 'tag_id' => $tag_id], ['%d', '%d']);
                        if ($relation_result !== false) { $saved_count++; }
                    }
                }
                $total_tags_generated += $saved_count;
                $processed_count++;
            } catch (Exception $e) {
                $errors[] = "图片 {$image->name}: " . $e->getMessage();
            }
        }
        $next_offset = $offset + $limit;
        $total_count = $album_id > 0
            ? $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d", $album_id))
            : $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images");
        $has_more = $next_offset < $total_count;
        wp_send_json_success([
            'processed' => $processed_count,
            'total_tags' => $total_tags_generated,
            'errors' => $errors,
            'has_more' => $has_more,
            'next_offset' => $next_offset,
            'total_count' => $total_count,
            'fallback_count' => $fallback_count,
            'ai_available' => $ai_service_available
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => '批量AI标签生成失败: ' . $e->getMessage()]);
    }
}
