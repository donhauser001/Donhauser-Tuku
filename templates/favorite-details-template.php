<div class="favorite-container">
    <h2><?php echo esc_html($favorite->name); ?></h2>
    <p>由<?php echo esc_html(get_the_author_meta('display_name', $favorite->user_id)); ?> 在 <?php echo esc_html(date('Y-m-d', strtotime($favorite->created_at))); ?> 创建</p>
</div>

<div class="dhs-album-images-grid" id="favoriteImagesGrid">
    <?php foreach ($images as $image) :
        $album_id = esc_attr($image->album_id);
        $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . $image->name . '_thumbnail.jpg';
        $thumbnail_url = site_url('/wp-content/uploads/tuku/' . $album_id . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');

        if (!file_exists($thumbnail_path)) {
            $thumbnail_url = site_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg'); // 默认缩略图
        }
    ?>
        <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>" data-album-id="<?php echo $album_id; ?>" data-favorite-id="<?php echo $favorite_id; ?>">
            <div class="dhs-image-icons">
                <a href="javascript:void(0)" class="favorite-icon" title="取消收藏" data-image-id="<?php echo esc_attr($image->id); ?>">
                    <i class="fa fa-star"></i>
                </a>
            </div>
            <a href="javascript:void(0)" class="chakanimage dhs-tuku-open-modal" data-modal="image:50" data-image-id="<?php echo esc_attr($image->id); ?>">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($image->name); ?>" />
            </a>
        </div>
    <?php endforeach; ?>
</div>



<script>
    jQuery(document).ready(function($) {
        var $grid = $('#favoriteImagesGrid');
        var loadMoreButton = $('#loadMoreButton');

        function initMasonry() {
            $grid.imagesLoaded(function() {
                $grid.masonry({
                    itemSelector: '.dhs-album-image-item',
                    percentPosition: true,
                    columnWidth: '.dhs-album-image-item',
                    gutter: 15
                });
            });
        }

        initMasonry();



        function initFavorites() {
            $('.favorite-icon').each(function() {
                $(this).css('color', 'red');

                $(this).off('click').on('click', function(event) {
                    event.preventDefault();
                    var $icon = $(this);
                    var imageId = $icon.closest('.dhs-album-image-item').data('image-id');
                    var favoriteId = $icon.closest('.dhs-album-image-item').data('favorite-id');

                    console.log('Favorite ID:', favoriteId); // 调试信息
                    console.log('Image ID:', imageId); // 调试信息

                    // 取消收藏
                    updateFavoriteSelection(favoriteId, imageId, false, $icon);
                });
            });
        }

        function updateFavoriteSelection(favoriteId, imageId, isChecked, $icon) {
            console.log('Sending AJAX request with data:', {
                action: 'update_favorite_selection',
                favorite_id: favoriteId,
                image_id: imageId,
                is_checked: isChecked ? 1 : 0,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            }); // 调试信息

            $.post(dhs_ajax_obj.ajax_url, {
                action: 'update_favorite_selection',
                favorite_id: favoriteId,
                image_id: imageId,
                is_checked: isChecked ? 1 : 0,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            }).done(function(response) {
                console.log('Server response:', response); // 调试信息
                if (response.success) {
                    // 刷新页面以更新状态
                    location.reload();
                } else {
                    alert('操作失败：' + (response.message || '未知错误'));
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error); // 调试信息
                alert('更新失败，请稍后重试');
            });
        }

        initFavorites();
    });
</script>