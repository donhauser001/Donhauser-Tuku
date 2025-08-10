<?php echo dhs_tuku_menu(); ?>
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

<?php 
// 加载相册样式和脚本文件
wp_enqueue_style('dhs-tuku-albums', DHS_TUKU_ASSETS_URL . 'css/albums.css', [], DHS_TUKU_VERSION);
wp_enqueue_script('dhs-tuku-albums', DHS_TUKU_ASSETS_URL . 'js/albums.js', ['jquery'], DHS_TUKU_VERSION, true);
?>