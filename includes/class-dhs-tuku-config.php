<?php

/**
 * DHS Tuku 配置类
 * 用于管理全局配置项和常量，包括数据库字段
 */

class DHS_Tuku_Config
{
    // 存储所有配置项的静态属性，包括数据库字段
    private static $config = [
        // 插件版本信息
        'version' => '1.0.0',

        // 全局上传大小限制
        'max_upload_size' => '5GB',

        // 图片相关数据库字段
        'image_fields' => [
            'id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',   // 图片主键，自动增长
            'name' => 'VARCHAR(255) NOT NULL',              // 图片名称（去掉扩展名）
            'file_data' => 'LONGTEXT NOT NULL',             // 存储文件信息（路径、MIME 类型、上传日期等），以 JSON 格式存储
            'album_id' => 'BIGINT(20) UNSIGNED',            // 所属相册 ID
            'status' => "ENUM('active', 'deleted') DEFAULT 'active'",  // 文件状态
        ],

        // 图片元数据相关字段
        'imagemeta_fields' => [
            'meta_id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',  // 元数据主键，自增
            'image_id' => 'BIGINT(20) UNSIGNED NOT NULL',       // 关联的图片 ID
            'meta_key' => 'VARCHAR(255) NOT NULL',              // 元数据键
            'meta_value' => 'LONGTEXT NOT NULL',                // 元数据值
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',  // 元数据创建时间
            'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',  // 元数据更新时间
        ],

        // 相册相关数据库字段
        'album_fields' => [
            'id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',       // 相册主键
            'album_name' => 'VARCHAR(255) NOT NULL',            // 相册名称
            'created_by' => 'BIGINT(20) UNSIGNED NOT NULL',     // 创建者用户 ID
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',  // 创建时间
            'cover_image' => 'BIGINT(20) UNSIGNED',             // 封面图片 ID
        ],

        // 相册元数据相关字段
        'albummeta_fields' => [
            'meta_id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',  // 元数据主键，自增
            'album_id' => 'BIGINT(20) UNSIGNED NOT NULL',       // 关联的相册 ID
            'meta_key' => 'VARCHAR(255) NOT NULL',              // 元数据键
            'meta_value' => 'LONGTEXT NOT NULL',                // 元数据值
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',  // 元数据创建时间
            'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',  // 元数据更新时间
        ],

        // 分类表相关字段
        'category_fields' => [
            'id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',   // 分类主键，自增
            'category_name' => 'VARCHAR(255) NOT NULL',     // 分类名称，不能为空
            'parent_id' => 'BIGINT(20) UNSIGNED DEFAULT NULL', // 父类别 ID，允许为空
            'category_id' => 'BIGINT(20) UNSIGNED NOT NULL',   // 新增的分类 ID 字段，不能为空
            'description' => 'TEXT DEFAULT NULL',           // 分类描述，非必填
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', // 创建时间
            'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', // 更新时间
            'FOREIGN_KEY' => 'FOREIGN KEY (parent_id) REFERENCES dhs_gallery_categories(id) ON DELETE SET NULL',
            'KEY category_id (category_id)' // 为新增加的 category_id 创建索引以便优化查询
        ],

        // 标签相关数据库字段
        'tag_fields' => [
            'id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',       // 标签主键
            'tag_name' => 'VARCHAR(255) NOT NULL',              // 标签名称
        ],

        // 图片和标签的关联关系表
        'image_tag_fields' => [
            'id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',       // 主键，自增
            'image_id' => 'BIGINT(20) UNSIGNED NOT NULL',       // 关联的图片 ID
            'tag_id' => 'BIGINT(20) UNSIGNED NOT NULL',         // 关联的标签 ID
        ],

        // 权限管理相关数据库字段
        'permission_fields' => [
            'id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',       // 主键，自增
            'resource_type' => "ENUM('album', 'image') NOT NULL",  // 资源类型
            'resource_id' => 'BIGINT(20) UNSIGNED NOT NULL',    // 资源 ID
            'user_id' => 'BIGINT(20) UNSIGNED NOT NULL',        // 用户 ID
            'permission' => "ENUM('view', 'edit', 'delete') NOT NULL",  // 权限类型
            'expires_at' => 'DATETIME NULL',                    // 权限过期时间（可选）
        ],

        // 日志相关数据库字段
        'log_fields' => [
            'log_id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',   // 主键，自增
            'user_id' => 'BIGINT(20) UNSIGNED NOT NULL',        // 操作用户 ID
            'resource_type' => "ENUM('album', 'image', 'tag') NOT NULL",  // 资源类型
            'resource_id' => 'BIGINT(20) UNSIGNED NOT NULL',    // 资源 ID
            'action' => "ENUM('create', 'update', 'delete', 'view', 'upload') NOT NULL",  // 操作类型
            'log_message' => 'TEXT NOT NULL',                   // 操作日志内容
            'log_time' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',  // 操作时间
            'ip_address' => 'VARCHAR(45) NOT NULL',             // 用户的 IP 地址
            'user_agent' => 'VARCHAR(255)',                     // 用户的浏览器信息
        ],

        // 收藏表相关数据库字段
        'favorite_fields' => [
            'id' => 'BIGINT(20) UNSIGNED AUTO_INCREMENT',       // 收藏主键，自增
            'name' => 'VARCHAR(255) NOT NULL',                  // 收藏名称
            'user_id' => 'BIGINT(20) UNSIGNED NOT NULL',        // 用户 ID
            'image_id' => 'BIGINT(20) UNSIGNED NOT NULL',       // 图片 ID
            'is_public' => 'BOOLEAN NOT NULL DEFAULT FALSE',    // 是否公开
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',  // 创建时间
            'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',  // 更新时间
        ],
    ];

    /**
     * 获取配置项的值
     *
     * @param string $key 配置项的键
     * @return mixed|null 返回配置项的值，若不存在返回 null
     */
    public static function get($key)
    {
        return isset(self::$config[$key]) ? self::$config[$key] : null;
    }

    /**
     * 设置配置项的值
     *
     * @param string $key 配置项的键
     * @param mixed $value 配置项的值
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }

    /**
     * 获取所有配置项
     *
     * @return array 返回所有配置项
     */
    public static function all()
    {
        return self::$config;
    }
}
