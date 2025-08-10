<?php

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

    $created_at = current_time('mysql');

    // 插入分类数据
    $result = $wpdb->insert($table_name, [
        'category_name' => $category_name,
        'parent_id' => $parent_id,
        'created_at' => $created_at,
    ]);

    if ($result) {
        wp_send_json_success(['message' => '分类创建成功', 'category_id' => $wpdb->insert_id]);
    } else {
        wp_send_json_error(['message' => '创建分类失败，错误信息: ' . $wpdb->last_error]);
    }

    wp_die();
}

add_action('wp_ajax_dhs_create_category', 'dhs_create_category');

function dhs_submit_file_handler()
{
    // 验证nonce和用户权限
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

        // 验证文件类型
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'psd', 'ai', 'svg'];

        $upload_dir = wp_upload_dir();
        $tuku_dir = $upload_dir['basedir'] . '/tuku/' . $album_id;

        // 确保目录存在
        if (!file_exists($tuku_dir)) {
            wp_mkdir_p($tuku_dir);
        }

        $file_data = [];
        foreach ($files['name'] as $index => $file_name) {
            $file_tmp = $files['tmp_name'][$index];
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION); // 获取扩展名
            $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);  // 获取文件基础名

            // 处理文件名中的特殊字符
            $file_base_name = str_replace([' ', '#'], ['_', ''], $file_base_name); // 将空格替换为下划线并删除 #

            // 为文件名添加时间戳
            $timestamp = time();
            $name = $file_base_name . '_' . $timestamp;

            // 初始化目标文件路径
            $new_file_name = $name . '.' . $file_extension;
            $target_file = $tuku_dir . '/' . $new_file_name;

            // 移动上传的文件并重命名
            if (move_uploaded_file($file_tmp, $target_file)) {
                // 更新file_data中的文件路径，确保与name一致
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
            // 编码文件数据
            $encoded_file_data = wp_json_encode($file_data);

            // 插入数据库记录，name字段已确保唯一
            $result = $wpdb->insert(
                $wpdb->prefix . 'dhs_gallery_images',
                [
                    'name' => $name,  // 使用唯一的name（带有时间戳）
                    'file_data' => $encoded_file_data,  // 包含文件的实际路径
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


add_action('wp_ajax_generate_thumbnails', 'generate_thumbnails_callback');
function generate_thumbnails_callback()
{
    // 验证nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }

    // 检查用户权限
    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => '权限不足']);
        return;
    }

    if (!isset($_POST['album_id'])) {
        wp_send_json_error(['message' => '缺少相册 ID']);
        return;
    }

    // 检查图像处理扩展
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

    // 获取数据库中的图片条目
    $image_entries = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}dhs_gallery_images
        WHERE album_id = %d
    ", $album_id));

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
                        // 使用 Imagick
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
                        // 使用 GD 库作为备用
                        $image_info = getimagesize($image_path);
                        if ($image_info === false) {
                            throw new Exception('无法读取图片信息');
                        }

                        $source_image = null;
                        switch ($image_info[2]) {
                            case IMAGETYPE_JPEG:
                                $source_image = imagecreatefromjpeg($image_path);
                                break;
                            case IMAGETYPE_PNG:
                                $source_image = imagecreatefrompng($image_path);
                                break;
                            case IMAGETYPE_TIFF_II:
                            case IMAGETYPE_TIFF_MM:
                                // GD不支持TIFF，跳过
                                throw new Exception('GD不支持TIFF格式');
                                break;
                            default:
                                throw new Exception('不支持的图片格式');
                        }

                        if ($source_image === false) {
                            throw new Exception('无法创建图片资源');
                        }

                        $original_width = imagesx($source_image);
                        $original_height = imagesy($source_image);

                        // 计算新尺寸（宽度为800px）
                        $new_width = 800;
                        $new_height = ($original_height * $new_width) / $original_width;

                        // 创建新图片
                        $thumbnail = imagecreatetruecolor($new_width, $new_height);

                        // 如果是PNG，保持透明度
                        if ($image_info[2] == IMAGETYPE_PNG) {
                            $white = imagecolorallocate($thumbnail, 255, 255, 255);
                            imagefill($thumbnail, 0, 0, $white);
                        }

                        // 调整图片大小
                        imagecopyresampled($thumbnail, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

                        // 保存为JPEG
                        imagejpeg($thumbnail, $thumbnail_path, 60);

                        // 清理内存
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

        // 更新进度
        $progress = ($processed_count / $total_count) * 100;
        update_option('thumbnail_progress_' . $album_id, $progress);
    }

    // 清除进度
    delete_option('thumbnail_progress_' . $album_id);

    if (!empty($errors)) {
        wp_send_json_success([
            'message' => '缩略图生成完成，但有部分文件未能成功生成。',
            'processed' => $processed_count,
            'errors' => $errors
        ]);
    } else {
        wp_send_json_success([
            'message' => '所有缩略图已成功生成。',
            'processed' => $processed_count
        ]);
    }
}


function get_thumbnail_progress_callback()
{
    if (!isset($_GET['album_id'])) {
        wp_send_json_error(['message' => '缺少相册 ID']);
        return;
    }

    $album_id = intval($_GET['album_id']);
    $progress = get_option('thumbnail_progress_' . $album_id, 0); // 假设进度存储在选项中

    wp_send_json_success(['progress' => $progress]);
}

add_action('wp_ajax_get_thumbnail_progress', 'get_thumbnail_progress_callback');

// 自动标签生成
add_action('wp_ajax_generate_auto_tags', 'generate_auto_tags_callback');
function generate_auto_tags_callback()
{
    error_log('自动标签AJAX请求开始，POST数据: ' . print_r($_POST, true));

    // 验证nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        error_log('自动标签AJAX：nonce验证失败');
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }

    // 检查用户权限
    if (!current_user_can('upload_files')) {
        error_log('自动标签AJAX：用户权限不足');
        wp_send_json_error(['message' => '权限不足']);
        return;
    }

    if (!isset($_POST['image_id'])) {
        wp_send_json_error(['message' => '缺少图片ID']);
        return;
    }

    $image_id = intval($_POST['image_id']);

    global $wpdb;

    // 获取图片信息
    $image = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE id = %d",
        $image_id
    ));

    if (!$image) {
        wp_send_json_error(['message' => '图片不存在']);
        return;
    }

    // 获取相册名称
    $album_name = $wpdb->get_var($wpdb->prepare(
        "SELECT album_name FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d",
        $image->album_id
    ));

    // 构建图片路径
    $image_path = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/' . $image->name;

    // 查找实际文件（尝试不同扩展名）
    $extensions = ['jpg', 'jpeg', 'png', 'tif', 'tiff'];
    $actual_path = null;

    foreach ($extensions as $ext) {
        $test_path = $image_path . '.' . $ext;
        if (file_exists($test_path)) {
            $actual_path = $test_path;
            break;
        }
    }

    if (!$actual_path) {
        wp_send_json_error(['message' => '找不到图片文件']);
        return;
    }

    try {
        // 生成自动标签
        $auto_tagger = DHS_Tuku_Auto_Tagger::get_instance();
        $auto_tags = $auto_tagger->generate_auto_tags($actual_path, $image->name, $album_name);

        if (empty($auto_tags)) {
            wp_send_json_success([
                'message' => '未能提取到标签',
                'tags' => []
            ]);
            return;
        }

        // 保存标签
        $auto_confirm = isset($_POST['auto_confirm']) && $_POST['auto_confirm'] === 'true';
        $success = $auto_tagger->save_auto_tags($image_id, $auto_tags, $auto_confirm);

        if ($success) {
            wp_send_json_success([
                'message' => '自动标签生成成功',
                'tags' => $auto_tags,
                'count' => count($auto_tags)
            ]);
        } else {
            wp_send_json_error(['message' => '标签保存失败']);
        }
    } catch (Exception $e) {
        error_log('自动标签生成失败: ' . $e->getMessage());
        wp_send_json_error(['message' => '自动标签生成失败: ' . $e->getMessage()]);
    }
}

// 批量自动标签生成
add_action('wp_ajax_batch_generate_auto_tags', 'batch_generate_auto_tags_callback');

function batch_generate_auto_tags_callback()
{
    // 验证nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }

    // 检查用户权限
    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => '权限不足']);
        return;
    }

    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    $limit = isset($_POST['limit']) ? min(intval($_POST['limit']), 50) : 10; // 限制每次处理数量
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    global $wpdb;

    // 获取需要处理的图片
    if ($album_id > 0) {
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d ORDER BY id LIMIT %d OFFSET %d",
            $album_id,
            $limit,
            $offset
        ));
    } else {
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dhs_gallery_images ORDER BY id LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    if (empty($images)) {
        wp_send_json_success([
            'message' => '没有更多图片需要处理',
            'processed' => 0,
            'has_more' => false
        ]);
        return;
    }

    $processed_count = 0;
    $total_tags = 0;
    $errors = [];

    $auto_tagger = DHS_Tuku_Auto_Tagger::get_instance();

    foreach ($images as $image) {
        try {
            // 获取相册名称
            $album_name = $wpdb->get_var($wpdb->prepare(
                "SELECT album_name FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d",
                $image->album_id
            ));

            // 构建图片路径
            $image_path = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/' . $image->name;

            // 查找实际文件
            $extensions = ['jpg', 'jpeg', 'png', 'tif', 'tiff'];
            $actual_path = null;

            foreach ($extensions as $ext) {
                $test_path = $image_path . '.' . $ext;
                if (file_exists($test_path)) {
                    $actual_path = $test_path;
                    break;
                }
            }

            if (!$actual_path) {
                $errors[] = "图片文件不存在: {$image->name}";
                continue;
            }

            // 生成自动标签
            $auto_tags = $auto_tagger->generate_auto_tags($actual_path, $image->name, $album_name);

            if (!empty($auto_tags)) {
                $success = $auto_tagger->save_auto_tags($image->id, $auto_tags, true);
                if ($success) {
                    $total_tags += count($auto_tags);
                    $processed_count++;
                } else {
                    $errors[] = "标签保存失败: {$image->name}";
                }
            } else {
                $processed_count++; // 即使没有标签也算处理过
            }
        } catch (Exception $e) {
            $errors[] = "处理失败: {$image->name} - " . $e->getMessage();
            error_log('批量自动标签生成失败: ' . $e->getMessage());
        }
    }

    // 检查是否还有更多图片
    if ($album_id > 0) {
        $total_images = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d",
            $album_id
        ));
    } else {
        $total_images = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images");
    }

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

function delete_album_callback()
{
    // 检查 nonce 验证
    check_ajax_referer('dhs_nonce', '_ajax_nonce');

    // 检查用户权限
    if (!current_user_can('delete_posts')) {
        wp_send_json_error(array('message' => '权限不足'));
        return;
    }

    // 获取相册 ID
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;

    if ($album_id <= 0) {
        wp_send_json_error(array('message' => '无效的相册 ID'));
        return;
    }

    global $wpdb;
    $album_table = $wpdb->prefix . 'dhs_gallery_albums';
    $image_table = $wpdb->prefix . 'dhs_gallery_images';

    // 开始数据库事务
    $wpdb->query('START TRANSACTION');

    // 删除相册中的所有图片记录
    $deleted_images = $wpdb->delete($image_table, array('album_id' => $album_id), array('%d'));

    // 删除相册
    $deleted_album = $wpdb->delete($album_table, array('id' => $album_id), array('%d'));

    if ($deleted_album !== false) {
        // 删除相册文件夹
        $album_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id;

        if (is_dir($album_path)) {
            // 递归删除目录和文件
            delete_directory($album_path);
        }

        // 提交数据库事务
        $wpdb->query('COMMIT');

        wp_send_json_success(array('message' => '相册和所有图片已成功删除'));
    } else {
        // 回滚数据库事务
        $wpdb->query('ROLLBACK');

        wp_send_json_error(array('message' => '删除相册时发生错误'));
    }
}

add_action('wp_ajax_delete_album', 'delete_album_callback');


// 递归删除目录及其内容
function delete_directory($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            delete_directory($path); // 递归删除子目录
        } else {
            unlink($path); // 删除文件
        }
    }

    rmdir($dir); // 删除空目录
}




// 添加 AJAX 处理函数
add_action('wp_ajax_get_album_details', 'get_album_details_callback');

function get_album_details_callback()
{
    // 验证 nonce，确保请求的合法性
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }

    // 在通过安全验证后，处理获取相册信息的逻辑
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;

    if (!$album_id) {
        wp_send_json_error(array('message' => '无效的相册ID'));
        return;
    }

    // 获取相册详情的逻辑
    global $wpdb;
    $table_name = $wpdb->prefix . 'dhs_gallery_albums';
    $album = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $album_id), ARRAY_A);

    if ($album) {
        wp_send_json_success(array('album' => $album));
    } else {
        wp_send_json_error(array('message' => '未找到相册'));
    }
}



add_action('wp_ajax_update_album_settings', 'update_album_settings');
function update_album_settings()
{
    global $wpdb;

    // 验证权限和 nonce
    if (!current_user_can('edit_posts') || !check_ajax_referer('dhs_nonce', '_wpnonce', false)) {
        wp_send_json_error(['message' => '权限不足或 nonce 验证失败']);
    }

    $album_id = intval($_POST['album_id']);
    $album_name = sanitize_text_field($_POST['album_name']);
    $album_description = sanitize_textarea_field($_POST['album_description']);

    // 检查分类，如果是新建分类，需要插入新分类并获取其 ID
    if ($_POST['album_category'] === 'new') {
        $new_category_name = sanitize_text_field($_POST['new_category_name']);
        $parent_category_id = intval($_POST['parent_category']);

        // 验证新分类名称
        if (empty($new_category_name)) {
            wp_send_json_error(['message' => '请填写新分类名称']);
        }

        // 插入新分类并获取分类 ID
        $wpdb->insert(
            $wpdb->prefix . 'dhs_gallery_categories',
            [
                'category_name' => $new_category_name,
                'parent_id' => $parent_category_id,
            ],
            ['%s', '%d']
        );

        if ($wpdb->insert_id) {
            $category_id = $wpdb->insert_id; // 获取新分类的 ID
        } else {
            wp_send_json_error(['message' => '新分类创建失败']);
        }
    } else {
        $category_id = intval($_POST['album_category']);
    }

    // 验证字段
    if (!$album_id || !$album_name || !$category_id) {
        error_log('缺少必要字段');  // 调试日志
        wp_send_json_error(['message' => '缺少必要字段']);
    }

    // 处理封面图片上传
    $cover_image_url = '';
    if (!empty($_FILES['album_cover']['tmp_name']) && !$_FILES['album_cover']['error']) {
        $upload_dir = wp_upload_dir();
        $album_dir = $upload_dir['basedir'] . "/tuku/{$album_id}/thumbnails";
        if (!file_exists($album_dir)) {
            wp_mkdir_p($album_dir);
        }

        $cover_image_path = "{$album_dir}/cover_image.jpg";
        if (move_uploaded_file($_FILES['album_cover']['tmp_name'], $cover_image_path)) {
            $full_url = $upload_dir['baseurl'] . "/tuku/{$album_id}/thumbnails/cover_image.jpg";

            // 将完整 URL 转换为相对路径
            $relative_path = str_replace($upload_dir['baseurl'], '', $full_url);

            // 输出日志，记录转换后的相对路径
            error_log('封面图片上传成功，完整地址: ' . $full_url);
            error_log('封面图片转换后的相对路径: ' . $relative_path);

            $cover_image_url = $relative_path; // 使用相对路径
        } else {
            error_log('封面上传失败');  // 调试日志
            wp_send_json_error(['message' => '封面上传失败']);
        }
    }

    // 如果封面图片为空，保留数据库中的现有封面图片值
    if (empty($cover_image_url)) {
        $current_cover_image = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT cover_image FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d",
                $album_id
            )
        );
        $cover_image_url = $current_cover_image; // 保留现有的封面图片 URL
    }

    // 输出准备写入数据库的封面图片地址
    error_log('准备写入数据库的封面图片地址: ' . $cover_image_url);

    // 更新数据库
    $table_name_album = $wpdb->prefix . 'dhs_gallery_albums';
    $result = $wpdb->update(
        $table_name_album,
        [
            'album_name' => $album_name,
            'category_id' => $category_id,
            'cover_image' => $cover_image_url,  // 使用相对路径
            'description' => $album_description
        ],
        ['id' => $album_id],
        ['%s', '%d', '%s', '%s'],
        ['%d']
    );

    if ($result === false) {
        error_log('数据库更新失败');  // 调试日志
        wp_send_json_error(['message' => '数据库更新失败']);
    }

    // 成功返回
    wp_send_json_success(['message' => '相册更新成功']);
}


// 定义获取图片详细信息的函数
function get_image_details_callback()
{
    // 验证 nonce，确保请求的合法性
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }

    // 获取 image_id
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

    // 检查 image_id 是否有效
    if ($image_id === 0) {
        wp_send_json_error(array('message' => '无效的 image_id'));
        return;
    }

    global $wpdb;
    $images_table = $wpdb->prefix . 'dhs_gallery_images';
    $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
    $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
    $image_tag_table = $wpdb->prefix . 'dhs_gallery_image_tag';

    // 从数据库获取图片详细信息
    $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$images_table} WHERE id = %d", $image_id));

    if ($image) {
        // 获取图片的标签
        $tags = $wpdb->get_results($wpdb->prepare("
            SELECT t.tag_name
            FROM {$tags_table} t
            INNER JOIN {$image_tag_table} it ON t.id = it.tag_id
            WHERE it.image_id = %d
        ", $image_id));

        $image_tags = [];
        if ($tags) {
            foreach ($tags as $tag) {
                $image_tags[] = esc_html($tag->tag_name);
            }
        }

        // 获取 album_id
        $album_id = $image->album_id;  // 假设图片表中有 album_id 字段

        // 获取相册的名称作为分类和相册名称
        $album = $wpdb->get_row($wpdb->prepare("SELECT album_name FROM {$albums_table} WHERE id = %d", $album_id));
        $category = $album ? $album->album_name : '未分类';
        $album_name = $album ? $album->album_name : '未知相册'; // 相册名称

        // 解析 file_data 字段
        $file_data = json_decode($image->file_data, true);

        // 获取第一个 upload_date
        $upload_time = isset($file_data[0]['upload_date']) ? $file_data[0]['upload_date'] : '';

        $image_name = $image->name;

        // 构建完整的图片 URL
        $image_url = site_url('/wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . rawurlencode($image_name) . '_thumbnail.jpg');

        // 准备返回的数据
        $image_data = array(
            'image_id' => esc_html($image_id),
            'image_url' => esc_url($image_url),
            'file_name' => esc_html($image_name),
            'upload_time' => esc_html($upload_time),
            'category' => esc_html($category),
            'album_name' => esc_html($album_name),
            'album_id' => esc_html($album_id),
            'tags' => $image_tags // 使用实际获取的标签
        );

        // 动态生成文件按钮
        $buttons = [];
        if (!empty($file_data)) {
            foreach ($file_data as $file) {
                $file_type = strtoupper($file['file_type']); // 文件类型（例如JPG, PSD等）
                $file_url = site_url() . $file['file_path']; // 补全 file_path

                $buttons[] = array(
                    'type' => $file_type,
                    'url' => esc_url($file_url)
                );
            }
        }

        // 如果没有找到标签，添加"生成标签"按钮
        if (empty($image_tags)) {
            $buttons[] = array(
                'type' => '生成标签',
                'url' => '#', // 可以设置为触发生成标签的操作，比如一个 JavaScript 函数
                'action' => 'generate_tags' // 可以在前端根据这个 action 处理点击事件
            );
        }

        // 添加生成的文件按钮到返回数据中
        $image_data['buttons'] = $buttons;

        wp_send_json_success($image_data);
    } else {
        wp_send_json_error(array('message' => '未找到图片'));
    }
}
// 注册 AJAX 处理函数
add_action('wp_ajax_get_image_details', 'get_image_details_callback');
add_action('wp_ajax_nopriv_get_image_details', 'get_image_details_callback');


add_action('wp_ajax_associate_file', 'associate_file_callback');

function associate_file_callback()
{
    // 验证 nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }

    // 获取传递的 file_name 和 album_id
    $file_name = isset($_POST['file_name']) ? sanitize_text_field($_POST['file_name']) : '';
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;

    // 确保有有效的 album_id 和文件名
    if ($album_id === 0 || empty($file_name)) {
        wp_send_json_error(array('message' => '无效的 album_id 或文件名'));
        return;
    }

    // 处理上传文件
    if (isset($_FILES['file']) && !$_FILES['file']['error']) {
        $uploaded_file = $_FILES['file'];

        // 文件夹路径
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/tuku/' . $album_id;

        // 确保目录存在
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        // 目标文件路径，使用传递的 file_name
        $target_file = $target_dir . '/' . $file_name;

        // 移动上传的文件到目标位置
        if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            // 更新数据库，将文件信息追加到 file_data 列中
            global $wpdb;
            $images_table = $wpdb->prefix . 'dhs_gallery_images';

            // 获取当前文件的 file_data 列
            $image_id = intval($_POST['image_id']);
            $file_data = $wpdb->get_var($wpdb->prepare("SELECT file_data FROM {$images_table} WHERE id = %d", $image_id));
            $file_data = json_decode($file_data, true);

            // 添加新文件信息
            $file_data[] = array(
                'file_path' => '/wp-content/uploads/tuku/' . $album_id . '/' . $file_name,
                'file_type' => pathinfo($file_name, PATHINFO_EXTENSION),
                'upload_date' => current_time('mysql')
            );

            // 更新数据库
            $wpdb->update(
                $images_table,
                array('file_data' => wp_json_encode($file_data)),
                array('id' => $image_id),
                array('%s'),
                array('%d')
            );

            // 获取上传文件的完整 URL
            $file_url = $upload_dir['baseurl'] . '/tuku/' . $album_id . '/' . $file_name;

            wp_send_json_success(array(
                'message' => '文件关联成功',
                'file_url' => $file_url // 返回文件 URL
            ));
        } else {
            wp_send_json_error(array('message' => '文件移动失败'));
        }
    } else {
        wp_send_json_error(array('message' => '文件上传失败'));
    }
}
add_action('wp_ajax_associate_file', 'associate_file_callback');




function check_file_exists_callback()
{
    // 验证 nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }

    // 获取 album_id 和 file_name
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    $file_name = isset($_POST['file_name']) ? sanitize_text_field($_POST['file_name']) : '';

    if ($album_id === 0 || empty($file_name)) {
        wp_send_json_error(array('message' => '无效的 album_id 或文件名'));
        return;
    }

    // 文件夹路径
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/tuku/' . $album_id . '/' . $file_name;

    // 检查文件是否存在
    $file_exists = file_exists($file_path);

    // 输出调试日志
    error_log('检查文件是否存在: album_id = ' . $album_id . ', file_name = ' . $file_name);
    error_log('检查的文件路径: ' . $file_path);
    error_log('文件存在: ' . ($file_exists ? '是' : '否'));

    // 确保返回包含 exists 值的 JSON 响应
    wp_send_json_success(array('exists' => $file_exists));
}

// 注册 AJAX 处理函数
add_action('wp_ajax_check_file_exists', 'check_file_exists_callback');
add_action('wp_ajax_nopriv_check_file_exists', 'check_file_exists_callback');



function get_user_favorites()
{
    // 确保用户已登录
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

    // 查询用户的收藏夹
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

    // 处理查询结果
    if (!empty($favorites)) {
        foreach ($favorites as $favorite) {
            // 确保 associated_image_ids 不是 null
            $associated_image_ids = $favorite->associated_image_ids ?? ''; // 如果为 null，设置为空字符串
            $favorite->associated_image_ids = $associated_image_ids ? explode(',', $associated_image_ids) : [];
        }
        wp_send_json_success(['favorites' => $favorites]);
    } else {
        wp_send_json_success(['favorites' => []]); // 返回一个空数组
    }
}

// 注册 AJAX 动作
add_action('wp_ajax_get_user_favorites', 'get_user_favorites');



add_action('wp_ajax_update_favorite_selection', 'update_favorite_selection_callback');

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

    if ($favorite_id === 0 || $image_id === 0) {
        wp_send_json_error('无效的提交数据');
        return;
    }

    $table_name_favorite_images = $wpdb->prefix . 'dhs_gallery_favorite_images';

    if ($is_checked) {
        // 如果选中，则插入关联记录
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
        // 如果取消选中，则删除关联记录
        $result = $wpdb->delete(
            $table_name_favorite_images,
            array(
                'favorite_id' => $favorite_id,
                'image_id' => $image_id
            ),
            array('%d', '%d')
        );

        if ($result === false) {
            wp_send_json_error('更新数据库时发生错误：' . $wpdb->last_error);
        } else {
            wp_send_json_success('收藏夹选择已更新');
        }
    }
}


// 添加处理 AJAX 请求的钩子
add_action('wp_ajax_create_new_favorite', 'create_new_favorite_callback');

function create_new_favorite_callback()
{
    // 检查用户是否已登录
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }

    // 检查 AJAX 请求的 nonce 值是否合法
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error('无效的安全校验');
        return;
    }

    // 获取当前用户 ID 和新收藏夹名称
    $current_user_id = get_current_user_id();
    $favorite_name = isset($_POST['favorite_name']) ? sanitize_text_field($_POST['favorite_name']) : '';

    // 检查收藏夹名称是否为空
    if (empty($favorite_name)) {
        wp_send_json_error('收藏夹名称不能为空');
        return;
    }

    global $wpdb;
    $favorites_table = $wpdb->prefix . 'dhs_gallery_favorites';

    // 插入新收藏夹记录到数据库
    $result = $wpdb->insert(
        $favorites_table,
        array(
            'name' => $favorite_name,
            'user_id' => $current_user_id,
            'is_public' => 0, // 默认设置为非公开，具体根据需求可以调整
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array(
            '%s',
            '%d',
            '%d',
            '%s',
            '%s'
        )
    );

    // 检查插入是否成功
    if ($result !== false) {
        $favorite_id = $wpdb->insert_id; // 获取新插入的收藏夹ID
        wp_send_json_success(array('favorite_id' => $favorite_id)); // 返回收藏夹ID
    } else {
        wp_send_json_error('创建收藏夹时发生错误');
    }
}


add_action('wp_ajax_update_album_cover', 'update_album_cover_callback');

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


add_action('wp_ajax_upload_thumbnail_image', 'upload_thumbnail_image_callback');

function upload_thumbnail_image_callback()
{
    // 验证用户权限
    if (!is_user_logged_in()) {
        error_log('用户未登录');
        wp_send_json_error('用户未登录');
        return;
    }

    // 验证 nonce
    if (!check_ajax_referer('dhs_nonce', '_ajax_nonce', false)) {
        error_log('Nonce 验证失败');
        wp_send_json_error('Nonce 验证失败');
        return;
    }

    // 获取相册 ID 和文件名
    $album_id = isset($_POST['album_id']) ? intval($_POST['album_id']) : 0;
    $file_name = isset($_POST['file_name']) ? sanitize_file_name($_POST['file_name']) : '';

    error_log("接收到的相册 ID: $album_id");
    error_log("接收到的文件名: $file_name");

    if (!$album_id || !$file_name || !isset($_FILES['file'])) {
        error_log('无效的请求: 缺少必要的参数');
        wp_send_json_error('无效的请求');
        return;
    }

    // 处理上传文件
    $file = $_FILES['file'];
    error_log('接收到的文件信息: ' . print_r($file, true));

    $upload_dir = wp_upload_dir();
    $destination_path = $upload_dir['basedir'] . "/tuku/$album_id/thumbnails/";

    // 确保目录存在
    if (!file_exists($destination_path)) {
        if (wp_mkdir_p($destination_path)) {
            error_log("创建目录成功: $destination_path");
        } else {
            error_log("创建目录失败: $destination_path");
            wp_send_json_error('无法创建上传目录');
            return;
        }
    }

    $destination_file = $destination_path . $file_name;
    error_log("目标文件路径: $destination_file");

    // 处理图片
    $image = null;
    switch ($file['type']) {
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            error_log('加载 PNG 图片成功');
            break;
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            error_log('加载 JPG 图片成功');
            break;
        default:
            error_log('不支持的图片类型');
            wp_send_json_error('不支持的图片类型');
            return;
    }

    if (!$image) {
        error_log('图片加载失败');
        wp_send_json_error('图片处理失败');
        return;
    }

    // 获取原始尺寸
    $width = imagesx($image);
    $height = imagesy($image);
    error_log("原始图片尺寸: 宽度 $width, 高度 $height");

    // 如果宽边超过800px，则等比例缩小
    if ($width > 800 || $height > 800) {
        if ($width > $height) {
            $new_width = 800;
            $new_height = intval($height * (800 / $width));
        } else {
            $new_height = 800;
            $new_width = intval($width * (800 / $height));
        }

        error_log("调整后的图片尺寸: 宽度 $new_width, 高度 $new_height");

        $resized_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);  // 销毁原始图片资源
        $image = $resized_image;  // 更新图片资源为调整后的图片
    }

    // 保存图片
    if (imagejpeg($image, $destination_file, 90)) { // 保存为 JPG 格式，90 是 JPG 压缩质量
        error_log("图片成功保存到: $destination_file");
        wp_send_json_success('文件上传成功');
    } else {
        error_log('图片保存失败');
        wp_send_json_error('文件上传失败');
    }

    imagedestroy($image); // 销毁图片资源
}


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

function delete_album_entries()
{
    check_ajax_referer('dhs_nonce', '_wpnonce');

    global $wpdb;
    $album_id = intval($_POST['album_id']);

    $table_name = $wpdb->prefix . 'dhs_gallery_images';
    $deleted = $wpdb->delete($table_name, array('album_id' => $album_id));

    if ($deleted !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('无法删除相册条目。');
    }
}
add_action('wp_ajax_delete_album_entries', 'delete_album_entries');

function append_timestamp_to_files($album_id)
{
    $dir = ABSPATH . "wp-content/uploads/tuku/{$album_id}";

    if (!file_exists($dir) || !is_dir($dir)) {
        return false;
    }

    // 获取当前时间戳
    $timestamp = time();

    // 打开目录
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $old_path = $dir . '/' . $file;
                if (is_file($old_path)) {
                    $file_info = pathinfo($file);

                    // 正则表达式检查文件名是否已经包含时间戳格式
                    if (!preg_match('/_\d{10}$/', $file_info['filename'])) {
                        $new_filename = $file_info['filename'] . '_' . $timestamp . '.' . $file_info['extension'];
                        $new_path = $dir . '/' . $new_filename;
                        rename($old_path, $new_path);
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


add_action('wp_ajax_delete_thumbnails_folder', 'delete_thumbnails_folder');

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
function remove_directory($dir)
{
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir) || is_link($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!remove_directory($dir . "/" . $item)) {
            chmod($dir . "/" . $item, 0777);
            if (!remove_directory($dir . "/" . $item)) return false;
        }
    }
    return rmdir($dir);
}


function process_files_and_insert_into_db()
{
    check_ajax_referer('dhs_nonce', '_wpnonce');

    global $wpdb;
    $album_id = intval($_POST['album_id']);

    // 使用 WordPress 的 ABSPATH 常量来获取根目录路径
    $dir = ABSPATH . "wp-content/uploads/tuku/{$album_id}";

    // 检查目录是否存在
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

                // 过滤掉 .DS_Store 和 Thumbs.db 文件（包括重命名后带有时间戳的文件）
                if (preg_match('/\.(DS_Store|Thumbs\.db)$/i', $file)) {
                    continue; // 跳过这些文件
                }

                // 将文件按名称分组
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

    // 遍历分组后的文件并写入数据库
    foreach ($files as $name => $file_group) {
        // 在处理文件之前过滤掉 .DS_Store 和其他不需要的文件
        $filtered_group = array_filter($file_group, function ($file) {
            $file_info = pathinfo($file['file_path']);
            $basename = $file_info['basename'];

            // 检查并过滤掉无效文件
            return !preg_match('/^(\.DS_Store|Thumbs\.db)$/i', $basename);
        });

        // 如果过滤后没有文件，跳过此组
        if (empty($filtered_group)) {
            continue;
        }

        // 将文件组编码为 JSON
        $file_data = json_encode($filtered_group);

        // 获取文件名（去掉扩展名）
        $name = pathinfo(reset($filtered_group)['file_path'], PATHINFO_FILENAME);

        // 写入数据库
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


function dhs_delete_images()
{
    // 验证nonce
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
        $image_found = false;

        // 遍历原图目录
        foreach (glob($image_dir . $image_name . '.*') as $file) {
            if (is_file($file)) {
                unlink($file);
                $image_found = true;
            }
        }

        // 遍历缩略图目录
        foreach (glob($thumbnail_dir . $image_name . '_thumbnail.*') as $file) {
            if (is_file($file)) {
                unlink($file);
                $image_found = true;
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


function load_more_images()
{
    if (!isset($_POST['album_id']) || !isset($_POST['offset'])) {
        error_log('Missing album_id or offset in the request');
        wp_send_json_error(['message' => '缺少必要的参数']);
        wp_die();
    }

    $album_id = intval($_POST['album_id']);
    $offset = intval($_POST['offset']);
    $images_per_page = 20;

    error_log("Loading more images for album_id: $album_id with offset: $offset");

    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d LIMIT %d OFFSET %d",
        $album_id,
        $images_per_page,
        $offset
    );

    error_log("Running query: $query");

    $images = $wpdb->get_results($query);

    if ($wpdb->last_error) {
        error_log("Database error: " . $wpdb->last_error);
        wp_send_json_error(['message' => '数据库查询错误']);
        wp_die();
    }

    error_log("Number of images fetched: " . count($images));

    if (empty($images)) {
        error_log("No more images found for album_id: $album_id at offset: $offset");
        wp_send_json_error(['message' => '没有更多图片']);
        wp_die();
    }

    ob_start();
    foreach ($images as $image) {
        $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . $image->name . '_thumbnail.jpg';
        $thumbnail_url = site_url('/wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');

        if (!file_exists($thumbnail_path)) {
            error_log("Thumbnail not found for image: " . $image->name);
            $thumbnail_url = site_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg');
        }

?>
        <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>">
            <input type="checkbox" class="image-checkbox">
            <div class="dhs-image-icons">
                <a href="javascript:void(0)" class="favorite-icon" title="收藏">
                    <i class="fa fa-star"></i>
                </a>
                <!-- 添加 favorite-select-container -->
                <div class="favorite-select-container">
                    <button class="favorite-dropdown-btn">新建收藏夹</button>
                    <div class="favorite-dropdown-content">
                        <!-- 收藏夹选项会通过 JavaScript 动态插入到这里 -->
                    </div>
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

    // 检查 $html 是否正确生成
    if (empty($html)) {
        error_log("Generated HTML is empty or undefined.");
    } else {
        error_log("Generated HTML content is valid.");
    }

    $has_more = count($images) === $images_per_page;

    error_log("Generated HTML for images. Has more: " . ($has_more ? 'Yes' : 'No'));

    // 发送 JSON 响应
    wp_send_json_success(['html' => $html, 'has_more' => $has_more]);
}
add_action('wp_ajax_load_more_images', 'load_more_images');
add_action('wp_ajax_nopriv_load_more_images', 'load_more_images');

function load_more_liked_images()
{
    if (!isset($_POST['user_id']) || !isset($_POST['offset'])) {
        error_log('Missing user_id or offset in the request');
        wp_send_json_error(['message' => '缺少必要的参数']);
        wp_die();
    }

    $user_id = intval($_POST['user_id']);
    $offset = intval($_POST['offset']);
    $images_per_page = 20;

    error_log("Loading more liked images for user_id: $user_id with offset: $offset");

    global $wpdb;
    $likes_table = $wpdb->prefix . 'dhs_gallery_likes';
    $images_table = $wpdb->prefix . 'dhs_gallery_images';

    // 查询用户喜欢的图片
    $query = $wpdb->prepare(
        "SELECT img.*, img.album_id FROM $images_table img
         JOIN $likes_table likes ON img.id = likes.image_id
         WHERE likes.user_id = %d
         LIMIT %d OFFSET %d",
        $user_id,
        $images_per_page,
        $offset
    );

    error_log("Running query: $query");

    $images = $wpdb->get_results($query);

    if ($wpdb->last_error) {
        error_log("Database error: " . $wpdb->last_error);
        wp_send_json_error(['message' => '数据库查询错误']);
        wp_die();
    }

    error_log("Number of liked images fetched: " . count($images));

    if (empty($images)) {
        error_log("No more liked images found for user_id: $user_id at offset: $offset");
        wp_send_json_error(['message' => '没有更多喜欢的图片']);
        wp_die();
    }

    ob_start();
    foreach ($images as $image) {
        // 直接从 img 中获取 album_id
        $album_id = esc_attr($image->album_id);

        $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';
        $thumbnail_url = site_url('/wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');

        if (!file_exists($thumbnail_path)) {
            error_log("Thumbnail not found for image: " . $image->name);
            $thumbnail_url = site_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg');
        }

    ?>
        <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>">
            <div class="dhs-image-icons">
                <a href="javascript:void(0)" class="favorite-icon" title="收藏">
                    <i class="fa fa-star"></i>
                </a>
                <!-- 添加 favorite-select-container -->
                <div class="favorite-select-container">
                    <button class="favorite-dropdown-btn">新建收藏夹</button>
                    <div class="favorite-dropdown-content">
                        <!-- 收藏夹选项会通过 JavaScript 动态插入到这里 -->
                    </div>
                </div>
                <a href="javascript:void(0)" class="like-icon" title="喜欢">
                    <i class="fa fa-heart"></i>
                </a>
            </div>
            <a href="javascript:void(0)" class="chakanimage dhs-tuku-open-modal" data-modal="image:50" data-image-id="<?php echo esc_attr($image->id); ?>">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($image->name); ?>" />
            </a>
        </div>
<?php
    }

    $html = ob_get_clean();

    if (empty($html)) {
        error_log("Generated HTML is empty or undefined.");
    } else {
        error_log("Generated HTML content is valid.");
    }

    $has_more = count($images) === $images_per_page;

    error_log("Generated HTML for liked images. Has more: " . ($has_more ? 'Yes' : 'No'));

    wp_send_json_success(['html' => $html, 'has_more' => $has_more]);
}


add_action('wp_ajax_load_more_liked_images', 'load_more_liked_images');
add_action('wp_ajax_nopriv_load_more_liked_images', 'load_more_liked_images');





function enqueue_masonry_script()
{
    wp_enqueue_script(
        'masonry-js',
        'https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js',
        array('jquery'),
        '4.2.2',
        true
    );

    wp_enqueue_script(
        'imagesloaded-js',
        'https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/4.1.4/imagesloaded.pkgd.min.js',
        array('jquery'),
        '4.1.4',
        true
    );

    wp_add_inline_script('imagesloaded-js', "
        jQuery(document).ready(function($) {
            var \$grid = $('#albumImagesGrid, #likedImagesGrid ,#searchResultsGrid'); // 支持多种选择器
            if (\$grid.length) {
                \$grid.imagesLoaded(function() {
                    \$grid.masonry({
                        itemSelector: '.dhs-album-image-item',
                        percentPosition: true,
                        columnWidth: '.dhs-album-image-item',
                        gutter: 15
                    });
                });
            }
        });
    ");
}
add_action('wp_enqueue_scripts', 'enqueue_masonry_script');



function update_image_tags_callback()
{
    check_ajax_referer('dhs_nonce', '_ajax_nonce');

    $image_id = intval($_POST['image_id']);
    $new_tags = json_decode(stripslashes($_POST['tags']), true); // 接收并解析前端传来的标签数组

    if (!$image_id || empty($new_tags)) {
        error_log("Invalid image_id or empty tags provided.");
        wp_send_json_error(array('message' => '无效的图片ID或标签为空'));
        return;
    }

    global $wpdb;

    // 获取当前图片已有的标签
    $current_tags = $wpdb->get_col($wpdb->prepare(
        "SELECT t.tag_name 
        FROM {$wpdb->prefix}dhs_gallery_tags t
        INNER JOIN {$wpdb->prefix}dhs_gallery_image_tag it ON t.id = it.tag_id
        WHERE it.image_id = %d",
        $image_id
    ));

    // 查找需要删除的标签
    $tags_to_delete = array_diff($current_tags, $new_tags);
    if (!empty($tags_to_delete)) {
        foreach ($tags_to_delete as $tag_name) {
            $tag_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
                $tag_name
            ));

            if ($tag_id) {
                // 从关联表中删除该标签
                $wpdb->delete("{$wpdb->prefix}dhs_gallery_image_tag", array('image_id' => $image_id, 'tag_id' => $tag_id), array('%d', '%d'));
                error_log("Deleted tag association: image_id = $image_id, tag_id = $tag_id");
            }
        }
    }

    // 查找需要添加的新标签
    $tags_to_add = array_diff($new_tags, $current_tags);
    foreach ($tags_to_add as $tag_name) {
        // 检查标签是否已经存在
        $existing_tag_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
            $tag_name
        ));

        if (!$existing_tag_id) {
            // 标签不存在，插入新标签
            $wpdb->insert(
                "{$wpdb->prefix}dhs_gallery_tags",
                array('tag_name' => $tag_name),
                array('%s')
            );
            $tag_id = $wpdb->insert_id;
            error_log("Inserted new tag: $tag_name with ID: $tag_id");
        } else {
            // 标签已存在，使用现有的标签 ID
            $tag_id = $existing_tag_id;
            error_log("Tag already exists: $tag_name with ID: $tag_id");
        }

        // 添加关联表的数据
        $wpdb->insert(
            "{$wpdb->prefix}dhs_gallery_image_tag",
            array('image_id' => $image_id, 'tag_id' => $tag_id),
            array('%d', '%d')
        );
        error_log("Inserted tag association: image_id = $image_id, tag_id = $tag_id");
    }

    // 获取最新的标签列表返回前端
    $updated_tags = $wpdb->get_col($wpdb->prepare(
        "SELECT t.tag_name 
        FROM {$wpdb->prefix}dhs_gallery_tags t
        INNER JOIN {$wpdb->prefix}dhs_gallery_image_tag it ON t.id = it.tag_id
        WHERE it.image_id = %d",
        $image_id
    ));

    wp_send_json_success(array('tags' => $updated_tags));
}

add_action('wp_ajax_update_image_tags', 'update_image_tags_callback');


// 删除标签的 AJAX 回调函数
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

    // 获取标签的 ID
    $tag_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
        $tag_name
    ));

    if (!$tag_id) {
        wp_send_json_error(array('message' => '标签不存在'));
        return;
    }

    // 删除图片与标签的关联
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

// 将标签应用到素材集所有项目的 AJAX 回调函数
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

    // 获取标签的 ID
    $tag_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
        $tag_name
    ));

    if (!$tag_id) {
        // 标签不存在时，创建新的标签
        $wpdb->insert(
            "{$wpdb->prefix}dhs_gallery_tags",
            array('tag_name' => $tag_name),
            array('%s')
        );
        $tag_id = $wpdb->insert_id;
    }

    if (!$tag_id) {
        wp_send_json_error(array('message' => '创建或获取标签时出错'));
        return;
    }

    // 获取所有属于该相册的图片ID
    $image_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d",
        $album_id
    ));

    if (empty($image_ids)) {
        wp_send_json_error(array('message' => '该相册中没有图片'));
        return;
    }

    // 为相册中的每个图片添加标签
    foreach ($image_ids as $image_id) {
        $existing_assoc = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_image_tag WHERE image_id = %d AND tag_id = %d",
            $image_id,
            $tag_id
        ));

        if (!$existing_assoc) {
            $wpdb->insert(
                "{$wpdb->prefix}dhs_gallery_image_tag",
                array(
                    'image_id' => $image_id,
                    'tag_id' => $tag_id
                ),
                array('%d', '%d')
            );
        }
    }

    wp_send_json_success();
}
add_action('wp_ajax_apply_tag_to_all', 'apply_tag_to_all_callback');


add_action('wp_ajax_update_like_status', 'update_like_status_callback');

function update_like_status_callback()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('用户未登录');
        return;
    }

    // 检查 AJAX 请求的 nonce 值是否合法
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
        // 检查是否已经喜欢过该图片
        $existing_like = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name_likes WHERE user_id = %d AND image_id = %d",
            $current_user_id,
            $image_id
        ));

        if ($existing_like > 0) {
            wp_send_json_error('你已经喜欢过这张图片');
            return;
        }

        // 添加喜欢记录
        $result = $wpdb->insert(
            $table_name_likes,
            array(
                'user_id' => $current_user_id,
                'image_id' => $image_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );

        if ($result === false) {
            wp_send_json_error('更新数据库时发生错误：' . $wpdb->last_error);
        } else {
            wp_send_json_success('图片已喜欢');
        }
    } else {
        // 删除喜欢记录
        $result = $wpdb->delete(
            $table_name_likes,
            array(
                'user_id' => $current_user_id,
                'image_id' => $image_id
            ),
            array('%d', '%d')
        );

        if ($result === false) {
            wp_send_json_error('更新数据库时发生错误：' . $wpdb->last_error);
        } else {
            wp_send_json_success('图片喜欢已取消');
        }
    }
}


add_action('wp_ajax_check_like_status', 'check_like_status_callback');

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
        $current_user_id,
        $image_id
    ));

    if ($is_liked > 0) {
        wp_send_json_success(array('is_liked' => true));
    } else {
        wp_send_json_success(array('is_liked' => false));
    }
}


function dhs_delete_favorite_callback()
{
    // 验证 nonce
    check_ajax_referer('dhs_delete_favorite_nonce', '_ajax_nonce');

    global $wpdb;
    $favorite_id = intval($_POST['favorite_id']);

    // 检查当前用户是否有权删除此收藏夹
    $current_user_id = get_current_user_id();
    $favorites_table = $wpdb->prefix . 'dhs_gallery_favorites';

    $favorite = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$favorites_table} WHERE id = %d AND user_id = %d
    ", $favorite_id, $current_user_id));

    if (!$favorite) {
        wp_send_json_error(['message' => '收藏夹不存在或您无权删除此收藏夹。']);
    }

    // 删除收藏夹记录
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
    // 反转 private 状态，因为 is_public 为 1 表示公开，0 表示私密
    $is_public = isset($_POST['favorite_private']) ? 0 : 1;

    $updated = $wpdb->update(
        $wpdb->prefix . 'dhs_gallery_favorites',
        [
            'name' => $favorite_name,
            'is_public' => $is_public,
        ],
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

    $result = $wpdb->get_row($wpdb->prepare("
        SELECT name, is_public 
        FROM {$wpdb->prefix}dhs_gallery_favorites 
        WHERE id = %d
    ", $favorite_id), ARRAY_A);

    // 调试输出查询结果
    error_log('Favorite Details Query Result: ' . print_r($result, true));

    if ($result) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error(['message' => '无法找到收藏夹详情']);
    }
}

add_action('wp_ajax_get_favorite_details', 'get_favorite_details_callback');



function dhs_get_category_details_callback()
{
    // 验证 nonce
    check_ajax_referer('dhs_nonce', 'security');

    global $wpdb;
    $category_id = intval($_POST['category_id']);

    // 查询分类详情
    $result = $wpdb->get_row($wpdb->prepare("
        SELECT category_name AS name, parent_id 
        FROM {$wpdb->prefix}dhs_gallery_categories 
        WHERE id = %d
    ", $category_id), ARRAY_A);

    if ($result) {
        // 获取可用的父分类
        $available_categories = $wpdb->get_results("
            SELECT id, category_name AS name 
            FROM {$wpdb->prefix}dhs_gallery_categories 
            WHERE id != {$category_id} AND parent_id IS NULL
        ", ARRAY_A);

        // 返回分类详情和可用的父分类列表
        wp_send_json_success([
            'name' => $result['name'],
            'parent_id' => $result['parent_id'],
            'available_categories' => $available_categories,
        ]);
    } else {
        wp_send_json_error(['message' => '无法找到分类详情']);
    }
}

add_action('wp_ajax_get_category_details', 'dhs_get_category_details_callback');


function dhs_edit_category_callback()
{
    // 验证 nonce
    check_ajax_referer('dhs_nonce', 'security');

    global $wpdb;
    $category_id = intval($_POST['category_id']);
    $category_name = sanitize_text_field($_POST['category_name']);
    $parent_category = intval($_POST['parent_category']) === 0 ? null : intval($_POST['parent_category']);

    // 更新分类
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


function dhs_delete_category_callback()
{
    // 验证 nonce
    check_ajax_referer('dhs_nonce', 'security');

    global $wpdb;
    $category_id = intval($_POST['category_id']);

    // 删除分类前，检查是否存在子分类
    $child_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}dhs_gallery_categories 
        WHERE parent_id = %d
    ", $category_id));

    if ($child_count > 0) {
        wp_send_json_error(['message' => '无法删除：请先删除子分类']);
    }

    // 删除分类记录
    $deleted = $wpdb->delete($wpdb->prefix . 'dhs_gallery_categories', ['id' => $category_id], ['%d']);

    if ($deleted) {
        wp_send_json_success(['message' => '分类已删除']);
    } else {
        wp_send_json_error(['message' => '删除分类失败']);
    }
}

add_action('wp_ajax_delete_category', 'dhs_delete_category_callback');


function check_favorite_public_status()
{
    check_ajax_referer('dhs_nonce', '_ajax_nonce');

    global $wpdb;
    $favorite_id = intval($_POST['favorite_id']);
    $table_name = $wpdb->prefix . 'dhs_gallery_favorites';

    $favorite = $wpdb->get_row($wpdb->prepare("
        SELECT is_public, name 
        FROM {$table_name} 
        WHERE id = %d
    ", $favorite_id));

    error_log("检查公开状态，收藏夹ID: $favorite_id, 公开状态: {$favorite->is_public}"); // 调试信息

    if ($favorite) {
        wp_send_json_success([
            'is_public' => $favorite->is_public,
            'favorite_name' => $favorite->name,
        ]);
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

    $updated = $wpdb->update(
        $table_name,
        ['is_public' => 1],
        ['id' => $favorite_id],
        ['%d'],
        ['%d']
    );

    error_log("设为公开，收藏夹ID: $favorite_id, 更新状态: $updated"); // 调试信息

    if ($updated !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => '无法更新收藏夹状态。']);
    }
}
add_action('wp_ajax_make_favorite_public_and_share', 'make_favorite_public_and_share');

/**
 * AI标签生成回调函数
 */
function generate_ai_tags_callback()
{
    // 验证nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }

    // 检查用户权限
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

    // 获取图片信息
    $image = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE id = %d",
        $image_id
    ));

    if (!$image) {
        wp_send_json_error(['message' => '图片不存在']);
        return;
    }

    // 获取相册信息
    $album = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d",
        $image->album_id
    ));

    if (!$album) {
        wp_send_json_error(['message' => '相册不存在']);
        return;
    }

    // 使用缩略图路径进行AI分析（缩略图都是标准的jpg格式）
    $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';

    if (!file_exists($thumbnail_path)) {
        wp_send_json_error(['message' => '缩略图不存在: ' . $image->name]);
        return;
    }

    $image_path = $thumbnail_path;

    try {
        // 创建AI标签生成器实例
        $ai_tagger = new DHS_Tuku_AI_Tagger();

        // 尝试使用AI服务，如果不可用则使用降级方案
        $ai_tags = [];
        $is_ai_available = $ai_tagger->is_ai_service_available();

        if ($is_ai_available) {
            // AI服务可用，尝试生成AI标签
            try {
                $ai_tags = $ai_tagger->generate_ai_tags($image_path, $image->name, $album->album_name);
            } catch (Exception $e) {
                error_log('AI标签生成失败，使用降级方案: ' . $e->getMessage());
                $ai_tags = [];
            }
        }

        // 如果AI标签生成失败或服务不可用，使用降级方案
        if (empty($ai_tags)) {
            $ai_tags = $ai_tagger->generate_fallback_tags($image_path, $image->name, $album->album_name);
            $fallback_used = true;
        } else {
            $fallback_used = false;
        }

        if (empty($ai_tags)) {
            wp_send_json_error(['message' => '标签生成失败，请重试']);
            return;
        }

        // 将AI标签保存到数据库
        $saved_tags = [];

        foreach ($ai_tags as $tag_name) {
            if (empty(trim($tag_name))) {
                continue;
            }

            // 检查标签是否已存在
            $existing_tag = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
                $tag_name
            ));

            if ($existing_tag) {
                $tag_id = $existing_tag->id;
            } else {
                // 创建新标签
                $insert_result = $wpdb->insert(
                    $wpdb->prefix . 'dhs_gallery_tags',
                    [
                        'tag_name' => $tag_name
                    ],
                    ['%s']
                );

                if ($insert_result === false) {
                    continue;
                }

                $tag_id = $wpdb->insert_id;
            }

            // 检查图片和标签的关联是否已存在
            $existing_relation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dhs_gallery_image_tag WHERE image_id = %d AND tag_id = %d",
                $image_id,
                $tag_id
            ));

            if (!$existing_relation) {
                // 创建图片和标签的关联
                $relation_result = $wpdb->insert(
                    $wpdb->prefix . 'dhs_gallery_image_tag',
                    [
                        'image_id' => $image_id,
                        'tag_id' => $tag_id
                    ],
                    ['%d', '%d']
                );

                if ($relation_result !== false) {
                    $saved_tags[] = $tag_name;
                }
            } else {
                $saved_tags[] = $tag_name; // 关联已存在也算成功
            }
        }

        if (!empty($saved_tags)) {
            $message = $fallback_used ? '智能标签生成成功（使用基础算法）' : 'AI标签生成成功';
            wp_send_json_success([
                'message' => $message,
                'tags' => $saved_tags,
                'count' => count($saved_tags),
                'fallback_used' => $fallback_used
            ]);
        } else {
            wp_send_json_error(['message' => '标签保存失败']);
        }
    } catch (Exception $e) {
        error_log('AI标签生成异常: ' . $e->getMessage());
        wp_send_json_error(['message' => 'AI标签生成失败: ' . $e->getMessage()]);
    }
}

add_action('wp_ajax_generate_ai_tags', 'generate_ai_tags_callback');
add_action('wp_ajax_nopriv_generate_ai_tags', 'generate_ai_tags_callback');

/**
 * 批量AI标签生成回调函数
 */
function batch_generate_ai_tags_callback()
{
    // 验证nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'dhs_nonce')) {
        wp_send_json_error(['message' => '安全验证失败']);
        return;
    }

    // 检查用户权限
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => '请先登录']);
        return;
    }

    $album_id = intval($_POST['album_id']);
    $limit = isset($_POST['limit']) ? min(intval($_POST['limit']), 20) : 5; // AI处理限制更小
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    global $wpdb;

    // 创建AI标签生成器并检查服务可用性
    $ai_tagger = new DHS_Tuku_AI_Tagger();
    $ai_service_available = $ai_tagger->is_ai_service_available();

    try {
        // 获取图片列表
        if ($album_id > 0) {
            $images = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d ORDER BY id LIMIT %d OFFSET %d",
                $album_id,
                $limit,
                $offset
            ));
        } else {
            $images = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dhs_gallery_images ORDER BY id LIMIT %d OFFSET %d",
                $limit,
                $offset
            ));
        }

        $processed_count = 0;
        $total_tags_generated = 0;
        $errors = [];
        $fallback_count = 0;

        foreach ($images as $image) {
            try {
                // 获取相册信息
                $album = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d",
                    $image->album_id
                ));

                if (!$album) {
                    $errors[] = "图片 {$image->name}: 相册不存在";
                    continue;
                }

                // 使用缩略图路径进行AI分析（缩略图都是标准的jpg格式）
                $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $image->album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';

                if (!file_exists($thumbnail_path)) {
                    $errors[] = "图片 {$image->name}: 缩略图不存在";
                    continue;
                }

                $image_path = $thumbnail_path;

                // 生成AI标签或使用降级方案
                $ai_tags = [];
                if ($ai_service_available) {
                    try {
                        $ai_tags = $ai_tagger->generate_ai_tags($image_path, $image->name, $album->album_name);
                    } catch (Exception $e) {
                        error_log('批量AI标签生成失败，使用降级方案: ' . $e->getMessage());
                        $ai_tags = [];
                    }
                }

                // 如果AI服务不可用或生成失败，使用降级方案
                if (empty($ai_tags)) {
                    $ai_tags = $ai_tagger->generate_fallback_tags($image_path, $image->name, $album->album_name);
                    $fallback_count++;
                }

                if (empty($ai_tags)) {
                    $errors[] = "图片 {$image->name}: 标签生成失败";
                    continue;
                }

                // 保存标签到数据库
                $saved_count = 0;
                foreach ($ai_tags as $tag_name) {
                    if (empty(trim($tag_name))) continue;

                    // 检查标签是否已存在
                    $existing_tag = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}dhs_gallery_tags WHERE tag_name = %s",
                        $tag_name
                    ));

                    if ($existing_tag) {
                        $tag_id = $existing_tag->id;
                    } else {
                        // 创建新标签
                        $insert_result = $wpdb->insert(
                            $wpdb->prefix . 'dhs_gallery_tags',
                            [
                                'tag_name' => $tag_name
                            ],
                            ['%s']
                        );

                        if ($insert_result === false) {
                            continue;
                        }

                        $tag_id = $wpdb->insert_id;
                    }

                    // 检查图片和标签的关联是否已存在
                    $existing_relation = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}dhs_gallery_image_tag WHERE image_id = %d AND tag_id = %d",
                        $image->id,
                        $tag_id
                    ));

                    if (!$existing_relation) {
                        // 创建图片和标签的关联
                        $relation_result = $wpdb->insert(
                            $wpdb->prefix . 'dhs_gallery_image_tag',
                            [
                                'image_id' => $image->id,
                                'tag_id' => $tag_id
                            ],
                            ['%d', '%d']
                        );

                        if ($relation_result !== false) {
                            $saved_count++;
                        }
                    }
                }

                $total_tags_generated += $saved_count;
                $processed_count++;
            } catch (Exception $e) {
                $errors[] = "图片 {$image->name}: " . $e->getMessage();
                error_log('AI批量标签生成错误: ' . $e->getMessage());
            }
        }

        // 检查是否还有更多图片需要处理
        $has_more = false;
        $next_offset = $offset + $limit;

        if ($album_id > 0) {
            $total_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images WHERE album_id = %d",
                $album_id
            ));
        } else {
            $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_images");
        }

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
        error_log('批量AI标签生成异常: ' . $e->getMessage());
        wp_send_json_error(['message' => '批量AI标签生成失败: ' . $e->getMessage()]);
    }
}

add_action('wp_ajax_batch_generate_ai_tags', 'batch_generate_ai_tags_callback');
add_action('wp_ajax_nopriv_batch_generate_ai_tags', 'batch_generate_ai_tags_callback');

// 标签管理相关的AJAX处理函数

// 添加新标签
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

    // 检查标签是否已存在
    $existing_tag = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tags_table} WHERE LOWER(tag_name) = LOWER(%s)",
        $tag_name
    ));

    if ($existing_tag) {
        wp_send_json_error('标签已存在');
        wp_die();
    }

    // 插入新标签
    $result = $wpdb->insert($tags_table, [
        'tag_name' => $tag_name
    ], ['%s']);

    if ($result === false) {
        wp_send_json_error('添加标签失败：' . $wpdb->last_error);
        wp_die();
    }

    wp_send_json_success(['tag_id' => $wpdb->insert_id, 'tag_name' => $tag_name]);
}
add_action('wp_ajax_dhs_add_tag', 'dhs_add_tag');

// 更新标签
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

    // 检查标签是否已存在（排除当前标签）
    $existing_tag = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tags_table} WHERE LOWER(tag_name) = LOWER(%s) AND id != %d",
        $tag_name,
        $tag_id
    ));

    if ($existing_tag) {
        wp_send_json_error('标签已存在');
        wp_die();
    }

    // 更新标签
    $result = $wpdb->update($tags_table, [
        'tag_name' => $tag_name
    ], [
        'id' => $tag_id
    ], ['%s'], ['%d']);

    if ($result === false) {
        wp_send_json_error('更新标签失败：' . $wpdb->last_error);
        wp_die();
    }

    wp_send_json_success(['tag_id' => $tag_id, 'tag_name' => $tag_name]);
}
add_action('wp_ajax_dhs_update_tag', 'dhs_update_tag');

// 删除标签
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

    // 检查标签是否正在使用
    $usage_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$image_tag_table} WHERE tag_id = %d",
        $tag_id
    ));

    if ($usage_count > 0) {
        wp_send_json_error('该标签正在使用中，无法删除');
        wp_die();
    }

    // 删除标签
    $result = $wpdb->delete($tags_table, ['id' => $tag_id], ['%d']);

    if ($result === false) {
        wp_send_json_error('删除标签失败：' . $wpdb->last_error);
        wp_die();
    }

    wp_send_json_success(['tag_id' => $tag_id]);
}
add_action('wp_ajax_dhs_delete_tag', 'dhs_delete_tag');

// 清空所有标签
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

    // 开始事务
    $wpdb->query('START TRANSACTION');

    try {
        // 首先删除所有图片标签关联
        $delete_image_tags = $wpdb->query("DELETE FROM {$image_tag_table}");
        if ($delete_image_tags === false) {
            throw new Exception('删除图片标签关联失败：' . $wpdb->last_error);
        }

        // 然后删除所有标签
        $delete_tags = $wpdb->query("DELETE FROM {$tags_table}");
        if ($delete_tags === false) {
            throw new Exception('删除标签失败：' . $wpdb->last_error);
        }

        // 提交事务
        $wpdb->query('COMMIT');

        wp_send_json_success([
            'message' => '所有标签已成功清空',
            'deleted_tags' => $delete_tags,
            'deleted_image_tags' => $delete_image_tags
        ]);

    } catch (Exception $e) {
        // 回滚事务
        $wpdb->query('ROLLBACK');
        wp_send_json_error('清空标签失败：' . $e->getMessage());
    }

    wp_die();
}
add_action('wp_ajax_dhs_clear_all_tags', 'dhs_clear_all_tags');
