<?php
// 临时调试文件 - 请勿在生产环境中使用
require_once('/var/www/html/wp-load.php');

if (!is_user_logged_in()) {
    die('请先登录');
}

global $wpdb;
$current_user_id = get_current_user_id();
$favorites_table = $wpdb->prefix . 'dhs_gallery_favorites';

// 获取收藏夹
$favorites = $wpdb->get_results($wpdb->prepare("
    SELECT id, name, created_at AS date, user_id AS author, is_public
    FROM {$favorites_table}
    WHERE user_id = %d
    ORDER BY created_at DESC
", $current_user_id));

echo "<h2>调试收藏夹数据</h2>";

foreach ($favorites as $favorite) {
    echo "<h3>收藏夹: {$favorite->name} (ID: {$favorite->id})</h3>";
    
    $images_table = $wpdb->prefix . 'dhs_gallery_images';
    $favorite_images_table = $wpdb->prefix . 'dhs_gallery_favorite_images';
    
    // 获取收藏夹中的图片缩略图（去重）
    $thumbnails = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT i.id, i.name, i.file_data, i.album_id
        FROM {$favorite_images_table} fi
        LEFT JOIN {$images_table} i ON fi.image_id = i.id
        WHERE fi.favorite_id = %d AND i.status = 'active'
        ORDER BY fi.created_at DESC
        LIMIT 3
    ", $favorite->id));
    
    echo "<p>找到 " . count($thumbnails) . " 张图片</p>";
    
    foreach ($thumbnails as $thumb) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<p><strong>图片ID:</strong> {$thumb->id}</p>";
        echo "<p><strong>文件名:</strong> {$thumb->name}</p>";
        echo "<p><strong>相册ID:</strong> {$thumb->album_id}</p>";
        
        // 尝试不同的缩略图文件名格式
        $possible_names = [
            $thumb->name . '_thumbnail.jpg',
            $thumb->name . '_thumbnail.png',
            $thumb->name . '.jpg',
            $thumb->name . '.png'
        ];
        
        echo "<p><strong>尝试的文件名:</strong></p><ul>";
        foreach ($possible_names as $filename) {
            $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $thumb->album_id . '/thumbnails/' . $filename;
            $exists = file_exists($thumbnail_path) ? '✅' : '❌';
            echo "<li>{$exists} {$filename}</li>";
            
            if (file_exists($thumbnail_path)) {
                $thumbnail_url = site_url('/wp-content/uploads/tuku/' . $thumb->album_id . '/thumbnails/' . rawurlencode($filename));
                echo "<p><strong>找到文件:</strong> <a href='{$thumbnail_url}' target='_blank'>{$thumbnail_url}</a></p>";
                echo "<img src='{$thumbnail_url}' style='max-width: 200px; max-height: 200px;'>";
                break;
            }
        }
        echo "</ul>";
        echo "</div>";
    }
    echo "<hr>";
}
?>
