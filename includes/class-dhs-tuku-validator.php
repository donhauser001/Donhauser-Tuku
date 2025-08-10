<?php
/**
 * DHS图库数据验证类
 */

class DHS_Tuku_Validator
{
    /**
     * 验证相册名称
     */
    public static function validate_album_name($name)
    {
        if (empty($name)) {
            return new WP_Error('empty_name', '相册名称不能为空');
        }
        
        if (strlen($name) > 255) {
            return new WP_Error('name_too_long', '相册名称不能超过255个字符');
        }
        
        // 检查是否包含危险字符
        if (preg_match('/[<>"\']/', $name)) {
            return new WP_Error('invalid_chars', '相册名称包含非法字符');
        }
        
        return true;
    }

    /**
     * 验证上传文件
     */
    public static function validate_upload_file($file)
    {
        $allowed_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'psd' => 'application/octet-stream',
            'ai' => 'application/postscript',
            'svg' => 'image/svg+xml'
        ];
        
        $max_size = 50 * 1024 * 1024; // 50MB
        
        // 检查文件大小
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', '文件大小不能超过50MB');
        }
        
        // 检查文件类型
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($file_ext, $allowed_types)) {
            return new WP_Error('invalid_file_type', '不支持的文件类型');
        }
        
        // 验证MIME类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            return new WP_Error('invalid_mime_type', '文件MIME类型不匹配');
        }
        
        return true;
    }

    /**
     * 验证分类ID
     */
    public static function validate_category_id($category_id)
    {
        if (empty($category_id)) {
            return new WP_Error('empty_category', '分类ID不能为空');
        }
        
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dhs_gallery_categories WHERE id = %d",
            $category_id
        ));
        
        if (!$exists) {
            return new WP_Error('category_not_exists', '指定的分类不存在');
        }
        
        return true;
    }

    /**
     * 验证用户权限
     */
    public static function validate_user_permission($action, $resource_id = null)
    {
        switch ($action) {
            case 'upload':
                return current_user_can('upload_files');
                
            case 'edit_album':
                if (!$resource_id) return false;
                
                global $wpdb;
                $album_owner = $wpdb->get_var($wpdb->prepare(
                    "SELECT created_by FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d",
                    $resource_id
                ));
                
                return (current_user_can('edit_others_posts') || 
                        (is_user_logged_in() && get_current_user_id() == $album_owner));
                
            case 'delete_album':
                if (!$resource_id) return false;
                
                global $wpdb;
                $album_owner = $wpdb->get_var($wpdb->prepare(
                    "SELECT created_by FROM {$wpdb->prefix}dhs_gallery_albums WHERE id = %d",
                    $resource_id
                ));
                
                return (current_user_can('delete_others_posts') || 
                        (is_user_logged_in() && get_current_user_id() == $album_owner));
                
            default:
                return false;
        }
    }

    /**
     * 安全清理HTML
     */
    public static function sanitize_html($html)
    {
        $allowed_tags = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'em' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
        ];
        
        return wp_kses($html, $allowed_tags);
    }
}
