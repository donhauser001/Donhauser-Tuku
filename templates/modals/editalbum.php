<h5>编辑素材集</h5>
<form id="editalbumForm">
    <!-- 文件设置区域 -->
    <div id="fileSettings" class="file-settings">
        <!-- 新建素材集区域，默认隐藏 -->
        <div id="newAlbumContainer" class="new-album-container">
            <div class="album-name-category">
                <div class="album-name">
                    <label for="AlbumName">素材集名称：</label>
                    <input type="text" id="AlbumName" name="album_name" placeholder="输入素材集名称" required value="<?php echo esc_attr($album->name); ?>">
                </div>
                <div class="album-category">
                    <label for="categorySelect">素材集分类：</label>
                    <div class="custom-select-wrapper">
                        <select id="categorySelect" name="album_category" required>
                            <option value="">选择分类</option>
                            <option value="new" <?php selected($album->category, 'new'); ?>>新建分类</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 新建分类区域，默认隐藏 -->
            <div id="newCategoryContainer" class="new-category-container" style="display: none;">
                <div class="category-name-parent">
                    <div id="categoryNameContainer" class="category-name">
                        <label for="newCategoryName">分类名称：</label>
                        <input type="text" id="newCategoryName" name="new_category_name" placeholder="输入分类名称">
                    </div>
                    <div id="parentCategoryContainer" class="category-parent">
                        <label for="parentCategorySelect">父类别：</label>
                        <select id="parentCategorySelect" name="parent_category">
                            <option value="none">无</option>
                            <!-- 动态加载父分类选项 -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- 素材集描述 -->
            <div class="album-description">
                <label for="newAlbumDescription">素材集描述：</label>
                <textarea id="newAlbumDescription" name="album_description" rows="3" placeholder="输入素材集描述"><?php echo esc_textarea($album->description); ?></textarea>
            </div>

            <!-- 相册封面上传 -->
            <div class="album-cover">
                <label for="albumCover">素材集封面：</label>
                <input type="file" id="albumCover" name="album_cover" accept="image/*">
                <!-- 封面预览 -->
                <div class="cover-preview">
                    <img id="coverPreview" src="#" alt="封面预览" style="display: none; max-width: 200px; height: auto;">
                </div>
            </div>
        </div>
    </div>

    <!-- 操作按钮 -->
    <div class="button-container">
        <button type="button" id="uploadFilesBtn">更新设置</button>
    </div>
</form>