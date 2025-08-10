<style>
    .init-thumbnails-modal {
        width: 600px;
        max-width: 90vw;
        max-height: 80vh;
        overflow-y: auto;
    }

    .init-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 20px 10px 20px;
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
    }

    .init-modal-header h3 {
        margin: 0;
        color: #333;
        font-size: 18px;
        font-weight: 600;
    }

    .close-modal-btn {
        background: none;
        border: none;
        font-size: 24px;
        color: #999;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .close-modal-btn:hover {
        background: #f0f0f0;
        color: #666;
    }

    .init-modal-body {
        padding: 0 20px 20px 20px;
    }

    .init-album-selection {
        margin-bottom: 20px;
    }

    .init-album-selection label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }

    .init-album-select {
        width: 100%;
        height: 45px;
        font-size: 14px;
        padding: 0 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        background: white;
        color: #333;
        transition: border-color 0.3s ease;
    }

    .init-album-select:focus {
        outline: none;
        border-color: #e60023;
        box-shadow: 0 0 0 3px rgba(230, 0, 35, 0.1);
    }

    .init-progress-container {
        margin: 20px 0;
        display: none;
    }

    .init-progress-message {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }

    .init-progress-bar-wrapper {
        width: 100%;
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        height: 30px;
        position: relative;
    }

    .init-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #e60023 0%, #cc001f 100%);
        border-radius: 10px;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
        width: 0%;
    }

    .init-results-section {
        margin-top: 20px;
        display: none;
    }

    .init-results-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .init-stat-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
    }

    .init-stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #e60023;
        margin-bottom: 5px;
    }

    .init-stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .init-success-message {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        color: #333;
        margin-bottom: 20px;
        font-weight: 500;
        display: none;
    }

    .init-error-details {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 15px;
        margin-top: 15px;
        display: none;
    }

    .init-error-details h4 {
        margin: 0 0 10px 0;
        color: #856404;
        font-size: 14px;
    }

    .init-error-item {
        padding: 8px 0;
        border-bottom: 1px solid #f5c6cb;
        color: #721c24;
        font-size: 13px;
    }

    .init-error-item:last-child {
        border-bottom: none;
    }

    .init-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .init-action-button {
        padding: 10px 20px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        color: #333;
    }

    .init-action-button:hover {
        background: #f5f5f5;
        border-color: #999;
    }

    .init-action-button.primary {
        background: #e60023;
        color: white;
        border-color: #e60023;
    }

    .init-action-button.primary:hover {
        background: #cc001f;
        border-color: #cc001f;
    }

    .init-action-button.primary:disabled {
        background: #ccc;
        border-color: #ccc;
        cursor: not-allowed;
    }

    .init-processing-indicator {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .init-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #e60023;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .password-input-section {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        display: none;
    }

    .password-input-section h4 {
        margin: 0 0 10px 0;
        color: #856404;
        font-size: 14px;
    }

    .password-input-field {
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .password-input-field:focus {
        outline: none;
        border-color: #e60023;
        box-shadow: 0 0 0 2px rgba(230, 0, 35, 0.1);
    }
</style>

<div class="init-thumbnails-modal">
    <div class="init-modal-header">
        <h3>初始化素材集预览图</h3>
        <button class="close-modal-btn" onclick="closeInitThumbnailsModal()">×</button>
    </div>
    
    <div class="init-modal-body">
        <!-- 相册选择区域 -->
        <div class="init-album-selection">
            <label for="initAlbumSelect">选择要初始化的素材集：</label>
            <select id="initAlbumSelect" class="init-album-select">
                <option value="" disabled selected>请选择素材集</option>
                <option value="newAlbum">新建素材集</option>
            </select>
        </div>

        <!-- 密码验证区域 -->
        <div class="password-input-section" id="passwordSection">
            <h4>管理员验证</h4>
            <input type="password" id="adminPassword" class="password-input-field" placeholder="请输入管理员密码">
        </div>
        
        <!-- 进度指示器 -->
        <div class="init-progress-container" id="initProgressContainer">
            <div class="init-processing-indicator">
                <div class="init-spinner"></div>
                <span>正在处理中，请稍候...</span>
            </div>
            
            <div class="init-progress-message">准备开始...</div>
            <div class="init-progress-bar-wrapper">
                <div class="init-progress-bar" style="width: 0%;">0%</div>
            </div>
        </div>
        
        <!-- 结果区域 -->
        <div class="init-results-section" id="initResultsSection">
            <div class="init-success-message" id="initSuccessMessage">
                <!-- 成功消息将在这里显示 -->
            </div>
            
            <div class="init-results-stats">
                <div class="init-stat-item">
                    <span class="init-stat-number" id="processedFiles">0</span>
                    <span class="init-stat-label">处理文件</span>
                </div>
                <div class="init-stat-item">
                    <span class="init-stat-number" id="createdThumbnails">0</span>
                    <span class="init-stat-label">创建预览图</span>
                </div>
                <div class="init-stat-item">
                    <span class="init-stat-number" id="errorCount">0</span>
                    <span class="init-stat-label">处理错误</span>
                </div>
            </div>
            
            <div class="init-error-details" id="initErrorDetails" style="display: none;">
                <h4>错误详情:</h4>
                <div id="initErrorList"></div>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="init-modal-actions">
            <button class="init-action-button" onclick="closeInitThumbnailsModal()">取消</button>
            <button class="init-action-button primary" id="startInitButton" onclick="startInitialization()">开始初始化</button>
        </div>
    </div>
</div>

<script>
    let isInitializing = false;
    
    // 全局函数供外部调用
    window.updateInitProgress = function updateInitProgress(percentage, message = '') {
        console.log('更新初始化进度:', percentage, message);
        const progressBar = document.querySelector('.init-progress-bar');
        const progressMessage = document.querySelector('.init-progress-message');
        
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.textContent = Math.round(percentage) + '%';
        }
        
        if (progressMessage && message) {
            progressMessage.textContent = message;
        }
    };
    
    window.showInitResults = function showInitResults(data) {
        // 隐藏进度指示器
        const progressContainer = document.getElementById('initProgressContainer');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        
        // 显示结果区域
        const resultsSection = document.getElementById('initResultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'block';
        }
        
        // 更新统计数据
        document.getElementById('processedFiles').textContent = data.processed || 0;
        document.getElementById('createdThumbnails').textContent = data.thumbnails || 0;
        document.getElementById('errorCount').textContent = (data.errors && data.errors.length) || 0;
        
        // 显示成功消息
        const successMessage = document.getElementById('initSuccessMessage');
        if (successMessage) {
            successMessage.textContent = `初始化完成！共处理 ${data.processed || 0} 个文件，创建 ${data.thumbnails || 0} 个预览图`;
            successMessage.style.display = 'block';
        }
        
        // 显示错误列表（如果有错误）
        if (data.errors && data.errors.length > 0) {
            const errorDetails = document.getElementById('initErrorDetails');
            const errorList = document.getElementById('initErrorList');
            if (errorDetails && errorList) {
                errorDetails.style.display = 'block';
                errorList.innerHTML = data.errors.map(error => 
                    `<div class="init-error-item">${error}</div>`
                ).join('');
            }
        }
        
        // 更新按钮状态
        const startButton = document.getElementById('startInitButton');
        if (startButton) {
            startButton.textContent = '完成';
            startButton.disabled = false;
        }
        
        isInitializing = false;
    };
    
    window.showInitError = function showInitError(message) {
        // 隐藏进度指示器
        const progressContainer = document.getElementById('initProgressContainer');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        
        // 显示错误消息
        const successMessage = document.getElementById('initSuccessMessage');
        if (successMessage) {
            successMessage.textContent = '初始化失败: ' + message;
            successMessage.style.background = '#f8d7da';
            successMessage.style.borderColor = '#f5c6cb';
            successMessage.style.color = '#721c24';
            successMessage.style.display = 'block';
        }
        
        // 更新按钮状态
        const startButton = document.getElementById('startInitButton');
        if (startButton) {
            startButton.textContent = '重试';
            startButton.disabled = false;
        }
        
        isInitializing = false;
    };
    
    function closeInitThumbnailsModal() {
        if (isInitializing && !confirm('初始化正在进行中，确定要关闭吗？')) {
            return;
        }
        
        const modalBackdrop = document.querySelector('.modal-backdrop');
        if (modalBackdrop) {
            modalBackdrop.remove();
        }
        isInitializing = false;
    }
    
    function startInitialization() {
        const albumSelect = document.getElementById('initAlbumSelect');
        const passwordInput = document.getElementById('adminPassword');
        const startButton = document.getElementById('startInitButton');
        
        const selectedAlbumId = albumSelect.value;
        const password = passwordInput.value;
        
        if (!selectedAlbumId || selectedAlbumId === '') {
            alert('请选择一个素材集');
            return;
        }
        
        if (!password) {
            // 显示密码输入区域
            document.getElementById('passwordSection').style.display = 'block';
            return;
        }
        
        if (isInitializing) return;
        
        isInitializing = true;
        startButton.disabled = true;
        startButton.textContent = '初始化中...';
        
        // 显示进度区域
        document.getElementById('initProgressContainer').style.display = 'block';
        document.getElementById('initResultsSection').style.display = 'none';
        
        // 开始初始化过程
        performInitialization(selectedAlbumId, password);
    }
    
    async function performInitialization(albumId, password) {
        try {
            // 1. 验证密码
            updateInitProgress(5, '验证管理员密码...');
            const passwordVerified = await verifyPassword(password);
            if (!passwordVerified) {
                showInitError('密码错误或验证失败');
                return;
            }
            
            // 2. 删除相册条目
            updateInitProgress(15, '清理相册数据...');
            await deleteAlbumEntries(albumId);
            
            // 3. 删除缩略图文件夹
            updateInitProgress(30, '删除旧的预览图...');
            await deleteThumbnailsFolder(albumId);
            
            // 4. 添加时间戳
            updateInitProgress(50, '添加文件时间戳...');
            await appendTimestamp(albumId);
            
            // 5. 处理文件和插入数据库
            updateInitProgress(70, '处理文件并更新数据库...');
            const result = await processFilesAndInsert(albumId);
            
            updateInitProgress(100, '初始化完成');
            
            // 显示结果
            showInitResults({
                processed: result.processed || 0,
                thumbnails: result.thumbnails || 0,
                errors: result.errors || []
            });
            
        } catch (error) {
            console.error('初始化过程中出错:', error);
            showInitError('初始化过程中出现错误: ' + error.message);
        }
    }
    
    // 辅助函数 - 将原有的 fetch 调用封装为 Promise
    function verifyPassword(password) {
        return fetch(dhs_ajax_obj.ajax_url + '?action=verify_user_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                user_id: 1,
                password: password,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
        .then(response => response.json())
        .then(data => data.success);
    }
    
    function deleteAlbumEntries(albumId) {
        return fetch(dhs_ajax_obj.ajax_url + '?action=delete_album_entries', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error('删除相册条目失败');
            return data;
        });
    }
    
    function deleteThumbnailsFolder(albumId) {
        return fetch(dhs_ajax_obj.ajax_url + '?action=delete_thumbnails_folder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
        .then(response => response.json())
        .then(data => data); // 允许失败，继续执行
    }
    
    function appendTimestamp(albumId) {
        return fetch(dhs_ajax_obj.ajax_url + '?action=append_timestamp_to_files', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error('添加时间戳失败');
            return data;
        });
    }
    
    function processFilesAndInsert(albumId) {
        return fetch(dhs_ajax_obj.ajax_url + '?action=process_files_and_insert', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error('文件处理失败');
            return data.data || {};
        });
    }
    
    // 页面加载时获取相册列表
    document.addEventListener('DOMContentLoaded', function() {
        const albumSelect = document.getElementById('initAlbumSelect');
        
        // 加载素材集列表
        fetch(dhs_ajax_obj.ajax_url + '?action=dhs_get_album_list', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ _wpnonce: dhs_ajax_obj.dhs_nonce })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(album => {
                    const option = document.createElement('option');
                    option.value = album.id;
                    option.textContent = album.album_name;
                    albumSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading albums:', error));
    });
</script>
