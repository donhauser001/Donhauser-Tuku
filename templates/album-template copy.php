<style>
    .favorite-select-container {
        position: relative;
        display: none;
        /* 默认隐藏 */
    }

    .favorite-dropdown-btn {
        background-color: rgba(0, 0, 0, 0.5);
        /* 黑色，80%不透明度 */
        color: white;
        padding: 8px;
        font-size: 14px;
        border: none;
        cursor: pointer;
        border-radius: 4px 4px 0 0;
        min-width: 160px;
    }

    .favorite-dropdown-btn:hover {
        background-color: rgba(0, 0, 0, 0.8);


    }

    .like-icon {
        color: #fff !important;
        /* 默认颜色 */
        cursor: pointer;
    }

    .like-icon.liked {
        color: red !important;
        /* 已喜欢的红色 */
    }

    .favorite-dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.5);
        z-index: 1;
        border-radius: 0 0 4px 4px;
        overflow-y: auto;
        max-height: 160px;
        padding: 10px;
    }

    .favorite-select-container:hover .favorite-dropdown-content {
        display: block;
    }

    .favorite-dropdown-content label {
        display: flex;
        align-items: center;
        padding: 5px;
        margin-bottom: 5px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
    }

    .favorite-dropdown-content label:hover {
        background-color: #f1f1f1;
    }

    .favorite-dropdown-content input[type="checkbox"] {
        margin-right: 10px;
        transform: scale(1.2);
    }



    .create-favorite-option {
        font-weight: bold;
        margin-bottom: 10px;
    }
</style>
<div class="dhs-album-details">
    <div class="album-container">
        <div class="header-container">
            <h2><?php echo esc_html($album->album_name); ?></h2>
            <p><?php echo esc_html(get_the_author_meta('display_name', $album->created_by)); ?> - <?php echo esc_html(date('Y-m-d', strtotime($album->created_at))); ?></p>
        </div>
        <div class="meta-container">
            <!-- 垃圾桶图标按钮 -->
            <div class="trash">
                <button id="delete-images" title="删除图片">
                    <i class="fa fa-trash"></i>
                </button>
                <div class="selection-actions" style="display:none;">
                    <button id="select-all">全选</button><span class="xian">|</span>
                    <button id="deselect-all">取消</button><button id="delete-check">删除</button>
                </div>
            </div>
            <button id="generate-thumbnails" data-album-id="<?php echo esc_attr($album_id); ?>">初始化预览图</button>
        </div>
    </div>
    <!-- 图片列表 -->
    <div class="dhs-album-images-grid" id="albumImagesGrid" data-album-id="<?php echo esc_attr($album_id); ?>">
        <?php foreach ($images as $image) :
            $image_data = json_decode($image->file_data, true);
            $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . $image->name . '_thumbnail.jpg';
            $thumbnail_url = site_url('/wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');

            if (!file_exists($thumbnail_path)) {
                $thumbnail_url = site_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg'); // 默认缩略图
            }
        ?>
            <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>">
                <!-- 复选框，默认隐藏 -->
                <input type="checkbox" class="image-checkbox">
                <div class="dhs-image-icons">
                    <a href="javascript:void(0)" class="favorite-icon" title="收藏" data-image-id="<?php echo esc_attr($image->id); ?>">
                        <i class="fa fa-star"></i>
                    </a>

                    <!-- 自定义下拉菜单容器 -->
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
        <?php endforeach; ?>
    </div>



    <!-- 加载更多按钮 -->
    <?php if ($has_more) : ?>
        <div class="load-more-container" style="text-align: center; margin-top: 20px;">
            <button id="loadMoreButton" data-offset="20">加载更多</button>
        </div>
    <?php endif; ?>
</div>



<!-- 添加 JS 逻辑 -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButton = document.getElementById('delete-images');
        const generateThumbnailsButton = document.getElementById('generate-thumbnails');
        const selectAllButton = document.getElementById('select-all');
        const deselectAllButton = document.getElementById('deselect-all');
        const checkboxes = document.querySelectorAll('.image-checkbox');
        const selectionActions = document.querySelector('.selection-actions');
        const deleteCheckButton = document.getElementById('delete-check');
        const loadMoreButton = document.getElementById('loadMoreButton');
        const albumImagesGrid = document.getElementById('albumImagesGrid');
        const favoriteSelectContainers = document.querySelectorAll('.favorite-select-container');
        const favoriteIcons = document.querySelectorAll('.favorite-icon');
        const favoriteDropdownButtons = document.querySelectorAll('.favorite-dropdown-btn');

        favoriteDropdownButtons.forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const imageItem = button.closest('.dhs-album-image-item');
                const imageId = imageItem.getAttribute('data-image-id');
                const dropdownContent = imageItem.querySelector('.favorite-dropdown-content');

                const favoriteName = prompt("请输入收藏夹名称：");
                if (favoriteName) {
                    createNewFavorite(favoriteName, dropdownContent);
                } else {
                    console.log('用户取消了输入');
                }
            });
        });



        favoriteIcons.forEach(function(icon) {
            icon.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation(); // 阻止事件冒泡
                const imageItem = icon.closest('.dhs-album-image-item');
                const favoriteContainer = imageItem.querySelector('.favorite-select-container');
                const isVisible = favoriteContainer.style.display === 'block';
                hideAllFavoriteContainers(); // 隐藏所有收藏夹选择容器
                if (!isVisible) {
                    favoriteContainer.style.display = 'block'; // 显示当前收藏夹选择容器
                }
            });
        });

        // 点击页面其他区域时隐藏所有收藏夹选择容器
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.favorite-select-container')) {
                hideAllFavoriteContainers();
            }
        });

        function hideAllFavoriteContainers() {
            favoriteSelectContainers.forEach(function(container) {
                container.style.display = 'none';
            });
        }

        function createNewFavorite(favoriteName, dropdownContent) {
            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'create_new_favorite',
                        favorite_name: favoriteName,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('新建收藏夹成功，请手动勾选刚刚创建的收藏夹。');
                        // 刷新收藏夹列表
                        const imageId = dropdownContent.closest('.dhs-album-image-item').getAttribute('data-image-id');
                        fetchUserFavorites(imageId, dropdownContent);
                    } else {
                        alert('新建收藏夹失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('新建收藏夹时出错:', error);
                    alert('新建失败，请稍后重试');
                });
        }

        function fetchUserFavorites(imageId, dropdownContent, favoriteIcon, callback) {
            if (!dropdownContent) {
                console.error('dropdownContent is undefined');
                return;
            }

            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'get_user_favorites',
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce,
                        image_id: imageId // 提交当前图片的 ID
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const favorites = data.data.favorites;

                        dropdownContent.innerHTML = ''; // 清空旧的内容

                        let isImageFavorited = false; // 用来判断图片是否已被收藏

                        if (Array.isArray(favorites) && favorites.length > 0) {
                            favorites.forEach(favorite => {
                                const label = document.createElement('label');
                                const checkbox = document.createElement('input');
                                checkbox.type = 'checkbox';
                                checkbox.value = favorite.id;
                                checkbox.checked = favorite.associated_image_ids.includes(imageId.toString());

                                // 如果该图片存在于任何一个收藏夹中，则设置 isImageFavorited 为 true
                                if (checkbox.checked) {
                                    isImageFavorited = true;
                                }

                                label.appendChild(checkbox);
                                label.appendChild(document.createTextNode(' ' + favorite.name));
                                dropdownContent.appendChild(label);
                            });
                        } else {
                            const message = document.createElement('p');
                            message.textContent = '没有收藏项';
                            dropdownContent.appendChild(message);
                        }

                        // 确保 favoriteIcon 存在
                        if (favoriteIcon) {
                            // 更新收藏图标样式
                            if (isImageFavorited) {
                                favoriteIcon.style.color = 'red'; // 修改为已收藏状态的颜色（例如红色）
                            } else {
                                favoriteIcon.style.color = ''; // 还原为默认状态
                            }
                        } else {
                            console.error('favoriteIcon is undefined');
                        }

                        // 绑定复选框事件
                        bindCheckboxEvents();

                        // 执行回调函数（如果存在）
                        if (typeof callback === 'function') {
                            callback();
                        }

                    } else {
                        console.error('获取收藏项失败:', data.message);
                    }
                })
                .catch(error => {
                    console.error('fetchUserFavorites 请求错误:', error);
                });
        }

        favoriteSelectContainers.forEach(function(container) {
            const imageId = container.closest('.dhs-album-image-item').getAttribute('data-image-id');
            const dropdownContent = container.querySelector('.favorite-dropdown-content');
            const favoriteIcon = container.closest('.dhs-image-icons').querySelector('.favorite-icon');

            // 确保 favoriteIcon 存在后再调用 fetchUserFavorites
            if (favoriteIcon) {
                fetchUserFavorites(imageId, dropdownContent, favoriteIcon);
            } else {
                console.error('favoriteIcon is undefined for imageId:', imageId);
            }
        });

        function bindCheckboxEvents() {
            document.querySelectorAll('.favorite-dropdown-content input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const favoriteId = checkbox.value;
                    const imageId = checkbox.closest('.dhs-album-image-item').getAttribute('data-image-id');
                    const isChecked = checkbox.checked;
                    const favoriteIcon = checkbox.closest('.dhs-album-image-item').querySelector('.favorite-icon');

                    if (favoriteId && imageId) {
                        updateFavoriteSelection(favoriteId, imageId, isChecked)
                            .then(() => {
                                // 如果更新成功，实时切换收藏图标的颜色
                                if (isChecked) {
                                    favoriteIcon.style.color = 'red'; // 修改为已收藏状态的颜色
                                } else {
                                    // 检查当前图片是否在任何收藏夹中，如果都未选中则恢复默认颜色
                                    const anyChecked = checkbox.closest('.favorite-dropdown-content').querySelectorAll('input[type="checkbox"]:checked').length > 0;
                                    if (!anyChecked) {
                                        favoriteIcon.style.color = ''; // 还原为默认状态
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('更新收藏夹时出错:', error);
                                alert('更新失败，请稍后重试');
                            });
                    } else {
                        console.error('无法获取 favoriteId 或 imageId:', {
                            favoriteId,
                            imageId
                        });
                        alert('无法获取收藏夹或图片的 ID，请重试。');
                    }
                });
            });
        }

        function updateFavoriteSelection(favoriteId, imageId, isChecked) {
            console.log('发送的数据:', {
                favoriteId,
                imageId,
                isChecked
            }); // 添加调试信息

            return fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'update_favorite_selection',
                        favorite_id: favoriteId,
                        image_id: imageId,
                        is_checked: isChecked ? 1 : 0,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('更新收藏夹选择响应数据:', data); // 输出响应数据
                    if (!data.success) {
                        alert('更新收藏夹选择失败：' + (data.message || '未知错误'));
                    }
                    return data;
                })
                .catch(error => {
                    alert('更新失败，请稍后重试');
                    console.error('更新收藏夹时出错:', error);
                    throw error;
                });
        }

        const likeIcons = document.querySelectorAll('.like-icon');

        likeIcons.forEach(function(icon) {
            const imageItem = icon.closest('.dhs-album-image-item');
            const imageId = imageItem.getAttribute('data-image-id');

            // 检查当前用户是否已经喜欢了这张图片
            checkIfLiked(imageId, icon);

            icon.addEventListener('click', function(event) {
                event.preventDefault();
                const isLiked = !icon.classList.contains('liked'); // 如果已经喜欢，则取消喜欢

                updateLikeStatus(imageId, isLiked, icon);
            });
        });

        function checkIfLiked(imageId, icon) {
            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'check_like_status',
                        image_id: imageId,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.is_liked) {
                        icon.classList.add('liked'); // 应用红色样式
                    }
                })
                .catch(error => {
                    console.error('检查喜欢状态时出错:', error);
                });
        }

        function updateLikeStatus(imageId, isLiked, icon) {
            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'update_like_status',
                        image_id: imageId,
                        is_liked: isLiked ? 1 : 0,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 根据返回的结果更新前端显示
                        if (isLiked) {
                            icon.classList.add('liked');
                        } else {
                            icon.classList.remove('liked');
                        }
                    } else {
                        alert('操作失败：' + (data.message || '未知错误'));
                    }
                })
                .catch(error => {
                    alert('更新失败，请稍后重试');
                    console.error('更新喜欢状态时出错:', error);
                });
        }

        jQuery(document).ready(function($) {
            var $grid = $('#albumImagesGrid');

            $grid.imagesLoaded(function() {
                $grid.masonry({
                    itemSelector: '.dhs-album-image-item',
                    percentPosition: true,
                    columnWidth: '.dhs-album-image-item',
                    gutter: 15
                });
            });

            // 加载更多图片的逻辑
            $('#loadMoreButton').on('click', function() {
                var offset = $(this).data('offset');
                var albumId = $grid.data('album-id');

                if (!albumId || !offset) {
                    console.error('album_id or offset is missing');
                    return;
                }

                $.post(dhs_ajax_obj.ajax_url, {
                    action: 'load_more_images',
                    album_id: albumId,
                    offset: offset
                }).done(function(response) {
                    if (response.success) {
                        var $items = $(response.data.html);
                        $grid.append($items).masonry('appended', $items);
                        $('#loadMoreButton').data('offset', offset + 20);

                        $grid.imagesLoaded(function() {
                            $grid.masonry('layout');
                        });

                        if (!response.data.has_more) {
                            $('#loadMoreButton').hide();
                        }
                    } else {
                        alert('没有更多图片可加载');
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Error: ' + status + error);
                    alert('加载更多图片失败，请稍后重试。');
                });
            });
        });

        // 处理缩略图生成逻辑
        generateThumbnailsButton.addEventListener('click', function() {
            var albumId = this.getAttribute('data-album-id');
            var imageItems = document.querySelectorAll('.dhs-album-image-item');
            var processedCount = 0;
            var errorFiles = [];

            function processNextImage() {
                if (processedCount >= imageItems.length) {
                    if (errorFiles.length > 0) {
                        alert('以下文件未能初始化预览图，随后你可以手动添加:\n' + errorFiles.join('\n'));
                    } else {
                        alert('所有缩略图已成功生成！');
                    }
                    return;
                }

                var currentImageItem = imageItems[processedCount];
                var currentImageName = currentImageItem.getAttribute('data-image-name');
                var progressBarContainer = currentImageItem.querySelector('.progress-bar-container');
                var progressBar = currentImageItem.querySelector('.progress-bar');
                var imageElement = currentImageItem.querySelector('img');

                progressBarContainer.style.display = 'block';
                progressBar.style.width = '0%';

                fetch(dhs_ajax_obj.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'generate_thumbnails',
                            album_id: albumId,
                            image_name: currentImageName
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.status === 'thumbnail_created') {
                            progressBar.style.width = '100%';
                            var newThumbnailUrl = dhs_ajax_obj.site_url + '/wp-content/uploads/tuku/' + albumId + '/thumbnails/' + encodeURIComponent(currentImageName) + '_thumbnail.jpg';

                            imageElement.style.opacity = '0';
                            imageElement.src = newThumbnailUrl;

                            imageElement.onload = function() {
                                imageElement.style.transition = 'opacity 1s ease';
                                imageElement.style.opacity = '1';
                            };

                            setTimeout(function() {
                                progressBarContainer.style.display = 'none';
                            }, 500);

                        } else if (data.success && data.data.status === 'thumbnail_exists') {
                            progressBarContainer.style.display = 'none';
                        } else {
                            progressBarContainer.style.display = 'none';
                            errorFiles.push(currentImageName);
                        }

                        processedCount++;
                        processNextImage();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('缩略图生成失败: 网络错误或服务器未响应');
                        progressBarContainer.style.display = 'none';
                        errorFiles.push(currentImageName);
                        processedCount++;
                        processNextImage();
                    });
            }

            processNextImage();
        });

        // 处理删除功能的逻辑
        deleteButton.addEventListener('click', function() {
            const isVisible = checkboxes[0].classList.contains('show');

            if (isVisible) {
                checkboxes.forEach(checkbox => {
                    checkbox.classList.remove('show');
                    setTimeout(() => checkbox.style.display = 'none', 400); // 延迟隐藏，等待动画结束
                });
                selectionActions.classList.remove('show');
                setTimeout(() => selectionActions.style.display = 'none', 400); // 延迟隐藏，等待动画结束
            } else {
                checkboxes.forEach(checkbox => {
                    checkbox.style.display = 'block';
                    setTimeout(() => checkbox.classList.add('show'), 10); // 延迟以便应用动画
                });
                selectionActions.style.display = 'block';
                setTimeout(() => selectionActions.classList.add('show'), 10); // 延迟以便应用动画
            }
        });

        selectAllButton.addEventListener('click', function() {
            checkboxes.forEach(checkbox => checkbox.checked = true);
        });

        deselectAllButton.addEventListener('click', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false; // 取消所有选中
                setTimeout(() => checkbox.style.display = 'none', 200); // 延迟隐藏，等待动画结束

            });
            selectionActions.classList.remove('show');
            setTimeout(() => selectionActions.style.display = 'none', 400); // 延迟隐藏，等待动画结束

        });

        deleteCheckButton.addEventListener('click', function() {
            const selectedImages = [];
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedImages.push(checkbox.closest('.dhs-album-image-item').getAttribute('data-image-name'));
                }
            });

            if (selectedImages.length > 0) {
                if (confirm('确定要删除选中的图片吗？此操作无法撤销！')) {
                    fetch(dhs_ajax_obj.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'delete_images',
                                album_id: '<?php echo esc_attr($album_id); ?>',
                                images: selectedImages.join(',')
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                selectedImages.forEach(imageName => {
                                    const imageItem = document.querySelector(`.dhs-album-image-item[data-image-name="${imageName}"]`);
                                    if (imageItem) {
                                        imageItem.remove();
                                    }
                                });

                                // 删除成功后隐藏复选框和操作按钮
                                checkboxes.forEach(checkbox => {
                                    setTimeout(() => checkbox.style.display = 'none', 400); // 延迟隐藏复选框
                                });
                                setTimeout(() => selectionActions.style.display = 'none', 800); // 延迟隐藏操作按钮

                            } else {
                                alert('删除失败，请稍后重试。');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('删除失败: 网络错误或服务器未响应');
                        });
                }
            } else {
                alert('请先选择要删除的图片！');
            }
        });



    });
</script>