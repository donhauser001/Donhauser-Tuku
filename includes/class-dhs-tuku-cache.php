<?php
/**
 * DHS图库缓存管理类
 */

class DHS_Tuku_Cache
{
    const CACHE_GROUP = 'dhs_tuku';
    const DEFAULT_EXPIRATION = 3600; // 1小时

    /**
     * 获取缓存
     */
    public static function get($key, $group = self::CACHE_GROUP)
    {
        return wp_cache_get($key, $group);
    }

    /**
     * 设置缓存
     */
    public static function set($key, $data, $group = self::CACHE_GROUP, $expiration = self::DEFAULT_EXPIRATION)
    {
        return wp_cache_set($key, $data, $group, $expiration);
    }

    /**
     * 删除缓存
     */
    public static function delete($key, $group = self::CACHE_GROUP)
    {
        return wp_cache_delete($key, $group);
    }

    /**
     * 清空组缓存
     */
    public static function flush_group($group = self::CACHE_GROUP)
    {
        wp_cache_flush_group($group);
    }

    /**
     * 获取相册缓存
     */
    public static function get_albums($args = [])
    {
        $cache_key = 'albums_' . md5(serialize($args));
        $cached = self::get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }

        // 如果缓存不存在，从数据库获取并缓存
        global $wpdb;
        $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
        
        $where_conditions = ['1=1'];
        $params = [];
        
        if (!empty($args['user_id'])) {
            $where_conditions[] = 'created_by = %d';
            $params[] = intval($args['user_id']);
        }
        
        if (!empty($args['category_id'])) {
            $where_conditions[] = 'category_id = %d';
            $params[] = intval($args['category_id']);
        }
        
        $limit_clause = '';
        if (!empty($args['limit'])) {
            $limit_clause = 'LIMIT %d';
            $params[] = intval($args['limit']);
        }
        
        $order_clause = 'ORDER BY created_at DESC';
        if (!empty($args['orderby'])) {
            $order_clause = sprintf('ORDER BY %s %s', 
                sanitize_sql_orderby($args['orderby']), 
                (!empty($args['order']) && strtoupper($args['order']) === 'ASC') ? 'ASC' : 'DESC'
            );
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT id, album_name, created_by, created_at, cover_image, category_id, description
            FROM {$albums_table}
            WHERE {$where_clause}
            {$order_clause}
            {$limit_clause}
        ";
        
        if (!empty($params)) {
            $albums = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $albums = $wpdb->get_results($query);
        }
        
        // 缓存结果
        self::set($cache_key, $albums, self::CACHE_GROUP, 1800); // 30分钟缓存
        
        return $albums;
    }

    /**
     * 获取图片缓存
     */
    public static function get_images($album_id, $limit = 20, $offset = 0)
    {
        $cache_key = "images_{$album_id}_{$limit}_{$offset}";
        $cached = self::get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $images_table = $wpdb->prefix . 'dhs_gallery_images';
        
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$images_table} 
             WHERE album_id = %d AND status = 'active' 
             ORDER BY id DESC 
             LIMIT %d OFFSET %d",
            $album_id, $limit, $offset
        ));
        
        // 缓存结果15分钟
        self::set($cache_key, $images, self::CACHE_GROUP, 900);
        
        return $images;
    }

    /**
     * 清理相册相关缓存
     */
    public static function clear_album_cache($album_id = null)
    {
        if ($album_id) {
            // 清理特定相册的缓存
            global $wpdb;
            $cache_keys = $wpdb->get_col($wpdb->prepare(
                "SELECT CONCAT('images_', %d, '_', limit_val, '_', offset_val) 
                 FROM (SELECT 20 as limit_val, 0 as offset_val) as limits",
                $album_id
            ));
            
            foreach ($cache_keys as $key) {
                self::delete($key);
            }
        }
        
        // 清理相册列表缓存
        self::delete_pattern('albums_');
    }

    /**
     * 按模式删除缓存
     */
    private static function delete_pattern($pattern)
    {
        // WordPress默认不支持模式删除，这里可以使用Redis或Memcached扩展
        // 暂时使用组清理
        self::flush_group();
    }
}
