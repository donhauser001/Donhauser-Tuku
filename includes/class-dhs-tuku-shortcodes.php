<?php
/**
 * DHS图库短代码类
 * 处理所有短代码相关功能
 */

class DHS_Tuku_Shortcodes
{
    /**
     * 初始化短代码
     */
    public static function init()
    {
        add_shortcode('dhs_tuku_albums', [__CLASS__, 'albums_shortcode']);
        add_shortcode('dhs_tuku_my_albums', [__CLASS__, 'my_albums_shortcode']);
        add_shortcode('dhs_tuku_album_details', [__CLASS__, 'album_details_shortcode']);
        add_shortcode('dhs_tuku_favorites', [__CLASS__, 'favorites_shortcode']);
        add_shortcode('dhs_tuku_liked_images', [__CLASS__, 'liked_images_shortcode']);
        add_shortcode('dhs_tuku_search', [__CLASS__, 'search_shortcode']);
        add_shortcode('dhs_tuku_categories', [__CLASS__, 'categories_shortcode']);
        add_shortcode('dhs_tuku_favorite_details', [__CLASS__, 'favorite_details_shortcode']);
    }

    /**
     * 相册列表短代码
     */
    public static function albums_shortcode($atts = [])
    {
        global $wpdb;
        
        $atts = shortcode_atts([
            'limit' => -1,
            'category' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        ], $atts);

        $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
        $images_table = $wpdb->prefix . 'dhs_gallery_images';
        $categories_table = $wpdb->prefix . 'dhs_gallery_categories';

        // 构建查询
        $where_clause = '';
        $params = [];
        
        if (!empty($atts['category'])) {
            $where_clause = 'WHERE albums.category_id = %d';
            $params[] = intval($atts['category']);
        }

        $limit_clause = '';
        if ($atts['limit'] > 0) {
            $limit_clause = 'LIMIT %d';
            $params[] = intval($atts['limit']);
        }

        $query = "
            SELECT albums.id, albums.album_name AS name, albums.created_at AS date, 
                   albums.created_by AS author, albums.category_id, albums.cover_image, 
                   images.file_data
            FROM {$albums_table} AS albums
            LEFT JOIN {$images_table} AS images ON albums.cover_image = images.id
            {$where_clause}
            ORDER BY albums.{$atts['orderby']} {$atts['order']}
            {$limit_clause}
        ";

        if (!empty($params)) {
            $albums = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $albums = $wpdb->get_results($query);
        }

        if (empty($albums)) {
            return '<p>当前没有可显示的相册</p>';
        }

        try {
            return self::render_albums($albums, $categories_table);
        } catch (Exception $e) {
            DHS_Tuku_Logger::error('Albums shortcode error: ' . $e->getMessage());
            return '<p>' . __('加载相册列表失败', 'dhs-tuku') . '</p>';
        }
    }



    /**
     * 获取相册缩略图
     */
    private static function get_album_thumbnail($album_id, $base_url)
    {
        // 查找第一个缩略图作为封面
        global $wpdb;
        $images_table = $wpdb->prefix . 'dhs_gallery_images';
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT name FROM {$images_table} WHERE album_id = %d ORDER BY id ASC LIMIT 5",
            $album_id
        ), ARRAY_A);

        if (!empty($images)) {
            $upload_dir = wp_upload_dir();
            foreach ($images as $image) {
                $thumbnail_path = "{$base_url}/tuku/{$album_id}/thumbnails/{$image['name']}_thumbnail.jpg";
                $file_path = "{$upload_dir['basedir']}/tuku/{$album_id}/thumbnails/{$image['name']}_thumbnail.jpg";
                
                if (file_exists($file_path)) {
                    return esc_url($thumbnail_path);
                }
            }
        }

        return DHS_TUKU_ASSETS_URL . 'images/default-fengmian.png';
    }

    /**
     * 我的相册短代码
     */
    public static function my_albums_shortcode($atts = [])
    {
        if (!is_user_logged_in()) {
            return '<p>请先登录以查看您的图库。</p>';
        }

        $atts = shortcode_atts([
            'limit' => -1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ], $atts);

        global $wpdb;
        $current_user_id = get_current_user_id();
        $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
        $images_table = $wpdb->prefix . 'dhs_gallery_images';
        $categories_table = $wpdb->prefix . 'dhs_gallery_categories';

        $limit_clause = '';
        $params = [$current_user_id];
        
        if ($atts['limit'] > 0) {
            $limit_clause = 'LIMIT %d';
            $params[] = intval($atts['limit']);
        }

        $query = "
            SELECT albums.id, albums.album_name AS name, albums.created_at AS date, 
                   albums.created_by AS author, albums.category_id, albums.cover_image, 
                   images.file_data
            FROM {$albums_table} AS albums
            LEFT JOIN {$images_table} AS images ON albums.cover_image = images.id
            WHERE albums.created_by = %d
            ORDER BY albums.{$atts['orderby']} {$atts['order']}
            {$limit_clause}
        ";

        $albums = $wpdb->get_results($wpdb->prepare($query, ...$params));

        if (empty($albums)) {
            return '<p>您当前没有创建任何相册。</p>';
        }

        return self::render_albums($albums, $categories_table);
    }

    /**
     * 渲染相册列表
     */
    private static function render_albums($albums, $categories_table = null)
    {
        global $wpdb;
        
        // 初始化相册数组
        $albums_array = [];
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];

        foreach ($albums as $album) {
            // 处理封面图片逻辑
            if (!empty($album->cover_image)) {
                $cover_image = esc_url($base_url . $album->cover_image);
            } else {
                $cover_image = self::get_album_thumbnail($album->id, $base_url);
            }

            // 获取相册创建者名称
            $author_name = get_the_author_meta('display_name', $album->author);
            $formatted_date = date('Y·m·d', strtotime($album->date));

            // 查询分类名称
            $category_name = '未分类';
            if ($categories_table && $album->category_id) {
                $category_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT category_name FROM {$categories_table} WHERE id = %d",
                    $album->category_id
                )) ?: '未分类';
            }

            // 动态生成相册链接
            $link = add_query_arg('album_id', $album->id, site_url('/tuku/album-details/'));

            // 构建相册数据数组
            $albums_array[] = [
                'id' => $album->id,
                'name' => esc_html($album->name),
                'cover_image' => $cover_image,
                'link' => esc_url($link),
                'author' => esc_html($author_name),
                'date' => esc_html($formatted_date),
                'category' => esc_html($category_name),
                'category_id' => $album->category_id ?? 0,
            ];
        }

        // 输出相册数据到模板
        ob_start();
        include DHS_TUKU_PLUGIN_DIR . 'templates/gallery-albums-template.php';
        return ob_get_clean();
    }

    /**
     * 分类相册短代码
     */
    public static function categories_shortcode($atts = [])
    {
        global $wpdb;
        $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

        if ($category_id === 0) {
            return '<p>' . __('未指定分类。', 'dhs-tuku') . '</p>';
        }

        try {
            $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
            $categories_table = $wpdb->prefix . 'dhs_gallery_categories';

            // 获取当前分类及其子分类
            $categories = $wpdb->get_results($wpdb->prepare("
                SELECT id, category_name 
                FROM {$categories_table} 
                WHERE id = %d OR parent_id = %d
            ", $category_id, $category_id));

            if (empty($categories)) {
                return '<p>' . __('未找到指定分类。', 'dhs-tuku') . '</p>';
            }

            $category_ids = array_map(function ($category) {
                return $category->id;
            }, $categories);

            $albums = $wpdb->get_results("
                SELECT albums.id, albums.album_name AS name, albums.created_at AS date, 
                       albums.created_by AS author, albums.category_id, albums.cover_image
                FROM {$albums_table} AS albums
                WHERE albums.category_id IN (" . implode(',', array_map('intval', $category_ids)) . ")
                ORDER BY albums.created_at DESC
            ");

            if (empty($albums)) {
                return '<p>' . __('该分类下没有找到任何相册。', 'dhs-tuku') . '</p>';
            }

            return self::render_albums($albums, $categories_table);

        } catch (Exception $e) {
            DHS_Tuku_Logger::error('Categories shortcode error: ' . $e->getMessage());
            return '<p>' . __('加载分类相册时发生错误。', 'dhs-tuku') . '</p>';
        }
    }

    /**
     * 相册详情短代码
     */
    public static function album_details_shortcode($atts = [])
    {
        global $wpdb;
        
        // 输出菜单
        $output = dhs_tuku_menu();
        
        $album_id = isset($_GET['album_id']) ? intval($_GET['album_id']) : 0;
        if ($album_id === 0) {
            return $output . '<p>' . __('相册ID无效或未提供。', 'dhs-tuku') . '</p>';
        }

        try {
            $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
            $images_table = $wpdb->prefix . 'dhs_gallery_images';

            // 获取相册信息
            $album = $wpdb->get_row($wpdb->prepare("SELECT * FROM $albums_table WHERE id = %d", $album_id));
            if (!$album) {
                return '<p>' . __('未找到相册。', 'dhs-tuku') . '</p>';
            }

            // 获取图片数据
            $images_per_page = 20;
            
            // 使用缓存获取图片
            $images = DHS_Tuku_Cache::get_images($album_id, $images_per_page, 0);
            $total_images = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $images_table WHERE album_id = %d AND status = 'active'", $album_id));
            $has_more = $total_images > $images_per_page;

            if (empty($images)) {
                return $output . '<p>' . __('此相册中没有图片。', 'dhs-tuku') . '</p>';
            }

            ob_start();
            include DHS_TUKU_PLUGIN_DIR . 'templates/album-template.php';
            return $output . ob_get_clean();

        } catch (Exception $e) {
            DHS_Tuku_Logger::error('Album details shortcode error: ' . $e->getMessage());
            return '<p>' . __('加载相册详情时发生错误。', 'dhs-tuku') . '</p>';
        }
    }

    /**
     * 收藏夹短代码
     */
    public static function favorites_shortcode($atts = [])
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('请先登录以查看您的收藏夹。', 'dhs-tuku') . '</p>';
        }

        global $wpdb;
        $current_user_id = get_current_user_id();
        $favorites_table = $wpdb->prefix . 'dhs_gallery_favorites';

        try {
            $favorites = $wpdb->get_results($wpdb->prepare("
                SELECT id, name, created_at AS date, user_id AS author, is_public
                FROM {$favorites_table}
                WHERE user_id = %d
                ORDER BY created_at DESC
            ", $current_user_id));

            if (empty($favorites)) {
                return '<p>' . __('您当前没有收藏任何内容。', 'dhs-tuku') . '</p>';
            }

            $favorites_array = [];
            $images_table = $wpdb->prefix . 'dhs_gallery_images';
            $favorite_images_table = $wpdb->prefix . 'dhs_gallery_favorite_images';
            
            foreach ($favorites as $favorite) {
                // 获取收藏夹中的图片缩略图（按图片ID去重）
                $thumbnails = $wpdb->get_results($wpdb->prepare("
                    SELECT i.id, i.name, i.file_data, i.album_id
                    FROM {$favorite_images_table} fi
                    LEFT JOIN {$images_table} i ON fi.image_id = i.id
                    WHERE fi.favorite_id = %d AND i.status = 'active'
                    GROUP BY i.id
                    ORDER BY fi.created_at DESC
                    LIMIT 3
                ", $favorite->id));
                
                $thumbnail_array = [];
                foreach ($thumbnails as $thumb) {
                    if ($thumb->file_data && $thumb->album_id && $thumb->name) {
                        // 尝试不同的缩略图文件名格式
                        $possible_names = [
                            $thumb->name . '_thumbnail.jpg',
                            $thumb->name . '_thumbnail.png',
                            $thumb->name . '.jpg',
                            $thumb->name . '.png'
                        ];
                        
                        $thumbnail_url = DHS_TUKU_PLUGIN_URL . 'assets/images/default-thumbnail.jpg'; // 默认图片
                        
                        foreach ($possible_names as $filename) {
                            $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $thumb->album_id . '/thumbnails/' . $filename;
                            if (file_exists($thumbnail_path)) {
                                $thumbnail_url = dhs_tuku_get_secure_url('/wp-content/uploads/tuku/' . $thumb->album_id . '/thumbnails/' . rawurlencode($filename));
                                break;
                            }
                        }
                        
                        $thumbnail_array[] = [
                            'thumbnail' => $thumbnail_url,
                            'image_id' => $thumb->id,
                            'name' => $thumb->name,
                            'album_id' => $thumb->album_id
                        ];
                    }
                }
                
                // 如果没有缩略图，使用默认图片
                $default_image = DHS_TUKU_PLUGIN_URL . 'assets/images/default-favorite.jpg';
                if (!file_exists(DHS_TUKU_PLUGIN_DIR . 'assets/images/default-favorite.jpg')) {
                    $default_image = DHS_TUKU_PLUGIN_URL . 'assets/images/default-thumbnail.jpg';
                }
                
                $favorites_array[] = [
                    'id' => $favorite->id,
                    'name' => esc_html($favorite->name),
                    'author' => esc_html(get_the_author_meta('display_name', $favorite->author)),
                    'date' => esc_html(date('Y·m·d', strtotime($favorite->date))),
                    'is_public' => $favorite->is_public,
                    'link' => add_query_arg('favorite_id', $favorite->id, site_url('/tuku/favorite-details/')),
                    'thumbnails' => $thumbnail_array,
                    'image_path' => $default_image
                ];
            }

            $favorites_count = count($favorites_array);

            ob_start();
            include DHS_TUKU_PLUGIN_DIR . 'templates/favorites-template.php';
            return ob_get_clean();

        } catch (Exception $e) {
            DHS_Tuku_Logger::error('Favorites shortcode error: ' . $e->getMessage());
            return '<p>' . __('加载收藏夹时发生错误。', 'dhs-tuku') . '</p>';
        }
    }

    /**
     * 收藏夹详情短代码
     */
    public static function favorite_details_shortcode($atts = [])
    {
        global $wpdb;
        
        // 输出菜单
        $output = dhs_tuku_menu();
        
        $favorite_id = isset($_GET['favorite_id']) ? intval($_GET['favorite_id']) : 0;
        $current_user_id = get_current_user_id();

        if (!$favorite_id) {
            return $output . '<p>' . __('无效的收藏夹。', 'dhs-tuku') . '</p>';
        }

        try {
            $favorites_table = $wpdb->prefix . 'dhs_gallery_favorites';
            $images_table = $wpdb->prefix . 'dhs_gallery_images';
            $favorites_images_table = $wpdb->prefix . 'dhs_gallery_favorite_images';

            // 查询收藏夹信息
            $favorite = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$favorites_table} 
                WHERE id = %d
            ", $favorite_id));

            if (!$favorite) {
                return $output . '<p>' . __('收藏夹不存在。', 'dhs-tuku') . '</p>';
            }

            // 权限检查
            if ($favorite->is_public == 0 && $favorite->user_id != $current_user_id) {
                return $output . '<p>' . __('此收藏夹为私密状态，您无权访问。', 'dhs-tuku') . '</p>';
            }

            // 查询收藏夹内的图片
            $images = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT img.* FROM {$images_table} img
                JOIN {$favorites_images_table} fi ON img.id = fi.image_id
                WHERE fi.favorite_id = %d AND img.status = 'active'
                ORDER BY fi.created_at DESC
            ", $favorite_id));

            if (empty($images)) {
                return $output . '<p>' . __('此收藏夹内没有图片。', 'dhs-tuku') . '</p>';
            }

            ob_start();
            include DHS_TUKU_PLUGIN_DIR . 'templates/favorite-details-template.php';
            return $output . ob_get_clean();

        } catch (Exception $e) {
            DHS_Tuku_Logger::error('Favorite details shortcode error: ' . $e->getMessage());
            return '<p>' . __('加载收藏夹详情时发生错误。', 'dhs-tuku') . '</p>';
        }
    }

    /**
     * 喜欢的图片短代码
     */
    public static function liked_images_shortcode($atts = [])
    {
        // 输出菜单
        $output = dhs_tuku_menu();
        
        if (!is_user_logged_in()) {
            return $output . '<p>' . __('您需要登录才能查看您的喜欢图片。', 'dhs-tuku') . '</p>';
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $likes_table = $wpdb->prefix . 'dhs_gallery_likes';
        $images_table = $wpdb->prefix . 'dhs_gallery_images';

        try {
            // 获取总喜欢的图片数量
            $total_liked_images = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $likes_table WHERE user_id = %d",
                $user_id
            ));

            $user_name = get_the_author_meta('display_name', $user_id);
            $images_per_page = 20;

            // 获取喜欢的图片
            $liked_images = $wpdb->get_results($wpdb->prepare(
                "SELECT img.*, img.album_id FROM $images_table img 
                 JOIN $likes_table likes ON img.id = likes.image_id 
                 WHERE likes.user_id = %d AND img.status = 'active'
                 ORDER BY likes.created_at DESC 
                 LIMIT %d",
                $user_id,
                $images_per_page
            ));

            if (empty($liked_images)) {
                return $output . '<p>' . __('您还没有喜欢任何图片。', 'dhs-tuku') . '</p>';
            }

            $has_more = $total_liked_images > $images_per_page;

            ob_start();
            include DHS_TUKU_PLUGIN_DIR . 'templates/liked-images-template.php';
            return $output . ob_get_clean();

        } catch (Exception $e) {
            DHS_Tuku_Logger::error('Liked images shortcode error: ' . $e->getMessage());
            return '<p>' . __('加载喜欢图片时发生错误。', 'dhs-tuku') . '</p>';
        }
    }

    /**
     * 搜索短代码
     */
    public static function search_shortcode($atts = [])
    {
        global $wpdb;
        
        // 输出菜单
        $output = dhs_tuku_menu();
        
        $search_query = isset($_GET['searchtuku']) ? sanitize_text_field($_GET['searchtuku']) : '';

        if (empty($search_query)) {
            return $output . '<p>' . __('请输入搜索关键词。', 'dhs-tuku') . '</p>';
        }

        try {
            $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
            $image_tag_table = $wpdb->prefix . 'dhs_gallery_image_tag';
            $images_table = $wpdb->prefix . 'dhs_gallery_images';
            $albums_table = $wpdb->prefix . 'dhs_gallery_albums';
            $users_table = $wpdb->prefix . 'users';

            $search_results = [];

            // 1. 搜索相册名称（优先级最高）
            $albums_from_name = $wpdb->get_results($wpdb->prepare("
                SELECT id, album_name FROM {$albums_table} 
                WHERE album_name LIKE %s
            ", '%' . $search_query . '%'));

            if (!empty($albums_from_name)) {
                $album_ids = array_map(function ($album) {
                    return $album->id;
                }, $albums_from_name);

                // 获取这些相册中的所有图片
                $image_ids_from_albums = $wpdb->get_results("
                    SELECT DISTINCT id, 'album' AS match_type 
                    FROM {$images_table} 
                    WHERE album_id IN (" . implode(',', array_map('intval', $album_ids)) . ") 
                      AND status = 'active'
                ");

                foreach ($image_ids_from_albums as $image) {
                    $search_results[$image->id] = ['priority' => 1, 'match_type' => $image->match_type];
                }
            }

            // 2. 搜索标签
            $tags = $wpdb->get_results($wpdb->prepare("
                SELECT id FROM {$tags_table} WHERE tag_name LIKE %s
            ", '%' . $search_query . '%'));

            if (!empty($tags)) {
                $tag_ids = array_map(function ($tag) {
                    return $tag->id;
                }, $tags);

                $image_ids_from_tags = $wpdb->get_results("
                    SELECT DISTINCT image_id, 'tag' AS match_type 
                    FROM {$image_tag_table} 
                    WHERE tag_id IN (" . implode(',', array_map('intval', $tag_ids)) . ")
                ");

                foreach ($image_ids_from_tags as $image) {
                    if (!isset($search_results[$image->image_id])) {
                        $search_results[$image->image_id] = ['priority' => 2, 'match_type' => $image->match_type];
                    }
                }
            }

            // 3. 搜索图片名称
            $image_ids_from_names = $wpdb->get_results($wpdb->prepare("
                SELECT id, 'name' AS match_type 
                FROM {$images_table} 
                WHERE name LIKE %s AND status = 'active'
            ", '%' . $search_query . '%'));

            foreach ($image_ids_from_names as $image) {
                if (!isset($search_results[$image->id])) {
                    $search_results[$image->id] = ['priority' => 3, 'match_type' => $image->match_type];
                }
            }

            if (empty($search_results)) {
                return $output . '<p>' . sprintf(__('没有找到与"%s"相关的图片或相册。', 'dhs-tuku'), esc_html($search_query)) . '</p>';
            }

            // 生成搜索结果说明
            $match_types = array_unique(array_column($search_results, 'match_type'));
            $search_info = [];
            if (in_array('album', $match_types)) {
                $search_info[] = '相册名称';
            }
            if (in_array('tag', $match_types)) {
                $search_info[] = '标签';
            }
            if (in_array('name', $match_types)) {
                $search_info[] = '图片名称';
            }
            
            $search_description = '';
            if (!empty($search_info)) {
                $search_description = '<div class="search-info" style="margin-bottom: 20px; padding: 10px; background-color: #f0f8ff; border-left: 4px solid #0073aa; font-size: 14px;">';
                $search_description .= sprintf(__('找到了 %d 张与"%s"相关的图片，匹配方式：%s', 'dhs-tuku'), 
                    count($search_results), 
                    esc_html($search_query), 
                    implode('、', $search_info)
                );
                $search_description .= '</div>';
            }

            // 按优先级排序
            uasort($search_results, function ($a, $b) {
                return $a['priority'] - $b['priority'];
            });

            // 限制结果数量
            $limited_image_ids = array_slice(array_keys($search_results), 0, 150);

            // 获取图片信息
            $images = $wpdb->get_results("
                SELECT id, name, file_data, album_id 
                FROM {$images_table} 
                WHERE id IN (" . implode(',', array_map('intval', $limited_image_ids)) . ")
                  AND status = 'active'
            ");

            ob_start();
            include DHS_TUKU_PLUGIN_DIR . 'templates/search-page-template.php';
            return $output . $search_description . ob_get_clean();

        } catch (Exception $e) {
            DHS_Tuku_Logger::error('Search shortcode error: ' . $e->getMessage());
            return '<p>' . __('搜索时发生错误。', 'dhs-tuku') . '</p>';
        }
    }
}
