<style>
    .generate-thumbnails-modal {
        width: 600px;
        max-width: 90vw;
        max-height: 80vh;
        overflow-y: auto;
    }

    .thumbnails-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 20px 10px 20px;
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
    }

    .thumbnails-modal-header h3 {
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

    .thumbnails-modal-body {
        padding: 0 20px 20px 20px;
    }

    .thumbnails-progress-container {
        margin: 20px 0;
        display: none;
    }

    .thumbnails-progress-message {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }

    .thumbnails-progress-bar-wrapper {
        width: 100%;
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        height: 30px;
        position: relative;
    }

    .thumbnails-progress-bar {
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

    .thumbnails-results-section {
        margin-top: 20px;
        display: none;
    }

    .thumbnails-results-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .thumbnails-stat-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
    }

    .thumbnails-stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #e60023;
        margin-bottom: 5px;
    }

    .thumbnails-stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .thumbnails-success-message {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        color: #333;
        margin-bottom: 20px;
        font-weight: 500;
        display: none;
    }

    .thumbnails-error-details {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 15px;
        margin-top: 15px;
        display: none;
    }

    .thumbnails-error-details h4 {
        margin: 0 0 10px 0;
        color: #856404;
        font-size: 14px;
    }

    .thumbnails-error-item {
        padding: 8px 0;
        border-bottom: 1px solid #f5c6cb;
        color: #721c24;
        font-size: 13px;
    }

    .thumbnails-error-item:last-child {
        border-bottom: none;
    }

    .thumbnails-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .thumbnails-action-button {
        padding: 10px 20px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        color: #333;
    }

    .thumbnails-action-button:hover {
        background: #f5f5f5;
        border-color: #999;
    }

    .thumbnails-action-button.primary {
        background: #e60023;
        color: white;
        border-color: #e60023;
    }

    .thumbnails-action-button.primary:hover {
        background: #cc001f;
        border-color: #cc001f;
    }

    .thumbnails-action-button.primary:disabled {
        background: #ccc;
        border-color: #ccc;
        cursor: not-allowed;
    }

    .thumbnails-processing-indicator {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .thumbnails-spinner {
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

    .album-info-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .album-info-section h4 {
        margin: 0 0 10px 0;
        color: #495057;
        font-size: 14px;
    }

    .album-info-section p {
        margin: 5px 0;
        color: #6c757d;
        font-size: 13px;
    }
</style>

<div class="generate-thumbnails-modal">
    <div class="thumbnails-modal-header">
        <h3>生成相册预览图</h3>
        <button class="close-modal-btn" onclick="closeGenerateThumbnailsModal()">×</button>
    </div>
    
    <div class="thumbnails-modal-body">
        <!-- 相册信息区域 -->
        <div class="album-info-section">
            <h4>相册信息</h4>
            <p id="albumNameDisplay">相册名称: -</p>
            <p id="imageCountDisplay">图片数量: -</p>
            <p>即将为此相册的所有图片生成预览图</p>
        </div>
        
        <!-- 进度指示器 -->
        <div class="thumbnails-progress-container" id="thumbnailsProgressContainer">
            <div class="thumbnails-processing-indicator">
                <div class="thumbnails-spinner"></div>
                <span>正在生成预览图，请稍候...</span>
            </div>
            
            <div class="thumbnails-progress-message">准备开始...</div>
            <div class="thumbnails-progress-bar-wrapper">
                <div class="thumbnails-progress-bar" style="width: 0%;">0%</div>
            </div>
        </div>
        
        <!-- 结果区域 -->
        <div class="thumbnails-results-section" id="thumbnailsResultsSection">
            <div class="thumbnails-success-message" id="thumbnailsSuccessMessage">
                <!-- 成功消息将在这里显示 -->
            </div>
            
            <div class="thumbnails-results-stats">
                <div class="thumbnails-stat-item">
                    <span class="thumbnails-stat-number" id="processedImages">0</span>
                    <span class="thumbnails-stat-label">已处理图片</span>
                </div>
                <div class="thumbnails-stat-item">
                    <span class="thumbnails-stat-number" id="successfulThumbnails">0</span>
                    <span class="thumbnails-stat-label">成功生成</span>
                </div>
                <div class="thumbnails-stat-item">
                    <span class="thumbnails-stat-number" id="failedThumbnails">0</span>
                    <span class="thumbnails-stat-label">生成失败</span>
                </div>
            </div>
            
            <div class="thumbnails-error-details" id="thumbnailsErrorDetails" style="display: none;">
                <h4>失败详情:</h4>
                <div id="thumbnailsErrorList"></div>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="thumbnails-modal-actions">
            <button class="thumbnails-action-button" onclick="closeGenerateThumbnailsModal()">取消</button>
            <button class="thumbnails-action-button primary" id="startThumbnailsButton" onclick="startThumbnailGeneration()">开始生成</button>
        </div>
    </div>
</div>

<script>
    let isGeneratingThumbnails = false;
    let currentAlbumId = null;
    
    // 全局函数供外部调用
    window.updateThumbnailProgress = function updateThumbnailProgress(percentage, message = '', current = 0, total = 0) {
        console.log('更新预览图生成进度:', percentage, message);
        const progressBar = document.querySelector('.thumbnails-progress-bar');
        const progressMessage = document.querySelector('.thumbnails-progress-message');
        
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.textContent = Math.round(percentage) + '%';
        }
        
        if (progressMessage) {
            if (message) {
                progressMessage.textContent = message;
            } else if (current > 0 && total > 0) {
                progressMessage.textContent = `正在处理第 ${current} 张，共 ${total} 张图片...`;
            }
        }
    };
    
    window.showThumbnailResults = function showThumbnailResults(data) {
        // 隐藏进度指示器
        const progressContainer = document.getElementById('thumbnailsProgressContainer');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        
        // 显示结果区域
        const resultsSection = document.getElementById('thumbnailsResultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'block';
        }
        
        // 更新统计数据
        document.getElementById('processedImages').textContent = data.processed || 0;
        document.getElementById('successfulThumbnails').textContent = data.successful || 0;
        document.getElementById('failedThumbnails').textContent = (data.errors && data.errors.length) || 0;
        
        // 显示成功消息
        const successMessage = document.getElementById('thumbnailsSuccessMessage');
        if (successMessage) {
            successMessage.textContent = `预览图生成完成！共处理 ${data.processed || 0} 张图片，成功生成 ${data.successful || 0} 个预览图`;
            successMessage.style.display = 'block';
        }
        
        // 显示错误列表（如果有错误）
        if (data.errors && data.errors.length > 0) {
            const errorDetails = document.getElementById('thumbnailsErrorDetails');
            const errorList = document.getElementById('thumbnailsErrorList');
            if (errorDetails && errorList) {
                errorDetails.style.display = 'block';
                errorList.innerHTML = data.errors.map(error => 
                    `<div class="thumbnails-error-item">${error}</div>`
                ).join('');
            }
        }
        
        // 更新按钮状态
        const startButton = document.getElementById('startThumbnailsButton');
        if (startButton) {
            startButton.textContent = '完成';
            startButton.disabled = false;
            startButton.onclick = function() {
                closeGenerateThumbnailsModal(true); // 传递参数表示需要刷新
            };
        }
        
        isGeneratingThumbnails = false;
    };
    
    window.showThumbnailError = function showThumbnailError(message) {
        // 隐藏进度指示器
        const progressContainer = document.getElementById('thumbnailsProgressContainer');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        
        // 显示错误消息
        const successMessage = document.getElementById('thumbnailsSuccessMessage');
        if (successMessage) {
            successMessage.textContent = '预览图生成失败: ' + message;
            successMessage.style.background = '#f8d7da';
            successMessage.style.borderColor = '#f5c6cb';
            successMessage.style.color = '#721c24';
            successMessage.style.display = 'block';
        }
        
        // 更新按钮状态
        const startButton = document.getElementById('startThumbnailsButton');
        if (startButton) {
            startButton.textContent = '重试';
            startButton.disabled = false;
            startButton.onclick = function() {
                startThumbnailGeneration();
            };
        }
        
        isGeneratingThumbnails = false;
    };
    
    function closeGenerateThumbnailsModal(shouldRefresh = false) {
        if (isGeneratingThumbnails && !confirm('预览图生成正在进行中，确定要关闭吗？')) {
            return;
        }
        
        const modalBackdrop = document.querySelector('.modal-backdrop');
        if (modalBackdrop) {
            modalBackdrop.remove();
        }
        isGeneratingThumbnails = false;
        
        // 如果需要刷新页面（生成完成后）
        if (shouldRefresh) {
            window.location.reload();
        }
    }
    
    function startThumbnailGeneration() {
        if (!currentAlbumId) {
            alert('缺少相册ID');
            return;
        }
        
        if (isGeneratingThumbnails) return;
        
        isGeneratingThumbnails = true;
        const startButton = document.getElementById('startThumbnailsButton');
        startButton.disabled = true;
        startButton.textContent = '生成中...';
        
        // 显示进度区域
        document.getElementById('thumbnailsProgressContainer').style.display = 'block';
        document.getElementById('thumbnailsResultsSection').style.display = 'none';
        
        // 开始生成过程
        performThumbnailGeneration(currentAlbumId);
    }
    
    async function performThumbnailGeneration(albumId) {
        try {
            // 开始生成缩略图
            updateThumbnailProgress(10, '开始生成缩略图...');
            
            const response = await fetch(dhs_ajax_obj.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'generate_thumbnails',
                    album_id: albumId,
                    _ajax_nonce: dhs_ajax_obj.dhs_nonce
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data ? data.data.message : '生成失败');
            }
            
            // 显示100%进度
            updateThumbnailProgress(100, '预览图生成完成');
            
            // 显示最终结果
            showThumbnailResults({
                processed: data.data.processed || 0,
                successful: data.data.processed || 0,
                errors: data.data.errors || []
            });
            
        } catch (error) {
            console.error('预览图生成过程中出错:', error);
            showThumbnailError('生成过程中出现错误: ' + error.message);
        }
    }
    

    
    // 设置相册信息
    window.setAlbumInfo = function(albumId, albumName, imageCount) {
        currentAlbumId = albumId;
        document.getElementById('albumNameDisplay').textContent = `相册名称: ${albumName}`;
        document.getElementById('imageCountDisplay').textContent = `图片数量: ${imageCount} 张`;
    };
</script>
