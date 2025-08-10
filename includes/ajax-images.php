<?php
/**
 * 图片相关的AJAX处理函数
 */

if (!defined('ABSPATH')) {
    exit;
}

// 提交文件上传
function dhs_submit_file_handler()
{
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'dhs_nonce')) {
        wp_send_json_error('安全验证失败');
        return;
    }
    if (!current_user_can('upload_files')) {
        wp_send_json_error('权限不足');
        return;
    }

    global $wpdb;

    if (!empty($_FILES['files'])) {
        $files = $_FILES['files'];
        $album_id = isset($_POST['album']) ? intval($_POST['album']) : 0;
        $group_name = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : '';

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'psd', 'ai', 'svg'];

        $upload_dir = wp_upload_dir();
        $tuku_dir = $upload_dir['basedir'] . '/tuku/' . $album_id;

        if (!file_exists($tuku_dir)) {
            wp_mkdir_p($tuku_dir);
        }

        $file_data = [];
        $name = '';
        foreach ($files['name'] as $index => $file_name) {
            $file_tmp = $files['tmp_name'][$index];
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);
            $file_base_name = str_replace([' ', '#'], ['_', ''], $file_base_name);
            $timestamp = time();
            $name = $file_base_name . '_' . $timestamp;
            $new_file_name = $name . '.' . $file_extension;
            $target_file = $tuku_dir . '/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $file_data[] = [
                    'file_path' => '/wp-content/uploads/tuku/' . $album_id . '/' . $new_file_name,
                    'file_type' => $file_extension,
                    'upload_date' => current_time('mysql')
                ];
            } else {
                wp_send_json_error('文件移动失败: ' . $file_name);
                return;
            }
        }

        if (!empty($file_data)) {
            $encoded_file_data = wp_json_encode($file_data);
            $result = $wpdb->insert(
                $wpdb->prefix . 'dhs_gallery_images',
                [
                    'name' => $name,
                    'file_data' => $encoded_file_data,
                    'album_id' => $album_id,
                    'status' => 'active',
                ]
            );

            if ($result) {
                wp_send_json_success('文件上传成功');
            } else {
                wp_send_json_error('数据库插入失败: ' . $wpdb->last_error);
            }
        } else {
            wp_send_json_error('文件上传失败');
        }
    } else {
        wp_send_json_error('没有文件上传');
    }
    wp_die();
}
add_action('wp_ajax_dhs_submit_file', 'dhs_submit_file_handler');
add_action('wp_ajax_nopriv_dhs_submit_file', 'dhs_submit_file_handler');

// 获取图片详情
function get_image_details_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    if ($image_id === 0) {
        wp_send_json_error(array('message' => '无效的 image_id'));
        return;
    }
    global $wpdb;
    $images_table = $wpdb->prefix . 'dhs_gallery_images';
    $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
    $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
    $image_tag_table = $wpdb->prefix . 'dhs_gallery_image_tag';

    $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$images_table} WHERE id = %d", $image_id));
    if ($image) {
        $tags = $wpdb->get_results($wpdb->prepare(
            "SELECT t.tag_name FROM {$tags_table} t INNER JOIN {$image_tag_table} it ON t.id = it.tag_id WHERE it.image_id = %d",
            $image_id
        ));
        $image_tags = [];
        if ($tags) { foreach ($tags as $tag) { $image_tags[] = esc_html($tag->tag_name); } }
        $album_id = $image->album_id;
        $album = $wpdb->get_row($wpdb->prepare("SELECT album_name FROM {$albums_table} WHERE id = %d", $album_id));
        $category = $album ? $album->album_name : '未分类';
        $album_name = $album ? $album->album_name : '未知相册';
        $file_data = json_decode($image->file_data, true);
        $upload_time = isset($file_data[0]['upload_date']) ? $file_data[0]['upload_date'] : '';
        $image_name = $image->name;
        $image_url = site_url('/wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . rawurlencode($image_name) . '_thumbnail.jpg');
        $image_data = array(
            'image_id' => esc_html($image_id),
            'image_url' => esc_url($image_url),
            'file_name' => esc_html($image_name),
            'upload_time' => esc_html($upload_time),
            'category' => esc_html($category),
            'album_name' => esc_html($album_name),
            'album_id' => esc_html($album_id),
            'tags' => $image_tags
        );
        $buttons = [];
        if (!empty($file_data)) {
            foreach ($file_data as $file) {
                $file_type = strtoupper($file['file_type']);
                $file_url = site_url() . $file['file_path'];
                $buttons[] = array('type' => $file_type, 'url' => esc_url($file_url));
            }
        }
        if (empty($image_tags)) {
            $buttons[] = array('type' => '生成标签', 'url' => '#', 'action' => 'generate_tags');
        }
        $image_data['buttons'] = $buttons;
        wp_send_json_success($image_data);
    } else {
        wp_send_json_error(array('message' => '未找到图片'));
    }
}
add_action('wp_ajax_get_image_details', 'get_image_details_callback');
add_action('wp_ajax_nopriv_get_image_details', 'get_image_details_callback');

// 生成缩略图
add_action('wp_ajax_generate_thumbnails', 'generate_thumbnails_callback');
function generate_thumbnails_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }
    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => '权限不足']);
        return;
    }
    if (!isset($_POST['album_id'])) {
        wp_send_json_error(['message' => '缺少相册 ID']);
        return;
    }
    if (!extension_loaded('imagick') && !extension_loaded('gd')) {
        wp_send_json_error(['message' => '服务器缺少图像处理扩展（Imagick或GD）']);
        return;
    }

    global $wpdb;
    $album_id = intval($_POST['album_id']);
    $album_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id;
    $thumbnails_path = $album_path . '/thumbnails';

    if (!file_exists($thumbnails_path)) {
        if (!mkdir($thumbnails_path, 0755, true)) {
            wp_send_json_error(['message' => '缩略图目录创建失败']);
            return;
        }
    }

    $image_entries = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d", $album_id));
    if (empty($image_entries)) {
        wp_send_json_error(['message' => '相册中没有找到数据库条目']);
        return;
    }

    $errors = [];
    $processed_count = 0;
    $total_count = count($image_entries);
    $image_extensions = ['jpg', 'jpeg', 'png', 'tif', 'tiff'];

    foreach ($image_entries as $entry) {
        $image_name = $entry->name;
        $image_found = false;

        foreach ($image_extensions as $ext) {
            $image_path = $album_path . '/' . $image_name . '.' . $ext;
            $thumbnail_path = $thumbnails_path . '/' . $image_name . '_thumbnail.jpg';

            if (file_exists($image_path)) {
                $image_found = true;
                if (file_exists($thumbnail_path)) {
                    $processed_count++;
                    break;
                }
                try {
                    if (extension_loaded('imagick')) {
                        $imagick = new Imagick($image_path);
                        if ($imagick->getImageFormat() === 'PNG' && $imagick->getImageAlphaChannel()) {
                            $background = new Imagick();
                            $background->newImage($imagick->getImageWidth(), $imagick->getImageHeight(), new ImagickPixel('white'));
                            $background->setImageFormat('png');
                            $background->compositeImage($imagick, Imagick::COMPOSITE_OVER, 0, 0);
                            $imagick = $background;
                        }
                        $imagick->resizeImage(800, 0, Imagick::FILTER_LANCZOS, 1);
                        $imagick->setImageFormat('jpeg');
                        $imagick->setImageCompressionQuality(60);
                        $imagick->writeImage($thumbnail_path);
                        $imagick->clear();
                        $imagick->destroy();
                    } else {
                        $image_info = getimagesize($image_path);
                        if ($image_info === false) {
                            throw new Exception('无法读取图片信息');
                        }
                        switch ($image_info[2]) {
                            case IMAGETYPE_JPEG:
                                $source_image = imagecreatefromjpeg($image_path);
                                break;
                            case IMAGETYPE_PNG:
                                $source_image = imagecreatefrompng($image_path);
                                break;
                            case IMAGETYPE_TIFF_II:
                            case IMAGETYPE_TIFF_MM:
                                throw new Exception('GD不支持TIFF格式');
                            default:
                                throw new Exception('不支持的图片格式');
                        }
                        if ($source_image === false) {
                            throw new Exception('无法创建图片资源');
                        }
                        $original_width = imagesx($source_image);
                        $original_height = imagesy($source_image);
                        $new_width = 800;
                        $new_height = ($original_height * $new_width) / $original_width;
                        $thumbnail = imagecreatetruecolor($new_width, $new_height);
                        if ($image_info[2] == IMAGETYPE_PNG) {
                            $white = imagecolorallocate($thumbnail, 255, 255, 255);
                            imagefill($thumbnail, 0, 0, $white);
                        }
                        imagecopyresampled($thumbnail, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
                        imagejpeg($thumbnail, $thumbnail_path, 60);
                        imagedestroy($source_image);
                        imagedestroy($thumbnail);
                    }
                    $processed_count++;
                } catch (Exception $e) {
                    $errors[] = $image_name;
                    error_log('缩略图生成失败: ' . $e->getMessage());
                }
                break;
            }
        }
        if (!$image_found) {
            error_log('未找到对应的文件: ' . $image_name);
            $errors[] = $image_name;
        }
        $progress = ($processed_count / $total_count) * 100;
        update_option('thumbnail_progress_' . $album_id, $progress);
    }

    delete_option('thumbnail_progress_' . $album_id);

    if (!empty($errors)) {
        wp_send_json_success(['message' => '缩略图生成完成，但有部分文件未能成功生成。','processed' => $processed_count,'errors' => $errors]);
    } else {
        wp_send_json_success(['message' => '所有缩略图已成功生成。','processed' => $processed_count]);
    }
}

function get_thumbnail_progress_callback()
{
    if (!isset($_GET['album_id'])) {
        wp_send_json_error(['message' => '缺少相册 ID']);
        return;
    }
    $album_id = intval($_GET['album_id']);
    $progress = get_option('thumbnail_progress_' . $album_id, 0);
    wp_send_json_success(['progress' => $progress]);
}
add_action('wp_ajax_get_thumbnail_progress', 'get_thumbnail_progress_callback');

// 关联额外文件
function associate_file_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }
    $file_name = isset($_POST['file_name']) ? sanitize_text_field($_POST['file_name']) : '';
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    if ($album_id === 0 || empty($file_name)) {
        wp_send_json_error(array('message' => '无效的 album_id 或文件名'));
        return;
    }
    if (isset($_FILES['file']) && !$_FILES['file']['error']) {
        $uploaded_file = $_FILES['file'];
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/tuku/' . $album_id;
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        $target_file = $target_dir . '/' . $file_name;
        if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            global $wpdb;
            $images_table = $wpdb->prefix . 'dhs_gallery_images';
            $image_id = intval($_POST['image_id']);
            $file_data = $wpdb->get_var($wpdb->prepare("SELECT file_data FROM {$images_table} WHERE id = %d", $image_id));
            $file_data = json_decode($file_data, true);
            $file_data[] = array(
                'file_path' => '/wp-content/uploads/tuku/' . $album_id . '/' . $file_name,
                'file_type' => pathinfo($file_name, PATHINFO_EXTENSION),
                'upload_date' => current_time('mysql')
            );
            $wpdb->update(
                $images_table,
                array('file_data' => wp_json_encode($file_data)),
                array('id' => $image_id),
                array('%s'),
                array('%d')
            );
            $file_url = $upload_dir['baseurl'] . '/tuku/' . $album_id . '/' . $file_name;
            wp_send_json_success(array('message' => '文件关联成功','file_url' => $file_url));
        } else {
            wp_send_json_error(array('message' => '文件移动失败'));
        }
    } else {
        wp_send_json_error(array('message' => '文件上传失败'));
    }
}
add_action('wp_ajax_associate_file', 'associate_file_callback');

// 检查指定文件是否存在
function check_file_exists_callback()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    $file_name = isset($_POST['file_name']) ? sanitize_text_field($_POST['file_name']) : '';
    if ($album_id === 0 || empty($file_name)) {
        wp_send_json_error(array('message' => '无效的 album_id 或文件名'));
        return;
    }
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/tuku/' . $album_id . '/' . $file_name;
    $file_exists = file_exists($file_path);
    wp_send_json_success(array('exists' => $file_exists));
}
add_action('wp_ajax_check_file_exists', 'check_file_exists_callback');
add_action('wp_ajax_nopriv_check_file_exists', 'check_file_exists_callback');

// 批量扫描目录并写入数据库
function process_files_and_insert_into_db()
{
    check_ajax_referer('dhs_nonce', '_wpnonce');
    global $wpdb;
    $album_id = intval($_POST['album_id']);
    $dir = ABSPATH . "wp-content/uploads/tuku/{$album_id}";
    if (!file_exists($dir) || !is_dir($dir)) {
        wp_send_json_error('相册目录不存在。');
        return;
    }
    $files = [];
    $handle = opendir($dir);
    if ($handle) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $file_info = pathinfo($file);
                $name = $file_info['filename'];
                $extension = isset($file_info['extension']) ? $file_info['extension'] : '';
                if (preg_match('/\.(DS_Store|Thumbs\.db)$/i', $file)) {
                    continue;
                }
                if (!isset($files[$name])) {
                    $files[$name] = [];
                }
                $files[$name][] = [
                    'file_path' => "/wp-content/uploads/tuku/{$album_id}/" . $file,
                    'file_type' => $extension,
                    'upload_date' => current_time('mysql')
                ];
            }
        }
        closedir($handle);
    }
    foreach ($files as $name => $file_group) {
        $filtered_group = array_filter($file_group, function ($file) {
            $file_info = pathinfo($file['file_path']);
            $basename = $file_info['basename'];
            return !preg_match('/^(\.DS_Store|Thumbs\.db)$/i', $basename);
        });
        if (empty($filtered_group)) {
            continue;
        }
        $file_data = json_encode($filtered_group);
        $name = pathinfo(reset($filtered_group)['file_path'], PATHINFO_FILENAME);
        $wpdb->insert(
            $wpdb->prefix . 'dhs_gallery_images',
            [
                'album_id' => $album_id,
                'name' => $name,
                'file_data' => $file_data,
                'status' => 'active'
            ],
            ['%d', '%s', '%s', '%s']
        );
    }
    wp_send_json_success('文件处理和数据库更新成功。');
}
add_action('wp_ajax_process_files_and_insert', 'process_files_and_insert_into_db');

// 删除图片（原图+缩略图+数据库）
function dhs_delete_images()
{
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }
    if (!isset($_POST['album_id']) || !isset($_POST['images'])) {
        wp_send_json_error(['message' => '缺少必要的参数']);
        return;
    }
    $album_id = sanitize_text_field($_POST['album_id']);
    $images = explode(',', sanitize_text_field($_POST['images']));
    $image_dir = ABSPATH . 'wp-content/uploads/tuku/' . $album_id . '/';
    $thumbnail_dir = $image_dir . 'thumbnails/';

    foreach ($images as $image_name) {
        foreach (glob($image_dir . $image_name . '.*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        foreach (glob($thumbnail_dir . $image_name . '_thumbnail.*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'dhs_gallery_images',
            ['name' => $image_name, 'album_id' => $album_id],
            ['%s', '%d']
        );
    }
    wp_send_json_success(['message' => '图片已成功删除']);
}
add_action('wp_ajax_delete_images', 'dhs_delete_images');
add_action('wp_ajax_nopriv_delete_images', 'dhs_delete_images');

// 分页加载图片
function load_more_images()
{
    if (!isset($_POST['album_id']) || !isset($_POST['offset'])) {
        wp_send_json_error(['message' => '缺少必要的参数']);
        wp_die();
    }
    $album_id = intval($_POST['album_id']);
    $offset = intval($_POST['offset']);
    $images_per_page = 20;
    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d LIMIT %d OFFSET %d",
        $album_id,
        $images_per_page,
        $offset
    );
    $images = $wpdb->get_results($query);
    if ($wpdb->last_error) {
        wp_send_json_error(['message' => '数据库查询错误']);
        wp_die();
    }
    if (empty($images)) {
        wp_send_json_error(['message' => '没有更多图片']);
        wp_die();
    }
    ob_start();
    foreach ($images as $image) {
        $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . $image->name . '_thumbnail.jpg';
        $thumbnail_url = site_url('/wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');
        if (!file_exists($thumbnail_path)) {
            $thumbnail_url = site_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg');
        }
        ?>
        <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>">
            <input type="checkbox" class="image-checkbox">
            <div class="dhs-image-icons">
                <a href="javascript:void(0)" class="favorite-icon" title="收藏">
                    <i class="fa fa-star"></i>
                </a>
                <div class="favorite-select-container">
                    <button class="favorite-dropdown-btn">新建收藏夹</button>
                    <div class="favorite-dropdown-content"></div>
                </div>
                <a href="javascript:void(0)" class="like-icon" title="喜欢">
                    <i class="fa fa-heart"></i>
                </a>
            </div>
            <a href="javascript:void(0)" class="chakanimage dhs-tuku-open-modal" data-modal="image:50" data-image-id="<?php echo esc_attr($image->id); ?>">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($image->name); ?>" />
            </a>
            <div class="progress-bar-container" style="display:none;">
                <div class="progress-bar"></div>
            </div>
        </div>
        <?php
    }
    $html = ob_get_clean();
    $has_more = count($images) === $images_per_page;
    wp_send_json_success(['html' => $html, 'has_more' => $has_more]);
}
add_action('wp_ajax_load_more_images', 'load_more_images');
add_action('wp_ajax_nopriv_load_more_images', 'load_more_images');

// 上传单个缩略图文件
function upload_thumbnail_image_callback()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }
    if (!check_ajax_referer('dhs_nonce', '_ajax_nonce', false)) {
        wp_send_json_error('Nonce 验证失败');
        return;
    }
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    $file_name = isset($_POST['file_name']) ? sanitize_file_name($_POST['file_name']) : '';
    if (!$album_id || !$file_name || !isset($_FILES['file'])) {
        wp_send_json_error('无效的请求');
        return;
    }
    $file = $_FILES['file'];
    $upload_dir = wp_upload_dir();
    $destination_path = $upload_dir['basedir'] . "/tuku/$album_id/thumbnails/";
    if (!file_exists($destination_path)) {
        if (!wp_mkdir_p($destination_path)) {
            wp_send_json_error('无法创建上传目录');
            return;
        }
    }
    $destination_file = $destination_path . $file_name;
    $image = null;
    switch ($file['type']) {
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        default:
            wp_send_json_error('不支持的图片类型');
            return;
    }
    if (!$image) {
        wp_send_json_error('图片处理失败');
        return;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width > 800 || $height > 800) {
        if ($width > $height) {
            $new_width = 800;
            $new_height = intval($height * (800 / $width));
        } else {
            $new_height = 800;
            $new_width = intval($width * (800 / $height));
        }
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $resized_image;
    }
    if (imagejpeg($image, $destination_file, 90)) {
        wp_send_json_success('文件上传成功');
    } else {
        wp_send_json_error('文件上传失败');
    }
    imagedestroy($image);
}
add_action('wp_ajax_upload_thumbnail_image', 'upload_thumbnail_image_callback');

// 追加时间戳到目录内文件
function append_timestamp_to_files($album_id)
{
    $dir = ABSPATH . "wp-content/uploads/tuku/{$album_id}";
    if (!file_exists($dir) || !is_dir($dir)) {
        return false;
    }
    $timestamp = time();
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $old_path = $dir . '/' . $file;
                if (is_file($old_path)) {
                    $file_info = pathinfo($file);
                    if (!preg_match('/_\d{10}$/', $file_info['filename'])) {
                        $new_filename = $file_info['filename'] . '_' . $timestamp . '.' . $file_info['extension'];
                        $new_path = $dir . '/' . $new_filename;
                        @rename($old_path, $new_path);
                    }
                }
            }
        }
        closedir($handle);
    }
    return true;
}
function handle_append_timestamp_request()
{
    check_ajax_referer('dhs_nonce', '_wpnonce');
    $album_id = intval($_POST['album_id']);
    $result = append_timestamp_to_files($album_id);
    if ($result) {
        wp_send_json_success('时间戳添加成功。');
    } else {
        wp_send_json_error('添加时间戳失败。');
    }
}
add_action('wp_ajax_append_timestamp_to_files', 'handle_append_timestamp_request');

// 删除相册缩略图文件夹
function delete_thumbnails_folder()
{
    check_ajax_referer('dhs_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
    }
    $album_id = intval($_POST['album_id']);
    $upload_dir = wp_upload_dir();
    $thumbnails_path = $upload_dir['basedir'] . "/tuku/{$album_id}/thumbnails";
    if (file_exists($thumbnails_path) && is_dir($thumbnails_path)) {
        $deleted = remove_directory($thumbnails_path);
        if ($deleted) {
            wp_send_json_success();
        } else {
            wp_send_json_error('无法删除缩略图文件夹');
        }
    } else {
        wp_send_json_error('文件夹不存在');
    }
}
add_action('wp_ajax_delete_thumbnails_folder', 'delete_thumbnails_folder');

// 递归删除目录（供缩略图删除使用）
if (!function_exists('remove_directory')) {
    function remove_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir) || is_link($dir)) {
            return @unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!remove_directory($dir . "/" . $item)) {
                @chmod($dir . "/" . $item, 0777);
                if (!remove_directory($dir . "/" . $item)) return false;
            }
        }
        return @rmdir($dir);
    }
}
