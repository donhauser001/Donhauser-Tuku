<h5>上传素材</h5>
<form id="uploadForm">
    <!-- 文件设置区域 -->
    <div id="fileSettings" class="file-settings">
        <label for="albumSelect">选择素材集：</label>
        <select id="albumSelect">
            <!-- 动态填充素材集选项 -->
        </select>

        <!-- 新建素材集区域，默认隐藏 -->
        <div id="newAlbumContainer" class="new-album-container" style="display: none;">
            <div class="album-name-category">
                <div class="album-name">
                    <label for="newAlbumName">素材集名称：</label>
                    <input type="text" id="newAlbumName" placeholder="输入素材集名称">
                </div>
                <div class="album-category">
                    <label for="categorySelect">素材集分类：</label>
                    <div class="custom-select-wrapper">
                        <select id="categorySelect">
                            <option value="">选择分类</option>
                            <!-- 动态填充分类选项 -->
                            <option value="new">新建分类</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 新建分类区域，默认隐藏 -->
            <div id="newCategoryContainer" class="new-category-container" style="display: none;">
                <div class="category-name-parent">
                    <div id="categoryNameContainer" class="category-name">
                        <label for="newCategoryName">分类名称：</label>
                        <input type="text" id="newCategoryName" placeholder="输入分类名称">
                    </div>
                    <div id="parentCategoryContainer" class="category-parent">
                        <label for="parentCategorySelect">父类别：</label>
                        <select id="parentCategorySelect">
                            <option value="none">无</option>
                            <!-- 动态加载父分类选项 -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- 素材集描述 -->
            <div class="album-description">
                <label for="newAlbumDescription">素材集描述：</label>
                <textarea id="newAlbumDescription" rows="3" placeholder="输入素材集描述"></textarea>
            </div>
        </div>
    </div>

    <!-- 文件拖动区 -->
    <div id="dropZone" class="drop-zone">
        <p>将文件拖到这里，或点击选择文件</p>
        <input type="file" id="fileInput" multiple style="display: none;">
    </div>
    <div class="button-container">
        <button type="button" id="clearListBtn">清空列表</button>
        <button type="button" id="associateSameNameBtn">关联同名文件</button>
    </div>
    <!-- 文件列表区域 -->
    <div id="fileList" class="file-list"></div>

    <!-- 操作按钮 -->
    <div class="button-container">
        <button type="button" id="uploadFilesBtn">确定上传</button>
        <button type="button" id="clearListBtn">清空列表</button>
    </div>
</form>