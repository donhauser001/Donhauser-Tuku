<?php echo dhs_tuku_menu(); ?>

<?php if (is_user_logged_in()): ?>
    <?php
    $current_user = wp_get_current_user(); // 获取当前用户对象
    $user_name = $current_user->display_name; // 获取当前用户的显示名称
    ?>

<?php endif; ?>

<div class="my-gallery-container">
    <h2><?php echo esc_html($user_name); ?>的素材集</h2>
    <p><?php echo esc_html($user_name); ?>创建了 <?php echo esc_html($album_count); ?> 个素材集</p>
</div>

<div class="dhs-tuku-albums">
    <div class="dhs-album-grid">
        <?php if (!empty($albums_array)): ?>
            <?php foreach ($albums_array as $album) : ?>
                <div class="dhs-album-item">
                    <!-- 动态添加相册详情页面的链接，附带 album_id 参数 -->
                    <a href="<?php $link = add_query_arg('album_id', $album['id'], site_url('/tuku/album-details/'));
                                echo esc_url($link); ?>">

                        <!-- 直接使用处理好的封面图片 -->
                        <img src="<?php echo esc_url($album['cover_image']); ?>" alt="<?php echo esc_attr($album['name']); ?>" />

                    </a>
                    <div class="dhs-album-info">
                        <!-- 显示相册的分类名称 -->
                        <div class="dhs-album-category">
                            <a href="<?php echo esc_url(add_query_arg('category_id', $album['category_id'], site_url('/tuku/tuku_categories/'))); ?>">
                                <?php echo esc_html($album['category']); ?>
                            </a>
                        </div>
                        <h3><?php echo esc_html($album['name']); ?></h3>
                        <div class="dhs-album-meta">
                            <p><?php echo esc_html($album['author']); ?> - <?php echo esc_html($album['date']); ?></p>
                        </div>
                    </div>
                    <!-- 添加编辑和删除菜单 -->
                    <div class="dhs-album-options">
                        <i class="fa fa-ellipsis-h"></i>
                        <div class="options-menu">
                            <a href="javascript:void(0)" class="edit-option dhs-tuku-open-modal" data-modal="editalbum:40" data-album-id="<?php echo esc_attr($album['id']); ?>">编辑</a>
                            <a href="javascript:void(0)" class="delete-option" data-album-id="<?php echo esc_attr($album['id']); ?>">删除</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>当前没有可显示的相册</p>
        <?php endif; ?>
    </div>
</div>

<style>
    .dhs-tuku-albums {
        max-width: 100%;
        margin: 20px 0 60px 0;
    }

    .dhs-album-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .dhs-album-item {
        position: relative;
        border-radius: 6px;
        padding: 0px;
        text-align: center;
        background-color: #f0f0f0;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .dhs-album-item:hover {
        transform: scale(1.05);
        box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
    }

    .dhs-album-item img {
        width: 100%;
        height: auto;
        aspect-ratio: 16 / 11.5;
        object-fit: cover;
        transition: transform 0.4s ease;
        transform-origin: bottom;
    }

    .dhs-album-item:hover img {
        transform: scale(1.1);
    }

    .dhs-album-info {
        margin-top: 10px;
    }

    /* 显示分类名称 */
    .dhs-album-category {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: rgba(0, 0, 0, 0.4);
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 12px;
        transition: background-color 0.4s ease;

    }

    /* 显示分类名称 */
    .dhs-album-category:hover {

        background-color: rgba(0, 0, 0, 0.8);

    }

    /* 显示分类名称 */
    .dhs-album-category a {

        color: white;

    }


    /* 相册名称的样式 */
    .dhs-album-info h3 {
        color: #fff;
        margin-left: 10px;
        margin-top: 10px;
        margin-bottom: 0px;
        font-size: 14px;
        font-weight: bold;
        text-align: left;
        transition: color 0.2s ease;
    }

    /* 作者和日期的样式 */
    .dhs-album-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #fff;
        margin: 5px 0 5px 10px;
    }

    .dhs-album-meta p {
        margin: 0;
        text-align: left;
    }

    /* 悬停时相册名称变色 */
    .dhs-album-item:hover .dhs-album-info h3 {
        color: #0073aa;
    }

    /* 编辑和删除菜单样式 */
    .dhs-album-options {
        position: absolute;
        top: 5px;
        right: 10px;
        cursor: pointer;
    }



    .dhs-album-options .options-menu {
        width: 50px;
        display: none;
        position: absolute;
        top: 20px;
        right: 0;
        background-color: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
        z-index: 20;
    }

    .dhs-album-options .options-menu a {
        font-size: 12px;
        font-weight: bold;
        display: block;
        padding: 6px;
        color: #333;
        text-align: center;
        text-decoration: none;
        transition: background-color 0.3s ease;
        border-radius: 4px;
    }

    .dhs-album-options .options-menu a:hover {
        background-color: #4f5564;
        color: #fff;
    }

    .dhs-album-options:hover .options-menu {
        display: block;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var albumItems = document.querySelectorAll('.dhs-album-item');

        albumItems.forEach(function(item) {
            var img = item.querySelector('img');

            // 函数：根据亮度调整文字颜色
            function adjustTextColor(color) {
                var brightness = calculateBrightness(color);
                if (brightness > 200) {
                    item.querySelector('h3').style.color = '#333'; // 设置深色文字
                    item.querySelector('.dhs-album-meta').style.color = '#333'; // 设置深色作者和日期
                } else {
                    item.querySelector('h3').style.color = '#fff'; // 设置白色文字
                    item.querySelector('.dhs-album-meta').style.color = '#fff'; // 设置白色作者和日期
                }
            }

            // 图片加载完成时获取主色调并调整背景和文字颜色
            function setAlbumColors() {
                var color = getDominantColor(img);
                var hasTransparency = checkTransparency(img);

                if (hasTransparency) {
                    // 如果图片有透明背景，设置浅灰色背景和深色文字
                    item.style.backgroundColor = '#f0f0f0';
                    item.querySelector('h3').style.color = '#333'; // 设置深色文字
                    item.querySelector('.dhs-album-meta').style.color = '#333'; // 设置深色作者和日期
                } else {
                    // 否则使用图片的主色调作为背景色并调整文字颜色
                    item.style.backgroundColor = color;
                    adjustTextColor(color);
                }
            }

            // 如果图片已经加载，立即处理
            if (img.complete) {
                setAlbumColors();
            } else {
                // 确保图片加载完成后处理
                img.addEventListener('load', setAlbumColors);
            }

            // 添加删除逻辑
            var deleteButton = item.querySelector('.delete-option');
            deleteButton.addEventListener('click', function(event) {
                event.preventDefault();
                var albumId = deleteButton.getAttribute('data-album-id');
                if (confirm('确定要删除这个相册吗？')) {
                    fetch(dhs_ajax_obj.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'delete_album',
                                album_id: albumId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('相册已成功删除');
                                item.remove(); // 从页面中移除相册项
                            } else {
                                alert('删除相册时发生错误: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('删除相册时发生网络错误或服务器未响应');
                        });
                }
            });
        });



        // 获取图片的主色调
        function getDominantColor(image) {
            var canvas = document.createElement('canvas');
            var context = canvas.getContext('2d');
            canvas.width = image.width;
            canvas.height = image.height;
            context.drawImage(image, 0, 0, canvas.width, canvas.height);

            var data = context.getImageData(0, 0, canvas.width, canvas.height).data;
            var r = 0,
                g = 0,
                b = 0,
                count = 0;

            for (var i = 0; i < data.length; i += 40) {
                r += data[i];
                g += data[i + 1];
                b += data[i + 2];
                count++;
            }

            r = Math.floor(r / count);
            g = Math.floor(g / count);
            b = Math.floor(b / count);

            return `rgb(${r}, ${g}, ${b})`;
        }

        // 检查图片是否有透明背景
        function checkTransparency(image) {
            var canvas = document.createElement('canvas');
            var context = canvas.getContext('2d');
            canvas.width = image.width;
            canvas.height = image.height;
            context.drawImage(image, 0, 0, canvas.width, canvas.height);

            var data = context.getImageData(0, 0, canvas.width, canvas.height).data;
            for (var i = 3; i < data.length; i += 4) { // Alpha 通道，每 4 个数据检查透明度
                if (data[i] < 255) {
                    return true; // 发现透明像素
                }
            }
            return false; // 没有透明像素
        }

        // 计算颜色亮度
        function calculateBrightness(rgbColor) {
            var rgb = rgbColor.match(/\d+/g); // 获取 RGB 值
            var r = parseInt(rgb[0]);
            var g = parseInt(rgb[1]);
            var b = parseInt(rgb[2]);

            // 亮度计算公式
            return Math.sqrt(0.299 * (r * r) + 0.587 * (g * g) + 0.114 * (b * b));
        }
    });
</script>