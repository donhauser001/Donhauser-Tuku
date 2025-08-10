<?php
/**
 * DHS图库AJAX处理类
 * 统一管理所有AJAX请求
 */

class DHS_Tuku_Ajax
{
    public static function init()
    {
        self::register_ajax_handlers();
    }

    /**
     * 注册所有AJAX处理函数
     */
    private static function register_ajax_handlers()
    {
        $ajax_actions = [
            // 相册相关
            'dhs_get_album_list' => 'get_album_list',
            'dhs_create_album' => 'create_album',
            'dhs_get_album_details' => 'get_album_details',
            'update_album_settings' => 'update_album_settings',
            'delete_album' => 'delete_album',
            'update_album_cover' => 'update_album_cover',
            
            // 分类相关
            'dhs_get_category_list' => 'get_category_list',
            'dhs_create_category' => 'create_category',
            'get_category_details' => 'get_category_details',
            'edit_category' => 'edit_category',
            'delete_category' => 'delete_category',
            
            // 文件上传相关
            'dhs_submit_file' => 'submit_file',
            'generate_thumbnails' => 'generate_thumbnails',
            'get_thumbnail_progress' => 'get_thumbnail_progress',
            'upload_thumbnail_image' => 'upload_thumbnail_image',
            'associate_file' => 'associate_file',
            'check_file_exists' => 'check_file_exists',
            
            // 图片相关
            'get_image_details' => 'get_image_details',
            'load_more_images' => 'load_more_images',
            'delete_images' => 'delete_images',
            'update_image_tags' => 'update_image_tags',
            'delete_image_tag' => 'delete_image_tag',
            'apply_tag_to_all' => 'apply_tag_to_all',
            
            // 收藏和喜欢
            'get_user_favorites' => 'get_user_favorites',
            'update_favorite_selection' => 'update_favorite_selection',
            'create_new_favorite' => 'create_new_favorite',
            'edit_favorite' => 'edit_favorite',
            'get_favorite_details' => 'get_favorite_details',
            'dhs_delete_favorite' => 'delete_favorite',
            'update_like_status' => 'update_like_status',
            'check_like_status' => 'check_like_status',
            'load_more_liked_images' => 'load_more_liked_images',
            
            // 管理功能
            'verify_user_password' => 'verify_user_password',
            'delete_album_entries' => 'delete_album_entries',
            'append_timestamp_to_files' => 'append_timestamp_to_files',
            'delete_thumbnails_folder' => 'delete_thumbnails_folder',
            'process_files_and_insert' => 'process_files_and_insert',
            
            // 权限和公开
            'check_favorite_public_status' => 'check_favorite_public_status',
            'make_favorite_public_and_share' => 'make_favorite_public_and_share',
        ];

        foreach ($ajax_actions as $action => $method) {
            add_action("wp_ajax_{$action}", [__CLASS__, $method]);
            
            // 某些操作也允许未登录用户访问
            $public_actions = [
                'dhs_get_album_list',
                'dhs_get_category_list', 
                'dhs_submit_file',
                'get_image_details',
                'check_file_exists',
                'load_more_images',
                'delete_images',
                'load_more_liked_images'
            ];
            
            if (in_array($action, $public_actions)) {
                add_action("wp_ajax_nopriv_{$action}", [__CLASS__, $method]);
            }
        }
    }

    /**
     * 基础安全验证
     */
    private static function verify_security($check_nonce = true, $check_user = false)
    {
        if ($check_nonce) {
            if (!isset($_POST['_wpnonce']) && !isset($_POST['_ajax_nonce'])) {
                wp_send_json_error(['message' => __('安全验证失败：缺少nonce', 'dhs-tuku')]);
            }
            
            $nonce = $_POST['_wpnonce'] ?? $_POST['_ajax_nonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'dhs_nonce')) {
                wp_send_json_error(['message' => __('安全验证失败：nonce无效', 'dhs-tuku')]);
            }
        }
        
        if ($check_user && !is_user_logged_in()) {
            wp_send_json_error(['message' => __('请先登录', 'dhs-tuku')]);
        }
    }

    /**
     * 获取相册列表
     */
    public static function get_album_list()
    {
        self::verify_security(false);
        
        try {
            $albums = DHS_Tuku_Cache::get_albums();
            
            if (empty($albums)) {
                wp_send_json_error(['message' => __('没有找到相册', 'dhs-tuku')]);
            }
            
            wp_send_json_success($albums);
            
        } catch (Exception $e) {
            error_log('DHS Tuku Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('获取相册列表失败', 'dhs-tuku')]);
        }
    }

    /**
     * 创建相册
     */
    public static function create_album()
    {
        self::verify_security(true, true);
        
        $album_name = sanitize_text_field($_POST['album'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        
        // 验证输入
        $validation = DHS_Tuku_Validator::validate_album_name($album_name);
        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()]);
        }
        
        $validation = DHS_Tuku_Validator::validate_category_id($category_id);
        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()]);
        }
        
        try {
            global $wpdb;
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'dhs_gallery_albums',
                [
                    'album_name' => $album_name,
                    'category_id' => $category_id,
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%d', '%d', '%s']
            );
            
            if ($result) {
                // 清理相册缓存
                DHS_Tuku_Cache::clear_album_cache();
                
                wp_send_json_success([
                    'album_id' => $wpdb->insert_id,
                    'message' => __('相册创建成功', 'dhs-tuku')
                ]);
            } else {
                throw new Exception($wpdb->last_error);
            }
            
        } catch (Exception $e) {
            error_log('DHS Tuku Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('创建相册失败', 'dhs-tuku')]);
        }
    }

    /**
     * 文件上传处理
     */
    public static function submit_file()
    {
        self::verify_security(true, true);
        
        // 检查上传权限
        if (!DHS_Tuku_Validator::validate_user_permission('upload')) {
            wp_send_json_error(['message' => __('权限不足', 'dhs-tuku')]);
        }
        
        if (empty($_FILES['files'])) {
            wp_send_json_error(['message' => __('没有文件上传', 'dhs-tuku')]);
        }
        
        $files = $_FILES['files'];
        $album_id = intval($_POST['album'] ?? 0);
        
        if (!$album_id) {
            wp_send_json_error(['message' => __('无效的相册ID', 'dhs-tuku')]);
        }
        
        try {
            $upload_dir = wp_upload_dir();
            $tuku_dir = $upload_dir['basedir'] . '/tuku/' . $album_id;
            
            if (!file_exists($tuku_dir)) {
                wp_mkdir_p($tuku_dir);
            }
            
            $file_data = [];
            $errors = [];
            
            foreach ($files['name'] as $index => $file_name) {
                $file = [
                    'name' => $file_name,
                    'tmp_name' => $files['tmp_name'][$index],
                    'size' => $files['size'][$index],
                    'type' => $files['type'][$index]
                ];
                
                // 验证文件
                $validation = DHS_Tuku_Validator::validate_upload_file($file);
                if (is_wp_error($validation)) {
                    $errors[] = $file_name . ': ' . $validation->get_error_message();
                    continue;
                }
                
                // 处理文件名
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);
                $file_base_name = sanitize_file_name($file_base_name);
                
                $timestamp = time();
                $name = $file_base_name . '_' . $timestamp;
                $new_file_name = $name . '.' . $file_extension;
                $target_file = $tuku_dir . '/' . $new_file_name;
                
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    $file_data[] = [
                        'file_path' => '/wp-content/uploads/tuku/' . $album_id . '/' . $new_file_name,
                        'file_type' => $file_extension,
                        'upload_date' => current_time('mysql')
                    ];
                } else {
                    $errors[] = $file_name . ': ' . __('文件移动失败', 'dhs-tuku');
                }
            }
            
            if (!empty($file_data)) {
                global $wpdb;
                
                $result = $wpdb->insert(
                    $wpdb->prefix . 'dhs_gallery_images',
                    [
                        'name' => $name,
                        'file_data' => wp_json_encode($file_data),
                        'album_id' => $album_id,
                        'status' => 'active',
                    ],
                    ['%s', '%s', '%d', '%s']
                );
                
                if ($result) {
                    // 清理缓存
                    DHS_Tuku_Cache::clear_album_cache($album_id);
                    
                    $message = __('文件上传成功', 'dhs-tuku');
                    if (!empty($errors)) {
                        $message .= ' (' . __('部分文件失败', 'dhs-tuku') . ')';
                    }
                    
                    wp_send_json_success([
                        'message' => $message,
                        'errors' => $errors
                    ]);
                } else {
                    throw new Exception($wpdb->last_error);
                }
            } else {
                wp_send_json_error([
                    'message' => __('所有文件上传失败', 'dhs-tuku'),
                    'errors' => $errors
                ]);
            }
            
        } catch (Exception $e) {
            error_log('DHS Tuku Upload Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('文件上传处理失败', 'dhs-tuku')]);
        }
    }

    // 其他AJAX方法将在后续添加...
    // 为了保持文件大小合理，这里只展示核心方法的重构版本
}
