<div class="dhs-album-details">
    <div class="album-container">
        <div class="header-container">
            <h2><?php echo esc_html($album->album_name); ?></h2>
            <p><?php echo esc_html(get_the_author_meta('display_name', $album->created_by)); ?> - <?php echo esc_html(date('Y-m-d', strtotime($album->created_at))); ?></p>
        </div>
        <div class="meta-container">
            <?php if (is_user_logged_in()): ?>
            <!-- 垃圾桶图标按钮 -->
            <div class="trash">
                <button id="delete-images" title="删除图片">
                    <i class="fa fa-trash"></i>
                </button>
                <div class="selection-actions" style="display:none;">
                    <button id="select-all">全选</button><span class="xian">|</span>
                    <button id="deselect-all">取消</button> <button id="delete-check" data-album-id="<?php echo esc_attr($album_id); ?>">删除</button>

                </div>
            </div>
            <button id="generate-thumbnails" data-album-id="<?php echo esc_attr($album_id); ?>">初始化预览图</button>
            <button id="generate-auto-tags" class="auto-tag-batch" data-album-id="<?php echo esc_attr($album_id); ?>" title="批量生成标签">
                批量生成标签
            </button>
            <?php endif; ?>
        </div>
        
        <!-- 自动标签结果区域 -->
        <?php if (is_user_logged_in()): ?>
        <div class="auto-tagger-results-container" style="display:none;">
            <div class="auto-tag-progress">
                <div class="progress-message">准备开始...</div>
                <div class="progress-bar-container">
                    <div class="progress-bar">0%</div>
                </div>
            </div>
            
            <div class="auto-tag-batch-results"></div>
        </div>
        <?php endif; ?>
    </div>
    <!-- 图片列表 -->
    <div class="dhs-album-images-grid" id="albumImagesGrid" data-album-id="<?php echo esc_attr($album_id); ?>">
        <?php foreach ($images as $image) :
            $image_data = json_decode($image->file_data, true);
            $thumbnail_path = ABSPATH . 'wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . $image->name . '_thumbnail.jpg';
            $thumbnail_url = dhs_tuku_get_secure_url('/wp-content/uploads/tuku/' . esc_attr($album_id) . '/thumbnails/' . rawurlencode($image->name) . '_thumbnail.jpg');

            if (!file_exists($thumbnail_path)) {
                $thumbnail_url = dhs_tuku_get_secure_url('/wp-content/plugins/dhs-tuku/assets/images/default-thumbnail.jpg'); // 默认缩略图
            }
        ?>
            <div class="dhs-album-image-item" data-image-id="<?php echo esc_attr($image->id); ?>" data-image-name="<?php echo esc_attr($image->name); ?>">
                <?php if (is_user_logged_in()) : ?>
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
                <?php endif; ?>

                <a href="javascript:void(0)" class="chakanimage dhs-tuku-open-modal" data-modal="image:50" data-image-id="<?php echo esc_attr($image->id); ?>">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($image->name); ?>" />
                </a>
                <div class="progress-bar-container" style="display:none;">
                    <div class="progress-bar"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>



</div>