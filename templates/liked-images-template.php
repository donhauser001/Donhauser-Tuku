<div class="liked-container">
    <h2><?php echo esc_html($user_name); ?>喜欢的素材</h2>
    <p><?php echo esc_html($user_name); ?>喜欢了 <?php echo esc_html($total_liked_images); ?> 个素材</p>
</div>

<div class="dhs-album-images-grid" id="likedImagesGrid">
    <?php foreach ($liked_images as $image) :
        $album_id = esc_attr($image->album_id);

        $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';
        $thumbnail_url = site_url('/wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');

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
        var $grid = $('#likedImagesGrid');
        let isLoading = false; // 防止多次触发加载

        // 初始化 Masonry 布局
        $grid.imagesLoaded(function() {
            $grid.masonry({
                itemSelector: '.dhs-album-image-item',
                percentPosition: true,
                columnWidth: '.dhs-album-image-item',
                gutter: 15
            });
        });

        function loadImages() {
            if (isLoading) return;
            isLoading = true;

            var offset = $grid.data('offset') || 0;
            var userId = dhs_ajax_obj.user_id;

            console.log('Current Offset:', offset); // 调试信息
            console.log('User ID:', userId); // 调试信息

            if (!userId || offset === undefined) {
                console.error('user_id or offset is missing');
                isLoading = false;
                return;
            }

            $.post(dhs_ajax_obj.ajax_url, {
                action: 'load_more_liked_images',
                user_id: userId,
                offset: offset
            }).done(function(response) {
                if (response.success) {
                    var $items = $(response.data.html);
                    if ($items.length > 0) {
                        $grid.append($items).imagesLoaded(function() {
                            $grid.masonry('appended', $items);

                            // 重新初始化喜欢按钮和收藏按钮的状态
                            $items.find('.like-icon').each(function() {
                                var imageId = $(this).closest('.dhs-album-image-item').data('image-id');
                                checkIfLiked(imageId, $(this));
                            });

                            $items.find('.favorite-icon').each(function() {
                                var imageId = $(this).closest('.dhs-album-image-item').data('image-id');
                                var $dropdownContent = $(this).siblings('.favorite-select-container').find('.favorite-dropdown-content');
                                fetchUserFavorites(imageId, $dropdownContent, $(this));
                            });

                            // 重新绑定喜欢按钮和收藏按钮的事件
                            initLikes();
                            initFavorites();
                        });

                        // 更新 offset 值
                        $grid.data('offset', offset + $items.length);
                    }

                    if (!response.data.has_more) {
                        $(window).off('scroll', checkScroll); // 停止监听滚动事件
                    }
                } else {
                    alert('没有更多图片可加载');
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error: ' + status + error);
                alert('加载更多图片失败，请稍后重试。');
            }).always(function() {
                isLoading = false;
            });
        }

        function checkScroll() {
            var scrollTop = $(window).scrollTop();
            var windowHeight = $(window).height();
            var documentHeight = $(document).height();

            if (scrollTop + windowHeight >= documentHeight - 100) {
                loadImages();
            }
        }

        // 监听窗口的滚动事件
        $(window).on('scroll', checkScroll);

        // 初始化偏移量
        $grid.data('offset', 20);

        function checkIfLiked(imageId, $icon) {
            $.post(dhs_ajax_obj.ajax_url, {
                action: 'check_like_status',
                image_id: imageId,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            }).done(function(response) {
                if (response.success && response.data.is_liked) {
                    $icon.addClass('liked');
                } else {
                    $icon.removeClass('liked');
                }
            }).fail(function(xhr, status, error) {
                console.error('检查喜欢状态时出错:', error);
            });
        }

        function fetchUserFavorites(imageId, $dropdownContent, $icon) {
            $.post(dhs_ajax_obj.ajax_url, {
                action: 'get_user_favorites',
                image_id: imageId,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            }).done(function(response) {
                if (response.success) {
                    var favorites = response.data.favorites;
                    var isImageFavorited = false;

                    $dropdownContent.empty();

                    if (Array.isArray(favorites) && favorites.length > 0) {
                        favorites.forEach(function(favorite) {
                            var isChecked = favorite.associated_image_ids.includes(imageId.toString());
                            var $label = $('<label>');
                            var $checkbox = $('<input type="checkbox">').val(favorite.id).prop('checked', isChecked);

                            $label.append($checkbox).append(' ' + favorite.name);
                            $dropdownContent.append($label);

                            if (isChecked) {
                                isImageFavorited = true;
                            }
                        });
                    } else {
                        $dropdownContent.append('<p>没有收藏项</p>');
                    }

                    if (isImageFavorited) {
                        $icon.css('color', 'red');
                    } else {
                        $icon.css('color', '');
                    }

                    bindCheckboxEvents($dropdownContent);
                }
            }).fail(function(xhr, status, error) {
                console.error('获取收藏项时出错:', error);
            });
        }

        function initLikes() {
            $('.like-icon').off('click').on('click', function(event) {
                event.preventDefault();
                var $icon = $(this);
                var imageId = $icon.closest('.dhs-album-image-item').data('image-id');
                var isLiked = !$icon.hasClass('liked');

                updateLikeStatus(imageId, isLiked, $icon);
            });
        }

        function initFavorites() {
            $('.favorite-icon').off('click').on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                var $icon = $(this);
                var $imageItem = $icon.closest('.dhs-album-image-item');
                var $favoriteContainer = $imageItem.find('.favorite-select-container');

                $('.favorite-select-container').not($favoriteContainer).hide(); // 隐藏其他容器

                if ($favoriteContainer.is(':visible')) {
                    $favoriteContainer.hide();
                } else {
                    $favoriteContainer.show();
                }
            });

            // 点击页面其他区域时隐藏所有收藏夹选择容器
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.favorite-select-container, .favorite-icon').length) {
                    $('.favorite-select-container').hide();
                }
            });
        }

        function bindCheckboxEvents($container) {
            $container.find('input[type="checkbox"]').on('change', function() {
                var $checkbox = $(this);
                var favoriteId = $checkbox.val();
                var imageId = $checkbox.closest('.dhs-album-image-item').data('image-id');
                var isChecked = $checkbox.is(':checked');
                var $icon = $checkbox.closest('.dhs-album-image-item').find('.favorite-icon');

                updateFavoriteSelection(favoriteId, imageId, isChecked, $icon);
            });
        }

        function updateLikeStatus(imageId, isLiked, $icon) {
            $.post(dhs_ajax_obj.ajax_url, {
                action: 'update_like_status',
                image_id: imageId,
                is_liked: isLiked ? 1 : 0,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            }).done(function(response) {
                if (response.success) {
                    if (isLiked) {
                        $icon.addClass('liked');
                    } else {
                        $icon.removeClass('liked');
                    }
                    // 刷新页面
                    location.reload();
                } else {
                    alert('操作失败：' + (response.message || '未知错误'));
                }
            }).fail(function(xhr, status, error) {
                alert('更新失败，请稍后重试');
                console.error('更新喜欢状态时出错:', error);
            });
        }

        function updateFavoriteSelection(favoriteId, imageId, isChecked, $icon) {
            $.post(dhs_ajax_obj.ajax_url, {
                action: 'update_favorite_selection',
                favorite_id: favoriteId,
                image_id: imageId,
                is_checked: isChecked ? 1 : 0,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            }).done(function(response) {
                if (response.success) {
                    if (isChecked) {
                        $icon.css('color', 'red');
                    } else {
                        var anyChecked = $icon.closest('.favorite-select-container').find('input[type="checkbox"]:checked').length > 0;
                        if (!anyChecked) {
                            $icon.css('color', '');
                        }
                    }
                } else {
                    alert('更新收藏夹选择失败：' + (response.message || '未知错误'));
                }
            }).fail(function(xhr, status, error) {
                alert('更新失败，请稍后重试');
                console.error('更新收藏夹时出错:', error);
            });
        }

        // 初始化页面加载后的喜欢和收藏按钮
        initLikes();
        initFavorites();
    });
</script>