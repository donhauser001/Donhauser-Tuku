<div class="search-results-container">
    <h2>关于 "<?php echo esc_html($search_query); ?>" 的搜索结果</h2>
    <p>找到 <?php echo esc_html(count($images)); ?> 个相关的素材</p>
</div>

<div class="dhs-album-images-grid" id="searchResultsGrid">
    <?php foreach ($images as $image) :
        $album_id = esc_attr($image->album_id);

        $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';
        $thumbnail_url = site_url('/wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');
        // 输出路径调试信息
        error_log('Thumbnail path: ' . $thumbnail_path);
        error_log('Thumbnail URL: ' . $thumbnail_url);
        if (!file_exists($thumbnail_path)) {
            $thumbnail_url = site_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg'); // 默认缩略图
        }
    ?>
        <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>" data-album-id="<?php echo $album_id; ?>">
            <?php if (is_user_logged_in()) : ?>
                <div class="dhs-image-icons">
                    <a href="javascript:void(0)" class="favorite-icon" title="收藏" data-image-id="<?php echo esc_attr($image->id); ?>">
                        <i class="fa fa-star"></i>
                    </a>
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
            <?php endif; ?>
            <a href="javascript:void(0)" class="chakanimage dhs-tuku-open-modal" data-modal="image:50" data-image-id="<?php echo esc_attr($image->id); ?>">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($image->name); ?>" />
            </a>
        </div>
    <?php endforeach; ?>
</div>

<script>
    jQuery(document).ready(function($) {


        const likeIcons = document.querySelectorAll('.like-icon');

        // 移除现有的事件处理程序
        likeIcons.forEach(function(icon) {
            const clone = icon.cloneNode(true);
            icon.parentNode.replaceChild(clone, icon);
        });

        // 重新查询并绑定事件处理程序
        const newLikeIcons = document.querySelectorAll('.like-icon');

        newLikeIcons.forEach(function(icon) {
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
                }
            });
        });

        favoriteIcons.forEach(function(icon) {
            icon.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const imageItem = icon.closest('.dhs-album-image-item');
                if (!imageItem) {
                    return;
                }

                const favoriteContainer = imageItem.querySelector('.favorite-select-container');
                if (!favoriteContainer) {
                    return;
                }

                const isVisible = favoriteContainer.style.display === 'block';
                hideAllFavoriteContainers();
                if (!isVisible) {
                    favoriteContainer.style.display = 'block';
                }
            });
        });

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
                        const imageId = dropdownContent.closest('.dhs-album-image-item').getAttribute('data-image-id');
                        fetchUserFavorites(imageId, dropdownContent);
                    } else {
                        alert('新建收藏夹失败：' + data.message);
                    }
                })
                .catch(() => {
                    alert('新建失败，请稍后重试');
                });
        }

        function fetchUserFavorites(imageId, dropdownContent, favoriteIcon, callback) {
            if (!dropdownContent || !imageId) {
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
                        image_id: imageId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const favorites = data.data.favorites;
                        dropdownContent.innerHTML = '';

                        let isImageFavorited = false;

                        if (Array.isArray(favorites) && favorites.length > 0) {
                            favorites.forEach(favorite => {
                                if (Array.isArray(favorite.associated_image_ids)) {
                                    const isChecked = favorite.associated_image_ids.includes(imageId.toString());

                                    const label = document.createElement('label');
                                    const checkbox = document.createElement('input');
                                    checkbox.type = 'checkbox';
                                    checkbox.value = favorite.id;
                                    checkbox.checked = isChecked;

                                    if (isChecked) {
                                        isImageFavorited = true;
                                    }

                                    label.appendChild(checkbox);
                                    label.appendChild(document.createTextNode(' ' + favorite.name));
                                    dropdownContent.appendChild(label);
                                }
                            });
                        } else {
                            const message = document.createElement('p');
                            message.textContent = '没有收藏项';
                            dropdownContent.appendChild(message);
                        }

                        if (favoriteIcon) {
                            favoriteIcon.style.color = isImageFavorited ? 'red' : '';
                        }

                        bindCheckboxEvents();

                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                })
                .catch(() => {
                    console.error('fetchUserFavorites 请求错误');
                });
        }

        favoriteSelectContainers.forEach(function(container) {
            const imageId = container.closest('.dhs-album-image-item').getAttribute('data-image-id');
            const dropdownContent = container.querySelector('.favorite-dropdown-content');
            const favoriteIcon = container.closest('.dhs-image-icons').querySelector('.favorite-icon');

            if (favoriteIcon) {
                fetchUserFavorites(imageId, dropdownContent, favoriteIcon);
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
                                if (isChecked) {
                                    favoriteIcon.style.color = 'red';
                                } else {
                                    const anyChecked = checkbox.closest('.favorite-dropdown-content').querySelectorAll('input[type="checkbox"]:checked').length > 0;
                                    if (!anyChecked) {
                                        favoriteIcon.style.color = '';
                                    }
                                }
                            })
                            .catch(() => {
                                alert('更新失败，请稍后重试');
                            });
                    } else {
                        alert('无法获取收藏夹或图片的 ID，请重试。');
                    }
                });
            });
        }

        function updateFavoriteSelection(favoriteId, imageId, isChecked) {
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
                    if (!data.success) {
                        alert('更新收藏夹选择失败：' + (data.message || '未知错误'));
                    }
                    return data;
                })
                .catch(() => {
                    alert('更新失败，请稍后重试');
                    throw new Error('Update favorite selection failed');
                });
        }

        // 初始化页面加载后的喜欢和收藏按钮
        initLikes();
        initFavorites();
    });
</script>