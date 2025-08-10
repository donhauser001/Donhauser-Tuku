<?php
/**
 * 收藏与喜欢相关的AJAX处理函数
 */

if (!defined('ABSPATH')) {
    exit;
}

function get_user_favorites()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }
    global $wpdb;
    $current_user_id = get_current_user_id();
    $favorites = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT f.id, f.name, GROUP_CONCAT(fi.image_id) AS associated_image_ids
             FROM {$wpdb->prefix}dhs_gallery_favorites f
             LEFT JOIN {$wpdb->prefix}dhs_gallery_favorite_images fi ON f.id = fi.favorite_id
             WHERE f.user_id = %d
             GROUP BY f.id",
            $current_user_id
        )
    );
    if (!empty($favorites)) {
        foreach ($favorites as $favorite) {
            $associated_image_ids = $favorite->associated_image_ids ?? '';
            $favorite->associated_image_ids = $associated_image_ids ? explode(',', $associated_image_ids) : [];
        }
        wp_send_json_success(['favorites' => $favorites]);
    } else {
        wp_send_json_success(['favorites' => []]);
    }
}
add_action('wp_ajax_get_user_favorites', 'get_user_favorites');

function update_favorite_selection_callback()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }
    global $wpdb;
    $current_user_id = get_current_user_id();
    $favorite_id = isset($_POST['favorite_id']) ? intval($_POST['favorite_id']) : 0;
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    $is_checked = isset($_POST['is_checked']) ? intval($_POST['is_checked']) : 0;
    $table_name_favorite_images = $wpdb->prefix . 'dhs_gallery_favorite_images';
    if ($favorite_id === 0 || $image_id === 0) {
        wp_send_json_error('无效的提交数据');
        return;
    }
    if ($is_checked) {
        $result = $wpdb->insert(
            $table_name_favorite_images,
            array(
                'favorite_id' => $favorite_id,
                'image_id' => $image_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );
        if ($result === false) {
            wp_send_json_error('更新数据库时发生错误：' . $wpdb->last_error);
        } else {
            wp_send_json_success('收藏夹选择已更新');
        }
    } else {
        $result = $wpdb->delete(
            $table_name_favorite_images,
            array('favorite_id' => $favorite_id, 'image_id' => $image_id),
            array('%d', '%d')
        );
        if ($result === false) {
            wp_send_json_error('更新数据库时发生错误：' . $wpdb->last_error);
        } else {
            wp_send_json_success('收藏夹选择已更新');
        }
    }
}
add_action('wp_ajax_update_favorite_selection', 'update_favorite_selection_callback');

function create_new_favorite_callback()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error('无效的安全校验');
        return;
    }
    $current_user_id = get_current_user_id();
    $favorite_name = isset($_POST['favorite_name']) ? sanitize_text_field($_POST['favorite_name']) : '';
    if (empty($favorite_name)) {
        wp_send_json_error('收藏夹名称不能为空');
        return;
    }
    global $wpdb;
    $favorites_table = $wpdb->prefix . 'dhs_gallery_favorites';
    $result = $wpdb->insert(
        $favorites_table,
        array(
            'name' => $favorite_name,
            'user_id' => $current_user_id,
            'is_public' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%d', '%d', '%s', '%s')
    );
    if ($result !== false) {
        $favorite_id = $wpdb->insert_id;
        wp_send_json_success(array('favorite_id' => $favorite_id));
    } else {
        wp_send_json_error('创建收藏夹时发生错误');
    }
}
add_action('wp_ajax_create_new_favorite', 'create_new_favorite_callback');

function dhs_delete_favorite_callback()
{
    check_ajax_referer('dhs_delete_favorite_nonce', '_ajax_nonce');
    global $wpdb;
    $favorite_id = intval($_POST['favorite_id']);
    $current_user_id = get_current_user_id();
    $favorites_table = $wpdb->prefix . 'dhs_gallery_favorites';
    $favorite = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$favorites_table} WHERE id = %d AND user_id = %d", $favorite_id, $current_user_id));
    if (!$favorite) {
        wp_send_json_error(['message' => '收藏夹不存在或您无权删除此收藏夹。']);
    }
    $deleted = $wpdb->delete($favorites_table, ['id' => $favorite_id], ['%d']);
    if ($deleted) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => '删除收藏夹失败。']);
    }
}
add_action('wp_ajax_dhs_delete_favorite', 'dhs_delete_favorite_callback');

function dhs_tuku_edit_favorite()
{
    check_ajax_referer('dhs_nonce', 'security');
    global $wpdb;
    $favorite_id = intval($_POST['favorite_id']);
    $favorite_name = sanitize_text_field($_POST['favorite_name']);
    $is_public = isset($_POST['favorite_private']) ? 0 : 1;
    $updated = $wpdb->update(
        $wpdb->prefix . 'dhs_gallery_favorites',
        [ 'name' => $favorite_name, 'is_public' => $is_public ],
        ['id' => $favorite_id],
        ['%s', '%d'],
        ['%d']
    );
    if ($updated !== false) {
        wp_send_json_success(['message' => '收藏夹已更新']);
    } else {
        wp_send_json_error(['message' => '更新失败']);
    }
}
add_action('wp_ajax_edit_favorite', 'dhs_tuku_edit_favorite');

function get_favorite_details_callback()
{
    check_ajax_referer('dhs_nonce', 'security');
    global $wpdb;
    $favorite_id = intval($_POST['favorite_id']);
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT name, is_public FROM {$wpdb->prefix}dhs_gallery_favorites WHERE id = %d",
        $favorite_id
    ), ARRAY_A);
    if ($result) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error(['message' => '无法找到收藏夹详情']);
    }
}
add_action('wp_ajax_get_favorite_details', 'get_favorite_details_callback');

function update_like_status_callback()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error('无效的安全校验');
        return;
    }
    global $wpdb;
    $current_user_id = get_current_user_id();
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    $is_liked = isset($_POST['is_liked']) ? intval($_POST['is_liked']) : 0;
    if ($image_id === 0) {
        wp_send_json_error('无效的图片 ID');
        return;
    }
    $table_name_likes = $wpdb->prefix . 'dhs_gallery_likes';
    if ($is_liked) {
        $existing_like = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name_likes WHERE user_id = %d AND image_id = %d",
            $current_user_id, $image_id
        ));
        if ($existing_like > 0) {
            wp_send_json_error('你已经喜欢过这张图片');
            return;
        }
        $result = $wpdb->insert(
            $table_name_likes,
            array('user_id' => $current_user_id,'image_id' => $image_id,'created_at' => current_time('mysql')),
            array('%d', '%d', '%s')
        );
        if ($result === false) {
            wp_send_json_error('更新数据库时发生错误：' . $wpdb->last_error);
        } else {
            wp_send_json_success('图片已喜欢');
        }
    } else {
        $result = $wpdb->delete($table_name_likes, array('user_id' => $current_user_id,'image_id' => $image_id), array('%d', '%d'));
        if ($result === false) {
            wp_send_json_error('更新数据库时发生错误：' . $wpdb->last_error);
        } else {
            wp_send_json_success('图片喜欢已取消');
        }
    }
}
add_action('wp_ajax_update_like_status', 'update_like_status_callback');

function check_like_status_callback()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }
    global $wpdb;
    $current_user_id = get_current_user_id();
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    if ($image_id === 0) {
        wp_send_json_error('无效的图片 ID');
        return;
    }
    $table_name_likes = $wpdb->prefix . 'dhs_gallery_likes';
    $is_liked = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name_likes WHERE user_id = %d AND image_id = %d",
        $current_user_id, $image_id
    ));
    wp_send_json_success(array('is_liked' => $is_liked > 0));
}
add_action('wp_ajax_check_like_status', 'check_like_status_callback');

function load_more_liked_images()
{
    if (!isset($_POST['user_id']) || !isset($_POST['offset'])) {
        wp_send_json_error(['message' => '缺少必要的参数']);
        wp_die();
    }
    $user_id = intval($_POST['user_id']);
    $offset = intval($_POST['offset']);
    $images_per_page = 20;
    global $wpdb;
    $likes_table = $wpdb->prefix . 'dhs_gallery_likes';
    $images_table = $wpdb->prefix . 'dhs_gallery_images';
    $query = $wpdb->prepare(
        "SELECT img.*, img.album_id FROM $images_table img
         JOIN $likes_table likes ON img.id = likes.image_id
         WHERE likes.user_id = %d
         LIMIT %d OFFSET %d",
        $user_id, $images_per_page, $offset
    );
    $images = $wpdb->get_results($query);
    if ($wpdb->last_error) {
        wp_send_json_error(['message' => '数据库查询错误']);
        wp_die();
    }
    if (empty($images)) {
        wp_send_json_error(['message' => '没有更多喜欢的图片']);
        wp_die();
    }
    ob_start();
    foreach ($images as $image) {
        $album_id = esc_attr($image->album_id);
        $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';
        $thumbnail_url = site_url('/wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');
        if (!file_exists($thumbnail_path)) {
            $thumbnail_url = site_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg');
        }
        ?>
        <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>">
            <div class="dhs-image-icons">
                <a href="javascript:void(0)" class="favorite-icon" title="收藏"><i class="fa fa-star"></i></a>
                <div class="favorite-select-container">
                    <button class="favorite-dropdown-btn">新建收藏夹</button>
                    <div class="favorite-dropdown-content"></div>
                </div>
                <a href="javascript:void(0)" class="like-icon" title="喜欢"><i class="fa fa-heart"></i></a>
            </div>
            <a href="javascript:void(0)" class="chakanimage dhs-tuku-open-modal" data-modal="image:50" data-image-id="<?php echo esc_attr($image->id); ?>">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($image->name); ?>" />
            </a>
        </div>
        <?php
    }
    $html = ob_get_clean();
    $has_more = count($images) === $images_per_page;
    wp_send_json_success(['html' => $html, 'has_more' => $has_more]);
}
add_action('wp_ajax_load_more_liked_images', 'load_more_liked_images');
add_action('wp_ajax_nopriv_load_more_liked_images', 'load_more_liked_images');

function check_favorite_public_status()
{
    check_ajax_referer('dhs_nonce', '_ajax_nonce');
    global $wpdb;
    $favorite_id = intval($_POST['favorite_id']);
    $table_name = $wpdb->prefix . 'dhs_gallery_favorites';
    $favorite = $wpdb->get_row($wpdb->prepare(
        "SELECT is_public, name FROM {$table_name} WHERE id = %d",
        $favorite_id
    ));
    if ($favorite) {
        wp_send_json_success(['is_public' => $favorite->is_public, 'favorite_name' => $favorite->name]);
    } else {
        wp_send_json_error(['message' => '收藏夹不存在或无法访问。']);
    }
}
add_action('wp_ajax_check_favorite_public_status', 'check_favorite_public_status');

function make_favorite_public_and_share()
{
    check_ajax_referer('dhs_nonce', '_ajax_nonce');
    global $wpdb;
    $favorite_id = intval($_POST['favorite_id']);
    $table_name = $wpdb->prefix . 'dhs_gallery_favorites';
    $updated = $wpdb->update($table_name, ['is_public' => 1], ['id' => $favorite_id], ['%d'], ['%d']);
    if ($updated !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => '无法更新收藏夹状态。']);
    }
}
add_action('wp_ajax_make_favorite_public_and_share', 'make_favorite_public_and_share');
