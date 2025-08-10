<?php

class DHS_Tuku_Init
{
    /**
     * 数据库版本号
     */
    private static $db_version = '1.5'; // 更新版本号，增加索引和字段

    /**
     * 插件激活时执行的操作
     */
    public static function activate()
    {
        global $wpdb;

        // 获取数据库字符集和排序规则
        $charset_collate = $wpdb->get_charset_collate();

        // 检查并创建或更新表结构
        self::create_or_update_tables($wpdb, $charset_collate);

        // 更新数据库版本
        update_option('dhs_gallery_db_version', self::$db_version);
    }

    /**
     * 创建或更新表结构
     */
    private static function create_or_update_tables($wpdb, $charset_collate)
    {
        // 引入 dbDelta 所需的文件
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 获取已安装的数据库版本
        $installed_ver = get_option('dhs_gallery_db_version');

        // 如果数据库版本不一致，执行表更新
        if ($installed_ver != self::$db_version) {
            // 创建图片表
            $table_name = $wpdb->prefix . 'dhs_gallery_images';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,   -- 文件名（不带扩展名）
                file_data LONGTEXT NOT NULL,  -- 存储文件的路径、类型和上传日期，JSON格式
                album_id BIGINT(20) UNSIGNED NULL,
                status ENUM('active', 'deleted') DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY album_id (album_id),
                KEY status (status),
                KEY name (name),
                KEY created_at (created_at)
            ) $charset_collate;";
            dbDelta($sql);

            // 创建图片元数据表
            $table_name_meta = $wpdb->prefix . 'dhs_gallery_imagemeta';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_meta (
                meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                image_id BIGINT(20) UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (meta_id)
            ) $charset_collate;";
            dbDelta($sql);

            // 创建相册表
            $table_name_album = $wpdb->prefix . 'dhs_gallery_albums';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_album (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            album_name VARCHAR(255) NOT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            cover_image VARCHAR(2083) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,  -- 存储封面图片的 URL
            category_id BIGINT(20) UNSIGNED NOT NULL,  -- 分类 ID 列
            description TEXT,  -- 新增的描述字段
            PRIMARY KEY (id),
            KEY category_id (category_id)  -- 添加索引，便于查询
        ) $charset_collate;";
            dbDelta($sql);

            // 创建相册元数据表
            $table_name_albummeta = $wpdb->prefix . 'dhs_gallery_albummeta';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_albummeta (
                meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                album_id BIGINT(20) UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (meta_id)
            ) $charset_collate;";
            dbDelta($sql);

            // 创建标签表
            $table_name_tag = $wpdb->prefix . 'dhs_gallery_tags';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_tag (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                tag_name VARCHAR(255) NOT NULL,
                UNIQUE KEY (tag_name),
                PRIMARY KEY (id)
            ) $charset_collate;";
            dbDelta($sql);

            // 创建图片与标签的关联表
            $table_name_image_tag = $wpdb->prefix . 'dhs_gallery_image_tag';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_image_tag (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                image_id BIGINT(20) UNSIGNED NOT NULL,
                tag_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY (image_id, tag_id)
            ) $charset_collate;";
            dbDelta($sql);

            // 创建权限管理表
            $table_name_permissions = $wpdb->prefix . 'dhs_gallery_permissions';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_permissions (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                resource_type ENUM('album', 'image') NOT NULL,
                resource_id BIGINT(20) UNSIGNED NOT NULL,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                permission ENUM('view', 'edit', 'delete') NOT NULL,
                expires_at DATETIME NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";
            dbDelta($sql);

            // 创建日志表
            $table_name_logs = $wpdb->prefix . 'dhs_gallery_logs';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_logs (
                log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                resource_type ENUM('album', 'image', 'tag') NOT NULL,
                resource_id BIGINT(20) UNSIGNED NOT NULL,
                action ENUM('create', 'update', 'delete', 'view', 'upload') NOT NULL,
                log_message TEXT NOT NULL,
                log_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                PRIMARY KEY (log_id)
            ) $charset_collate;";
            dbDelta($sql);

            // 创建相册分类表
            $table_name_category = $wpdb->prefix . 'dhs_gallery_categories';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_category (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,   -- 主键，自动递增
                category_name VARCHAR(255) NOT NULL,              -- 分类名称，不能为空
                parent_id BIGINT(20) UNSIGNED DEFAULT NULL,       -- 父类别 ID，允许为空
                description TEXT DEFAULT NULL,                    -- 分类描述，非必填
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- 创建时间
                PRIMARY KEY (id),
                FOREIGN KEY (parent_id) REFERENCES $table_name_category(id) ON DELETE SET NULL -- 自关联外键
            ) $charset_collate;";
            dbDelta($sql);

            // 创建收藏表
            $table_name_favorites = $wpdb->prefix . 'dhs_gallery_favorites';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_favorites (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                cover_url VARCHAR(2083) NULL,  -- 封面字段，用于存储网址
                is_public BOOLEAN NOT NULL DEFAULT FALSE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
            ) $charset_collate;";
            dbDelta($sql);

            // 创建收藏夹与图片的中间表
            $table_name_favorite_images = $wpdb->prefix . 'dhs_gallery_favorite_images';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_favorite_images (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                favorite_id BIGINT(20) UNSIGNED NOT NULL,
                image_id BIGINT(20) UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (favorite_id) REFERENCES {$wpdb->prefix}dhs_gallery_favorites(id) ON DELETE CASCADE,
                FOREIGN KEY (image_id) REFERENCES {$wpdb->prefix}dhs_gallery_images(id) ON DELETE CASCADE
            ) $charset_collate;";
            dbDelta($sql);

            // 创建喜欢表
            $table_name_likes = $wpdb->prefix . 'dhs_gallery_likes';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name_likes (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            image_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_image (user_id, image_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
            FOREIGN KEY (image_id) REFERENCES {$wpdb->prefix}dhs_gallery_images(id) ON DELETE CASCADE
        ) $charset_collate;";
            dbDelta($sql);

            // 更新数据库版本
            update_option('dhs_gallery_db_version', self::$db_version);
        }
    }

    /**
     * 插件停用时执行的操作
     */
    public static function deactivate()
    {
        // 插件停用时，不删除数据，仅做停用操作
        // 如果需要删除数据库表，请在此处添加逻辑
    }

    /**
     * 初始化插件
     */
    public static function init()
    {
        // 在这里初始化其他插件功能，比如加载必要的脚本、样式，或注册自定义功能
    }
}

// 注册激活钩子
register_activation_hook(__FILE__, array('DHS_Tuku_Init', 'activate'));

// 注册停用钩子
register_deactivation_hook(__FILE__, array('DHS_Tuku_Init', 'deactivate'));

// 插件初始化
add_action('init', array('DHS_Tuku_Init', 'init'));
