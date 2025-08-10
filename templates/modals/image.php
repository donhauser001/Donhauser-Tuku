<style>
    .image-content {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        border-radius: 10px;
        background-color: #fff;
    }


    .caozuo {
        display: flex;
        flex-direction: row;
        align-items: baseline;
        border-radius: 10px;
        background-color: #fff;
        margin-bottom: 20px;
    }

    .image-section {
        max-width: 700px;
        align-items: center;
        background-color: #f0f0f0;
        border-radius: 4px;
        overflow: hidden;
        margin-right: 40px;
    }

    .image-section img {
        max-width: 100%;
        max-height: 100%;
    }


    .details-section {
        flex: 1;
        color: #333;
        display: flex;
        flex-direction: column;
    }

    .file-info {
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .file-info p {
        font-size: 14px;
        margin: 5px 0;
    }

    .file-down p {
        font-size: 14px;
        margin: 5px 0;
    }

    .file-down {
        border-radius: 4px;
        margin-bottom: 0;
    }

    .file-buttons {
        display: flex;
        flex-wrap: wrap;
    }

    .file-buttons button {
        font-size: 14px !important;
        color: #4f5564;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 50px;
        border: 1px solid #ccc;
        background-color: #fff;
        transition: background-color 0.3s ease;
        margin-right: 10px;
        margin-bottom: 10px;

    }

    .file-buttons button:hover {
        background-color: #f6f6f6;
        border: 1px solid #4f5564;
    }




    .fengmian-buttons button {
        font-size: 14px !important;
        color: #4f5564;
        padding: 10px 10px;
        cursor: pointer;
        border-radius: 50px;
        background-color: #f1f1f1;
        transition: margin-right 0.6s, background-color 0.3s ease;
        margin-right: 10px;
    }

    .fengmian-buttons button:hover {
        background-color: #4f5564;
        color: #fff;
        margin-right: 30px;
    }



    .delete-button button {
        font-size: 14px !important;
        color: #4f5564;
        padding: 10px 10px;
        cursor: pointer;
        border-radius: 50px;
        background-color: #f1f1f1;
        transition: background-color 0.3s ease;
        margin-right: 10px;
    }

    .delete-button button:hover {
        background-color: #4f5564;
        color: #fff;
    }



    .dropdown {
        position: relative;
        display: inline-block;
        margin-top: 20px;
        margin-bottom: 20px;
    }

    .dropdown button {
        font-size: 14px !important;
        color: #4f5564;
        padding: 10px 10px;
        cursor: pointer;
        border-radius: 50px;
        background-color: #f1f1f1;
        transition: margin-right 0.6s, background-color 0.3s ease;
        margin-right: 10px;
    }

    .dropdown button:hover {
        background-color: #4f5564;
        color: #fff;
        margin-right: 30px;
    }

    #outer-container button {
        font-size: 14px !important;
        color: #4f5564;
        padding: 5px 8px;
        cursor: pointer;
        border-radius: 3px;
        background-color: #f1f1f1;
        transition: color 0.3s, background-color 0.3s ease;
        white-space: nowrap;
        /* 防止文字换行 */
    }

    #outer-container button:hover {
        color: #fff;

        background-color: #b0b0b0;

    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #fff;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        border-radius: 5px;
        overflow: hidden;
        height: 200px;
        /* 设定固定高度 */
        overflow-y: auto;
        /* 启用垂直滚动条 */
    }

    .dropdown-content label {
        display: block;
        padding: 10px;
        cursor: pointer;
        color: #333;
    }

    .dropdown-content label:hover {
        background-color: #f1f1f1;
    }

    .dropdown-content input[type="checkbox"] {
        margin-right: 10px;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    .upload-button {
        margin-top: 20px;
    }

    .upload-button input[type="file"] {
        display: none;
    }

    .upload-button label {
        font-size: 14px !important;
        color: #4f5564;
        padding: 10px 10px;
        cursor: pointer;
        border-radius: 50px;
        background-color: #f1f1f1;
        transition: margin-right 0.6s, background-color 0.3s ease;
        margin-right: 10px;
    }

    .upload-button label:hover {
        background-color: #4f5564;
        color: #fff;
        margin-right: 30px;
    }

    /* 工具提示容器 */
    .tooltip {
        position: relative;
        display: inline-block;
    }

    .create-favorite-btn {
        font-size: 14px !important;
        width: 100%;
        color: #4f5564 !important;
        padding: 15px 10px !important;
        cursor: pointer;
        background-color: #fff !important;
        transition: background-color 0.3s ease;
        border-radius: 0 !important;
        box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, 0.1) !important;

    }


    .create-favorite-btn:hover {
        color: #fff !important;
        background-color: #4f5564 !important;

    }

    #tags-wrapper {
        font-size: 14px;
    }

    #tags-wrapper a {
        color: #000;
    }

    #tags-wrapper a:hover {
        font-weight: bold;
    }

    #tags {
        margin-right: 10px;
        margin-bottom: 10px;
    }

    /* 工具提示文本 */
    .tooltip .tooltiptext {
        visibility: hidden;
        width: 120px;
        background-color: #333;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        /* 位置调整到按钮上方 */
        left: 15px;
        margin-left: -60px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 12px;
    }

    /* 工具提示箭头 */
    .tooltip .tooltiptext::after {
        content: '';
        position: absolute;
        top: 100%;
        /* 位于工具提示的底部 */
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
    }

    /* 鼠标悬停时显示工具提示文本 */
    .tooltip:hover .tooltiptext {
        visibility: visible;
        opacity: 1;
    }



    #input-container {
        display: flex;
        flex-direction: row;
        align-items: center;
    }

    #input-container input {
        font-size: 14px !important;
        height: 30px;

    }

    .modal-window {
        position: fixed;
        left: 50%;
        top: 15%;
        transform: translate(-50%, -50%);
        background-color: #fff;
        padding: 20px;
        box-shadow: 0 0 50px rgba(0, 0, 0, 0.5);
        z-index: 1000;
        border-radius: 10px;
    }

    .modal-window p {
        margin-bottom: 10px;
        font-size: 16px;
        font-weight: bold;
    }

    /* 复选框和标签样式 */
    input[type="checkbox"] {
        margin-right: 10px;
    }

    label {
        font-size: 14px;
        margin-right: 15px;
    }



    .confirm-button,
    .cancel-button {
        border-radius: 4px;
        color: #4f5564;
        text-decoration: none;
        font-size: 14px;
        padding: 5px 10px;
        transition: background-color 0.3s ease, border-color 0.3s ease;
        border: 1px solid #dcdcdc;
        margin: 20px 10px 0 0;
        background-color: #fff;

    }



    .confirm-button:hover {
        background-color: #e6e6e6;
        border: 1px solid #4f5564;
        color: #4f5564;
    }

    .cancel-button:hover {
        background-color: #e6e6e6;
        border: 1px solid #4f5564;
        color: #4f5564;
    }
</style>


<div class="image-content">
    <div class="image-section">
        <img id="dynamicImage" src="" alt="Image" />
    </div>
    <div class="details-section">
        <div class="caozuo">
            <?php if (is_user_logged_in()) : ?>
                <!-- 带复选框的下拉菜单 -->
                <div class="dropdown tooltip">
                    <button class="dropbtn">
                        <i class="fas fa-star"></i>
                    </button>
                    <span class="tooltiptext">收藏素材</span>
                    <div class="dropdown-content">
                        <!-- 在这里插入"新建收藏夹"按钮 -->
                        <button id="createNewFavorite" class="create-favorite-btn">新建收藏夹</button>
                        <!-- 收藏项会通过 JavaScript 动态插入到这里 -->
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (is_user_logged_in()): ?>
            <!-- 设为素材集封面按钮 -->
            <div class="fengmian-buttons tooltip">
                <button>
                    <i class="fas fa-image"></i>
                </button>
                <span class="tooltiptext">设为素材集封面</span>
            </div>

            <!-- 上传预览图按钮 -->
            <div class="upload-button tooltip">
                <label for="uploadPreview">
                    <i class="fas fa-upload"></i>
                </label>
                <span class="tooltiptext">更新预览图</span>
                <input type="file" id="uploadPreview" accept="image/*" style="display: none;" />
            </div>

            <!-- 删除按钮 -->
            <div class="delete-button tooltip">
                <button id="deleteButton">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <span class="tooltiptext">删除素材</span>
            </div>
            <?php endif; ?>
        </div>
        <div class="file-info">
            <p><strong>文件名:</strong> <span id="fileName">加载中...</span></p>
            <p><strong>文件ID:</strong> <span id="fileId">加载中...</span></p>
            <p><strong>素材集名称:</strong> <span id="albumName">加载中...</span></p>
            <p><strong>上传时间:</strong> <span id="uploadTime">加载中...</span></p>
            <p><strong>分类:</strong> <span id="category">加载中...</span></p>
        </div>
        <div class="file-down">
            <div>
                <p><strong>下载源文件</strong></p>
            </div>

            <!-- 关联文件按钮 -->
            <div class="file-buttons" id="fileButtonsContainer">
                <!-- 下载按钮会通过JavaScript动态加载到这里，对所有用户可见 -->
                <?php if (is_user_logged_in()): ?>
                <!-- "关联其他文件" 按钮 -->
                <button id="associateFileButton">关联</button>
                <?php endif; ?>
            </div>

            <?php if (is_user_logged_in()): ?>
            <!-- 文件选择器，用于关联其他文件 -->
            <input type="file" id="fileSelector" style="display: none;" multiple />
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    (function() {
        let albumId; // 定义 albumId 变量

        // 确认 image_id 存在
        if (imageId) {
            fetchImageDetails(imageId);
        } else {
            console.error('未获取到有效的 image_id');
            alert('未获取到有效的 image_id');
        }

        function fetchImageDetails(imageId) {
            // AJAX 请求
            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'get_image_details',
                        image_id: imageId,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const imageData = data.data;

                        // 获取 albumId 用于后续关联文件
                        window.albumId = imageData.album_id; // 确保 albumId 被正确赋值

                        // 更新图片和信息
                        document.getElementById('fileId').textContent = imageData.image_id || 'N/A';
                        document.getElementById('dynamicImage').src = imageData.image_url || 'https://your-default-image-url';
                        document.getElementById('fileName').textContent = imageData.file_name || 'N/A';
                        document.getElementById('uploadTime').textContent = imageData.upload_time || 'N/A';
                        document.getElementById('category').textContent = imageData.category || '未分类';
                        document.getElementById('albumName').textContent = imageData.album_name || '未知相册';

                        // 动态设置删除按钮的数据属性（仅限登录用户）
                        <?php if (is_user_logged_in()): ?>
                        const deleteButton = document.getElementById('deleteButton');
                        if (deleteButton) {
                            deleteButton.setAttribute('data-album-id', window.albumId); // 使用 window.albumId
                            deleteButton.setAttribute('data-file-name', imageData.file_name);
                        }
                        <?php endif; ?>

                        // 查找并清除旧的标签和输入框部分
                        const existingTagsWrapper = document.getElementById('tags-wrapper');
                        if (existingTagsWrapper) {
                            existingTagsWrapper.remove();
                        }

                        // 处理标签的显示
                        const tagsContainer = document.createElement('span');
                        tagsContainer.setAttribute('id', 'tags');
                        tagsContainer.innerHTML = ''; // 清空标签容器

                        // 插入标签文本
                        if (imageData.tags && imageData.tags.length > 0) {
                            imageData.tags.forEach(tag => {
                                <?php if (is_user_logged_in()): ?>
                                // 登录用户：创建可点击的链接
                                const tagLink = document.createElement('a');
                                const albumName = imageData.album_name || '未知相册';
                                const albumId = imageData.album_id;

                                tagLink.href = '#';
                                tagLink.textContent = tag;
                                tagLink.style.marginRight = '10px';
                                tagLink.addEventListener('click', function(event) {
                                    event.preventDefault();
                                    showTagOptions(tag, imageId, albumId, albumName);
                                });
                                tagsContainer.appendChild(tagLink);
                                <?php else: ?>
                                // 未登录用户：创建静态文本
                                const tagSpan = document.createElement('span');
                                tagSpan.textContent = tag;
                                tagSpan.style.marginRight = '10px';
                                tagSpan.style.color = '#666';
                                tagSpan.style.background = '#f5f5f5';
                                tagSpan.style.padding = '2px 8px';
                                tagSpan.style.borderRadius = '3px';
                                tagSpan.style.fontSize = '12px';
                                tagsContainer.appendChild(tagSpan);
                                <?php endif; ?>
                            });
                        }

                        // 创建按钮容器和输入框容器
                        const outerContainer = document.createElement('div');
                        outerContainer.setAttribute('id', 'outer-container');
                        outerContainer.style.display = 'inline-block';

                        // 检查用户是否已登录（只有登录用户才能编辑标签）
                        <?php if (is_user_logged_in()): ?>
                        // 创建"编辑标签"按钮
                        const showTagInputButton = document.createElement('button');
                        showTagInputButton.textContent = imageData.tags && imageData.tags.length > 0 ? '编辑标签' : '添加标签';
                        showTagInputButton.addEventListener('click', function() {
                            // 隐藏"编辑标签"按钮
                            showTagInputButton.style.display = 'none';

                            // 隐藏已有标签
                            tagsContainer.style.display = 'none';

                            // 显示输入框和确认按钮
                            tagInput.style.display = 'inline-block';
                            addTagButton.style.display = 'inline-block';
                            tagInput.focus(); // 激活输入框

                            // 如果已有标签，填充输入框以供编辑
                            if (imageData.tags && imageData.tags.length > 0) {
                                tagInput.value = imageData.tags.join('，');
                            }
                        });

                        // 创建"自动生成标签"按钮
                        const autoTagButton = document.createElement('button');
                        autoTagButton.textContent = '自动生成标签';
                        autoTagButton.className = 'auto-tag-single';
                        autoTagButton.dataset.imageId = imageId;
                        autoTagButton.style.marginLeft = '10px';
                        autoTagButton.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        autoTagButton.style.color = 'white';
                        autoTagButton.style.border = 'none';
                        autoTagButton.style.padding = '6px 12px';
                        autoTagButton.style.borderRadius = '4px';
                        autoTagButton.style.cursor = 'pointer';
                        autoTagButton.style.fontSize = '12px';

                        // 创建"AI智能标签"按钮
                        const aiTagButton = document.createElement('button');
                        aiTagButton.textContent = 'AI智能标签';
                        aiTagButton.className = 'ai-tag-single';
                        aiTagButton.dataset.imageId = imageId;
                        aiTagButton.style.marginLeft = '10px';
                        aiTagButton.style.background = 'linear-gradient(135deg, #e60023 0%, #cc001f 100%)';
                        aiTagButton.style.color = 'white';
                        aiTagButton.style.border = 'none';
                        aiTagButton.style.padding = '6px 12px';
                        aiTagButton.style.borderRadius = '4px';
                        aiTagButton.style.cursor = 'pointer';
                        aiTagButton.style.fontSize = '12px';

                        outerContainer.appendChild(showTagInputButton);
                        outerContainer.appendChild(autoTagButton);
                        outerContainer.appendChild(aiTagButton);
                        <?php endif; ?>

                        // 创建输入框容器
                        const inputContainer = document.createElement('div');
                        inputContainer.setAttribute('id', 'input-container');

                        <?php if (is_user_logged_in()): ?>
                        // 添加输入框并默认隐藏
                        const tagInput = document.createElement('input');
                        tagInput.setAttribute('type', 'text');
                        tagInput.setAttribute('id', 'tag-input');
                        tagInput.setAttribute('placeholder', '输入新标签');
                        tagInput.style.display = 'none';

                        // 添加确认按钮并默认隐藏
                        const addTagButton = document.createElement('button');
                        addTagButton.textContent = '确认';
                        addTagButton.style.display = 'none';

                        // 定义操作的函数
                        function executeConfirmAction() {
                            addTag(tagInput.value.trim(), imageId)
                                .then(() => {
                                    tagInput.value = ''; // 清空输入框

                                    // 隐藏输入框和确认按钮
                                    tagInput.style.display = 'none';
                                    addTagButton.style.display = 'none';

                                    // 重新加载标签部分
                                    fetchImageDetails(imageId);
                                });
                        }

                        // 监听点击“确认”按钮事件
                        addTagButton.addEventListener('click', executeConfirmAction);

                        // 添加按回车键事件监听器
                        tagInput.addEventListener('keydown', function(event) {
                            if (event.key === 'Enter') {
                                executeConfirmAction();
                            }
                        });

                        inputContainer.appendChild(tagInput);
                        inputContainer.appendChild(addTagButton);
                        <?php endif; ?>

                        // 将按钮容器和输入框容器插入到 outerContainer 中
                        outerContainer.appendChild(inputContainer);

                        // 创建标签包装器并插入所有元素
                        const tagsWrapper = document.createElement('div');
                        tagsWrapper.setAttribute('id', 'tags-wrapper');
                        tagsWrapper.innerHTML = `<strong>标签:</strong> `;
                        tagsWrapper.appendChild(tagsContainer);
                        tagsWrapper.appendChild(outerContainer);

                        // 将标签包装器插入到页面中合适的位置
                        const detailsSection = document.querySelector('.details-section .file-info');
                        detailsSection.appendChild(tagsWrapper);

                        // 动态生成文件按钮
                        const fileButtonsContainer = document.getElementById('fileButtonsContainer');
                        fileButtonsContainer.innerHTML = ''; // 清空按钮容器

                        // 插入文件按钮
                        imageData.buttons.forEach(button => {
                            // 如果 URL 是无效的（例如 '#', 空字符串），跳过处理
                            if (button.url && button.url !== '#') {
                                try {
                                    const urlObject = new URL(button.url);
                                    const finalUrl = `${urlObject.origin}${urlObject.pathname}${urlObject.search}${urlObject.hash}`;

                                    const btn = document.createElement('button');
                                    btn.textContent = button.type; // 显示文件类型（例如 PSD, AI, JPG）
                                    btn.onclick = () => {
                                        window.location.href = finalUrl; // 使用原始的下载链接
                                    };

                                    fileButtonsContainer.appendChild(btn);
                                } catch (e) {
                                    console.error('Invalid URL:', button.url); // 如果 URL 无效，输出错误信息
                                }
                            } else {
                                console.warn('Skipping invalid or placeholder URL:', button.url); // 输出跳过的 URL 信息
                            }
                        });

                        // 插入 "关联其他文件" 按钮（仅限登录用户）
                        <?php if (is_user_logged_in()): ?>
                        const associateFileButton = document.createElement('button');
                        associateFileButton.textContent = "添加新的";
                        associateFileButton.onclick = () => {
                            document.getElementById('fileSelector').click(); // 打开文件选择窗口
                        };
                        fileButtonsContainer.appendChild(associateFileButton);
                        <?php endif; ?>

                        // 只有登录用户才获取并显示用户收藏
                        <?php if (is_user_logged_in()) : ?>
                            fetchUserFavorites();
                        <?php endif; ?>

                    } else {
                        alert('加载图片详情失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('请求错误:', error);
                });
        }

        function setAsCover() {
            // 从页面的文件名字段提取文件名
            let fileName = document.getElementById('fileName').textContent.trim();

            if (fileName) {
                // 替换文件名中的 # 为 %23
                fileName = fileName.replace(/#/g, '%23');

                // 获取当前文件预览图地址
                const imageUrl = document.getElementById('dynamicImage').src;

                // 提取相册 ID
                const albumIdMatch = imageUrl.match(/\/tuku\/(\d+)\//);
                const albumId = albumIdMatch ? albumIdMatch[1] : null;

                if (albumId) {
                    // 构造新的文件路径：保持文件名不变，仅添加 _thumbnail.jpg 后缀
                    const newRelativePath = `/tuku/${albumId}/thumbnails/${fileName}_thumbnail.jpg`;

                    console.log('New relative path to be stored:', newRelativePath);

                    fetch(dhs_ajax_obj.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'update_album_cover',
                                album_id: albumId,
                                cover_image: newRelativePath,
                                _ajax_nonce: dhs_ajax_obj.dhs_nonce
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('相册封面已更新');
                            } else {
                                alert('更新相册封面失败：' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('更新相册封面时出错:', error);
                            alert('更新失败，请稍后重试');
                        });
                } else {
                    alert('未找到相册 ID，无法更新封面');
                }
            } else {
                alert('未能提取文件名，无法更新封面');
            }
        }



        function showTagOptions(tag, imageId, albumId, albumName) {
            // 创建提示窗口
            const modal = document.createElement('div');
            modal.classList.add('modal-window');

            const message = document.createElement('p');
            message.textContent = `你要对标签 "${tag}" 进行什么操作：`;

            const deleteCheckbox = document.createElement('input');
            deleteCheckbox.type = 'checkbox';
            deleteCheckbox.id = 'deleteTag';
            const deleteLabel = document.createElement('label');
            deleteLabel.htmlFor = 'deleteTag';
            deleteLabel.textContent = '删除标签';

            const applyToAllCheckbox = document.createElement('input');
            applyToAllCheckbox.type = 'checkbox';
            applyToAllCheckbox.id = 'applyToAll';
            const applyToAllLabel = document.createElement('label');
            applyToAllLabel.htmlFor = 'applyToAll';
            applyToAllLabel.textContent = `应用到 "${albumName}" 素材集所有项目中`;

            // 确保两个选项不能同时选择
            deleteCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    applyToAllCheckbox.checked = false;
                }
            });

            applyToAllCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    deleteCheckbox.checked = false;
                }
            });

            // 创建“确定”按钮
            const confirmButton = document.createElement('button');
            confirmButton.textContent = '确定';
            confirmButton.classList.add('confirm-button');

            // 定义操作的函数
            function executeConfirmAction() {
                if (deleteCheckbox.checked) {
                    deleteTag(tag, imageId)
                        .then(() => {
                            if (applyToAllCheckbox.checked) {
                                return applyTagToAllItems(tag, albumId);
                            }
                        })
                        .finally(() => {
                            document.body.removeChild(modal); // 关闭提示窗口
                            document.removeEventListener('keydown', handleKeyDown); // 移除键盘事件监听器
                            fetchImageDetails(imageId); // 重新加载标签部分
                        });
                } else if (applyToAllCheckbox.checked) {
                    applyTagToAllItems(tag, albumId)
                        .finally(() => {
                            document.body.removeChild(modal); // 关闭提示窗口
                            document.removeEventListener('keydown', handleKeyDown); // 移除键盘事件监听器
                            fetchImageDetails(imageId); // 重新加载标签部分
                        });
                } else {
                    document.body.removeChild(modal); // 关闭提示窗口
                    document.removeEventListener('keydown', handleKeyDown); // 移除键盘事件监听器
                }
            }

            // 定义按回车键的事件处理函数
            function handleKeyDown(event) {
                if (event.key === 'Enter') {
                    executeConfirmAction();
                }
            }

            // 添加按回车键事件监听器
            document.addEventListener('keydown', handleKeyDown);

            // 添加按钮的点击事件监听器
            confirmButton.addEventListener('click', executeConfirmAction);

            const cancelButton = document.createElement('button');
            cancelButton.textContent = '取消';
            cancelButton.classList.add('cancel-button');
            cancelButton.addEventListener('click', function() {
                document.body.removeChild(modal); // 关闭提示窗口
                document.removeEventListener('keydown', handleKeyDown); // 移除键盘事件监听器
            });

            modal.appendChild(message);
            modal.appendChild(deleteCheckbox);
            modal.appendChild(deleteLabel);
            modal.appendChild(document.createElement('br'));
            modal.appendChild(applyToAllCheckbox);
            modal.appendChild(applyToAllLabel);
            modal.appendChild(document.createElement('br'));
            modal.appendChild(confirmButton);
            modal.appendChild(cancelButton);

            document.body.appendChild(modal);
        }

        function deleteTag(tag, imageId) {
            return fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'delete_image_tag',
                        image_id: imageId,
                        tag: tag,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('标签已删除');
                    } else {
                        throw new Error('标签删除失败');
                    }
                })
                .catch(error => {
                    console.error('删除标签时出错:', error);
                    alert('请求失败，请稍后重试。');
                });
        }

        function applyTagToAllItems(tag, albumId) {
            return fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'apply_tag_to_all',
                        album_id: albumId,
                        tag: tag,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('标签已应用到所有项目');
                    } else {
                        throw new Error('应用标签失败');
                    }
                })
                .catch(error => {
                    console.error('应用标签时出错:', error);
                    alert('请求失败，请稍后重试。');
                });
        }

        function addTag(tag, imageId) {
            if (!tag) {
                alert('请输入标签内容');
                return Promise.reject(); // 添加返回值以供链式调用
            }

            // 将标签中的中文逗号、顿号、空格拆分为不同的标签
            const tagsArray = tag.split(/[,，、\s]+/).filter(t => t.length > 0);

            // 发送标签更新请求
            return fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'update_image_tags',
                        image_id: imageId,
                        tags: JSON.stringify(tagsArray),
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {} else {
                        throw new Error('标签更新失败');
                    }
                })
                .catch(error => {
                    console.error('更新标签时出错:', error);
                    alert('请求失败，请稍后重试。');
                });
        }

        function fetchUserFavorites() {
            // 检查用户是否已登录（通过检查页面上是否存在收藏按钮）
            const favoriteButton = document.querySelector('.dropdown.tooltip');
            if (!favoriteButton) {
                console.log('用户未登录，跳过收藏功能加载');
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

                        const dropdownContent = document.querySelector('.dropdown-content');
                        if (!dropdownContent) {
                            console.log('收藏下拉菜单不存在，用户未登录');
                            return;
                        }
                        
                        dropdownContent.innerHTML = ''; // 清空已有的内容

                        // 插入"新建收藏夹"按钮
                        const createButton = document.createElement('button');
                        createButton.textContent = '新建收藏夹';
                        createButton.classList.add('create-favorite-btn');
                        createButton.onclick = createNewFavorite;
                        dropdownContent.appendChild(createButton);

                        if (Array.isArray(favorites) && favorites.length > 0) {
                            favorites.forEach(favorite => {
                                const label = document.createElement('label');
                                const checkbox = document.createElement('input');
                                checkbox.type = 'checkbox';
                                checkbox.value = favorite.id;
                                checkbox.checked = favorite.associated_image_ids.includes(imageId.toString()); // 检查该收藏夹是否关联了当前图片
                                label.appendChild(checkbox);
                                label.appendChild(document.createTextNode(' ' + favorite.name));
                                dropdownContent.appendChild(label);
                            });

                            // 绑定事件
                            bindCheckboxEvents();
                        } else {
                            const message = document.createElement('p');
                            message.textContent = '没有收藏项';
                            dropdownContent.appendChild(message);
                        }
                    } else {
                        // 不再显示错误提示，静默处理失败情况
                        console.log('获取收藏项失败：' + (data.message || '没有收藏项'));
                    }
                })
                .catch(error => {
                    console.error('请求错误:', error);
                    // 不再显示错误提示，静默处理错误
                });
        }

        function createNewFavorite() {
            const favoriteName = prompt("请输入新收藏夹的名称：");
            if (favoriteName) {
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
                            alert('新建收藏夹成功！');
                            // 重新加载收藏夹列表
                            fetchUserFavorites();
                        } else {
                            alert('新建收藏夹失败：' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('新建收藏夹时出错:', error);
                        alert('新建失败，请稍后重试');
                    });
            }
        }



        function bindCheckboxEvents() {
            document.querySelectorAll('.dropdown-content input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const favoriteId = checkbox.value;
                    const isChecked = checkbox.checked;

                    fetch(dhs_ajax_obj.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'update_favorite_selection',
                                favorite_id: favoriteId,
                                image_id: imageId, // 当前图片的 ID
                                is_checked: isChecked ? 1 : 0, // 1 表示选中，0 表示取消选中
                                _ajax_nonce: dhs_ajax_obj.dhs_nonce
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {} else {
                                alert('更新收藏夹选择失败：' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('更新失败，请稍后重试');
                        });
                });
            });
        }


        // 监听文件选择器的变化（仅限登录用户）
        <?php if (is_user_logged_in()): ?>
        document.getElementById('fileSelector').addEventListener('change', function(event) {
            const files = event.target.files;
            if (files.length > 0) {
                const file = files[0];



                // 调用检查文件是否存在的函数
                checkFileExists(file.name).then(fileExists => {
                    console.log('文件是否存在:', fileExists); // 调试：输出文件是否存在的结果

                    if (fileExists) {
                        // 提示用户选择操作
                        const userChoice = prompt(`文件 ${file.name} 已经存在。你希望？\n1. 覆盖文件\n2. 重命名文件\n3. 取消上传`, '1');

                        if (userChoice === '3') {
                            return; // 用户选择取消上传，终止逻辑
                        }

                        if (userChoice === '2') {
                            const newFileName = generateNewFileName(file.name);
                            uploadFile(newFileName, file); // 使用新文件名上传
                            return;
                        }

                        if (userChoice === '1') {
                            uploadFile(file.name, file); // 覆盖原文件上传
                            return;
                        }
                    } else {
                        // 文件不存在，直接上传
                        const confirmation = confirm(`你确定要关联 ${file.name} 文件吗？`);
                        if (!confirmation) {
                            return; // 用户取消关联
                        }

                        uploadFile(file.name, file); // 上传文件
                    }
                }).catch(error => {
                    console.error('检查文件是否存在时出错:', error);
                });
            }
        });
        <?php endif; ?>

        // 生成重命名文件的新文件名
        function generateNewFileName(fileName) {
            const fileParts = fileName.split('.');
            const extension = fileParts.pop();
            const baseName = fileParts.join('.');
            const newFileName = `${baseName}_${Date.now()}.${extension}`;
            return newFileName; // 在文件名上添加时间戳作为后缀
        }
        // 上传文件的函数
        // 上传文件的函数
        function uploadFile(fileName, file) {
            const albumId = window.albumId; // 获取全局变量

            const formData = new FormData();
            formData.append('action', 'associate_file');
            formData.append('image_id', imageId);
            formData.append('album_id', albumId); // 使用获取到的 albumId
            formData.append('file', file);
            formData.append('file_name', fileName); // 使用重命名的文件名
            formData.append('_ajax_nonce', dhs_ajax_obj.dhs_nonce);

            // 发起 AJAX 请求上传文件
            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    return response.json();
                })
                .then(data => {

                    if (data.success) {
                        alert('文件关联成功！请刷新页面查看更新。');
                    } else {
                        alert('文件关联失败: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('文件上传失败');
                });
        }

        // 检查文件是否存在的函数
        function checkFileExists(fileName) {
            const albumId = window.albumId; // 获取全局变量
            console.log('检查文件存在的相册 ID:', albumId); // 调试：输出相册 ID
            console.log('检查的文件名:', fileName); // 调试：输出文件名

            return fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'check_file_exists',
                        album_id: albumId,
                        file_name: fileName,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                })
                .then(response => {
                    console.log('检查文件存在的响应状态:', response.status); // 调试：输出响应状态
                    return response.json();
                })
                .then(data => {
                    // 确认服务器是否返回了 exists 值
                    console.log('检查文件存在的响应数据:', data); // 调试：输出服务器响应数据

                    if (data.success && typeof data.data.exists !== 'undefined') {
                        return data.data.exists; // 使用正确的 exists 值
                    } else {
                        console.error('未找到 exists 值，服务器响应:', data);
                        return false; // 如果未能正确返回 exists 值，默认为不存在
                    }
                })
                .catch(error => {
                    console.error('请求错误:', error);
                    return false;
                });
        }

        const dropdownContent = document.querySelector('.dropdown-content');

        // 删除按钮事件监听器（仅限登录用户）
        <?php if (is_user_logged_in()): ?>
        document.getElementById('deleteButton').addEventListener('click', function() {
            const albumId = this.getAttribute('data-album-id');
            const imageName = this.getAttribute('data-file-name');

            if (!albumId || !imageName) {
                alert('无法获取相册ID或文件名，无法删除');
                return;
            }

            const userConfirmed = confirm('确认要删除这个素材吗？');
            if (userConfirmed) {
                fetch(dhs_ajax_obj.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'delete_images',
                            album_id: albumId,
                            images: imageName,
                            _ajax_nonce: dhs_ajax_obj.dhs_nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('图片已成功删除');

                            // 移除图片内容元素
                            const imageContentElement = document.querySelector('.image-content');
                            if (imageContentElement) {
                                imageContentElement.remove();
                            } else {
                                console.warn('找不到 .image-content 元素，无法移除');
                            }

                            // 关闭模态窗口
                            const modalElement = document.querySelector('.modal');
                            if (modalElement) {
                                modalElement.remove();
                            } else {
                                console.warn('找不到 .modal 元素，无法关闭');
                            }

                            // 刷新页面
                            window.location.reload();
                        } else {
                            alert('删除失败: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('删除图片时出错:', error);
                        alert('删除失败，请稍后重试。');
                    });
            }
        });
        <?php endif; ?>

        // 上传预览图事件监听器（仅限登录用户）
        <?php if (is_user_logged_in()): ?>
        document.getElementById('uploadPreview').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const fileName = document.getElementById('fileName').textContent.trim();
                const fileExtension = file.type === 'image/png' ? 'jpg' : file.name.split('.').pop();
                const newFileName = fileName + '_thumbnail.' + fileExtension;

                if (!window.albumId) {
                    console.error('相册 ID 未设置，请检查相关代码。');
                    alert('无法获取相册 ID，无法上传预览图');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'upload_thumbnail_image');
                formData.append('album_id', window.albumId);
                formData.append('file_name', newFileName);
                formData.append('file', file);
                formData.append('_ajax_nonce', dhs_ajax_obj.dhs_nonce);

                fetch(dhs_ajax_obj.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('预览图更新成功');

                            // 强制刷新预览图
                            const dynamicImage = document.getElementById('dynamicImage');
                            const currentSrc = dynamicImage.src;
                            const newSrc = currentSrc.split('?')[0] + '?' + new Date().getTime(); // 加时间戳强制刷新
                            dynamicImage.src = newSrc;
                        } else {
                            alert('更新预览图失败：' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('更新预览图时出错:', error);
                        alert('更新失败，请稍后重试');
                    });
            } else {
                console.log('未选择文件');
            }
        });
        <?php endif; ?>
        
        // 绑定事件到按钮（仅限登录用户）
        <?php if (is_user_logged_in()): ?>
        document.querySelector('.fengmian-buttons button').addEventListener('click', setAsCover);
        <?php endif; ?>
    })();
</script>