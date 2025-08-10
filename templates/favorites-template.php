<?php if (is_user_logged_in()): ?>
    <?php
    $current_user = wp_get_current_user(); // 获取当前用户对象
    $user_name = $current_user->display_name; // 获取当前用户的显示名称
    ?>

<?php endif; ?>

<?php if (!empty($favorites_array)): ?>
    <?php echo dhs_tuku_menu(); // 菜单 
    ?>

    <div class="favorite-container">
        <h2><?php echo esc_html($user_name); ?>的收藏夹列表</h2>
        <p><?php echo esc_html($user_name); ?>创建了 <?php echo esc_html($favorites_count); ?> 个收藏夹</p>
    </div>

    <div class="dhs-favorites-container">
        <?php foreach ($favorites_array as $favorite): ?>
            <div class="dhs-favorite-item">
                <div class="dhs-favorite-image">
                    <a href="<?php echo esc_url(add_query_arg('favorite_id', $favorite['id'], site_url('/tuku/favorites/favorite_details'))); ?>">
                        <?php
                        $item_count = count($favorite['thumbnails']);

                        if ($item_count === 0) {
                            // 收藏夹为空时，显示默认封面图
                            echo '<img src="' . esc_url($favorite['image_path']) . '" alt="' . esc_attr($favorite['name']) . '">';
                        } elseif ($item_count === 1) {
                            // 只有一个项目时，显示第一个项目的缩略图
                            echo '<img src="' . esc_url($favorite['thumbnails'][0]['thumbnail']) . '" alt="' . esc_attr($favorite['name']) . '">';
                        } elseif ($item_count >= 3) {
                            // 有三个或更多项目时，显示三个项目的组合封面图
                        ?>
                            <div class="multi-thumbnail-cover">
                                <div class="left-thumbnail">
                                    <img src="<?php echo esc_url($favorite['thumbnails'][0]['thumbnail']); ?>" alt="<?php echo esc_attr($favorite['name']); ?>">
                                </div>
                                <div class="right-thumbnails">
                                    <div class="top-thumbnail">
                                        <img src="<?php echo esc_url($favorite['thumbnails'][1]['thumbnail']); ?>" alt="<?php echo esc_attr($favorite['name']); ?>">
                                    </div>
                                    <div class="bottom-thumbnail">
                                        <img src="<?php echo esc_url($favorite['thumbnails'][2]['thumbnail']); ?>" alt="<?php echo esc_attr($favorite['name']); ?>">
                                    </div>
                                </div>
                            </div>
                        <?php
                        } else {
                            // 如果有两个项目，显示第一个项目的缩略图
                            echo '<img src="' . esc_url($favorite['thumbnails'][0]['thumbnail']) . '" alt="' . esc_attr($favorite['name']) . '">';
                        }
                        ?>
                    </a>
                </div>
                <div class="dhs-favorite-details">
                    <h3><?php echo esc_html($favorite['name']); ?></h3>
                </div>
                <div class="favorite-actions">
                    <button class="menu-button">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="menu-dropdown">
                        <a href="javascript:void(0)" class="share-favorite" data-favorite-id="<?php echo esc_attr($favorite['id']); ?>">分享</a>
                        <a href="javascript:void(0)" class="edit-button dhs-tuku-open-modal" data-modal="editfavorite:20" data-favorite-id="<?php echo esc_attr($favorite['id']); ?>">编辑</a>
                        <a href="javascript:void(0)" class="delete-favorite" data-favorite-id="<?php echo esc_attr($favorite['id']); ?>">删除</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>当前没有可显示的收藏</p>
<?php endif; ?>
<style>
    .biaoti h2 {
        font-size: 24px;
        margin-top: 60px;
    }

    #edit-button {}

    .dhs-favorites-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 60px;
        margin-top: 20px;
    }

    .dhs-favorite-item {
        border: 1px solid #ddd;
        padding: 10px;
        box-sizing: border-box;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        position: relative;
        /* 使子元素相对于收藏项定位 */
    }

    /* 针对单张图片封面的样式 */
    .dhs-favorite-image img {
        border-radius: 4px;
        width: 100%;
        height: 200px;
        object-fit: cover;
        object-position: center;
        display: block;
        /* 浅灰色边框 */
    }


    .multi-thumbnail-cover {
        display: flex;
        width: 100%;
        height: 200px;
        border-radius: 6px;
        overflow: hidden;
    }

    .left-thumbnail {
        flex: 1;
        height: 100%;
        overflow: hidden;
        border-right: 2px solid #fff;
    }

    .right-thumbnails {
        display: flex;
        flex-direction: column;
        flex: 1;
        height: 100%;
        overflow: hidden;
    }

    .right-thumbnails .top-thumbnail,
    .right-thumbnails .bottom-thumbnail {
        flex: 1;
        overflow: hidden;
    }

    .right-thumbnails .top-thumbnail {
        border-bottom: 2px solid #fff;
    }

    .multi-thumbnail-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 0;
        box-sizing: border-box;
    }

    .dhs-favorite-details {
        text-align: left;
    }

    .dhs-favorite-details h3 {
        font-size: 14px !important;
        margin: 20px 0 10px 0;
    }

    .dhs-favorite-details p {
        margin: 0 0 5px;
        color: #666;
    }

    .dhs-favorite-link {
        display: inline-block;
        margin-top: 10px;
        padding: 5px 10px;
        background-color: #0073aa;
        color: #fff;
        text-decoration: none;
        border-radius: 3px;
    }

    .dhs-favorite-link:hover {
        background-color: #005177;
    }

    .favorite-actions {
        position: absolute;
        top: 10px;
        right: 20px;
        z-index: 10;
    }

    .menu-button {
        background-color: transparent;
        border: none;
        cursor: pointer;
        color: #fff;
        font-size: 20px;
        padding: 4px;
        text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.5);
    }




    .menu-button:hover {
        background-color: transparent;
        color: #fff;

    }

    .menu-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        border-radius: 4px;
        z-index: 1000;
        width: 60px;
    }

    .menu-dropdown a {
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

    .menu-dropdown a:hover {
        background-color: #4f5564;
        color: #fff;
    }

    .favorite-actions .menu-button:focus+.menu-dropdown,
    .favorite-actions .menu-button:hover+.menu-dropdown {
        display: block;
    }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 点击按钮时切换菜单的显示/隐藏
        document.querySelectorAll('.menu-button').forEach(button => {
            button.addEventListener('click', function(event) {
                event.stopPropagation(); // 阻止事件冒泡，避免触发全局的点击事件
                const dropdown = this.nextElementSibling;
                document.querySelectorAll('.menu-dropdown').forEach(menu => {
                    if (menu !== dropdown) {
                        menu.style.display = 'none'; // 关闭其他菜单
                    }
                });
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });
        });

        // 点击页面的其他地方时隐藏所有菜单
        document.addEventListener('click', function() {
            document.querySelectorAll('.menu-dropdown').forEach(menu => {
                menu.style.display = 'none';
            });
        });

        // 让菜单在鼠标悬停时保持显示
        document.querySelectorAll('.favorite-actions').forEach(action => {
            action.addEventListener('mouseenter', function() {
                const dropdown = this.querySelector('.menu-dropdown');
                dropdown.style.display = 'block';
            });

            action.addEventListener('mouseleave', function() {
                const dropdown = this.querySelector('.menu-dropdown');
                dropdown.style.display = 'none';
            });
        });

        jQuery(document).ready(function($) {
            $('.delete-favorite').on('click', function(e) {
                e.preventDefault();

                if (!confirm('确定要删除此收藏夹吗？此操作无法撤销！')) {
                    return;
                }

                var favoriteId = $(this).data('favorite-id');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'dhs_delete_favorite',
                        favorite_id: favoriteId,
                        _ajax_nonce: '<?php echo wp_create_nonce("dhs_delete_favorite_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('收藏夹已成功删除。');
                            location.reload(); // 删除后刷新页面
                        } else {
                            alert('删除失败: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('删除失败: 网络错误或服务器未响应');
                    }
                });
            });
        });

        jQuery(document).ready(function($) {
            $('.share-favorite').on('click', function(e) {
                e.preventDefault();
                var favoriteId = $(this).data('favorite-id');
                console.log('分享按钮点击，收藏夹ID:', favoriteId); // 调试信息

                $.post(dhs_ajax_obj.ajax_url, {
                    action: 'check_favorite_public_status',
                    favorite_id: favoriteId,
                    _ajax_nonce: dhs_ajax_obj.dhs_nonce
                }).done(function(response) {
                    console.log('检查公开状态响应:', response); // 调试信息
                    if (response.success) {
                        var isPublic = parseInt(response.data.is_public, 10); // 将公开状态解析为整数
                        console.log('收藏夹公开状态:', isPublic); // 调试信息

                        var siteUrl = dhs_ajax_obj.site_url; // 动态获取站点 URL
                        var favoriteName = response.data.favorite_name; // 收藏夹名称
                        var userName = dhs_ajax_obj.current_user_name; // 当前用户的显示名
                        var shareLink = siteUrl + '/tuku/favorites/favorite_details/?favorite_id=' + favoriteId;

                        if (isPublic === 1) { // 公开状态
                            var shareMessage = userName + " 向你分享了他的 " + favoriteName + " 素材收藏夹地址：" + shareLink;
                            console.log('生成的分享链接:', shareLink); // 调试信息
                            copyToClipboard(shareMessage);
                            alert('已经复制到剪贴板，分享内容如下：' + shareMessage);
                        } else {
                            console.log('收藏夹为私密状态，提示用户将其设为公开。'); // 调试信息
                            if (confirm('该收藏夹目前为私密状态，继续分享将使其公开。是否继续？')) {
                                $.post(dhs_ajax_obj.ajax_url, {
                                    action: 'make_favorite_public_and_share',
                                    favorite_id: favoriteId,
                                    _ajax_nonce: dhs_ajax_obj.dhs_nonce
                                }).done(function(updateResponse) {
                                    console.log('公开并分享响应:', updateResponse); // 调试信息
                                    if (updateResponse.success) {
                                        var updatedShareMessage = userName + " 向你分享了他的 " + favoriteName + " 素材收藏夹地址：" + shareLink;
                                        console.log('收藏夹已公开，生成的分享链接:', shareLink); // 调试信息
                                        copyToClipboard(updatedShareMessage);
                                        alert('收藏夹已设为公开，分享链接已复制到剪贴板：' + updatedShareMessage);
                                    } else {
                                        console.error('公开操作失败:', updateResponse.data.message); // 调试信息
                                        alert('操作失败：' + updateResponse.data.message);
                                    }
                                }).fail(function(xhr, status, error) {
                                    console.error('公开操作请求失败:', status, error); // 调试信息
                                });
                            } else {
                                console.log('用户取消了将收藏夹设为公开的操作。'); // 调试信息
                            }
                        }
                    } else {
                        console.error('检查收藏夹状态失败:', response.data.message); // 调试信息
                        alert('检查收藏夹状态时出错：' + response.data.message);
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX 请求失败:', status, error); // 调试信息
                });
            });

            function copyToClipboard(text) {
                var tempInput = document.createElement('input');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                console.log('链接已复制到剪贴板:', text); // 调试信息
            }
        });
    });
</script>