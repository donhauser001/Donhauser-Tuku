<style>
    .batch-auto-tags-modal {
        width: 600px;
        max-width: 90vw;
        max-height: 80vh;
        overflow-y: auto;
    }

    .batch-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 20px 10px 20px;
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
    }

    .batch-modal-header h3 {
        margin: 0;
        color: #333;
        font-size: 18px;
    }

    .batch-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .batch-modal-close:hover {
        color: #333;
    }

    .batch-modal-content {
        padding: 0 20px 20px 20px;
    }

    .batch-progress-section {
        margin-bottom: 20px;
    }

    .batch-progress-message {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }

    .batch-progress-bar-container {
        width: 100%;
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        height: 20px;
        position: relative;
    }

    .batch-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #e60023 0%, #cc001f 100%);
        border-radius: 10px;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: bold;
        min-width: 0;
    }

    .batch-results-section {
        display: none;
    }

    .batch-results-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .batch-stat-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        flex: 1;
        min-width: 120px;
    }

    .batch-stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #e60023;
        margin-bottom: 5px;
    }

    .batch-stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }

    .batch-errors-section {
        margin-top: 20px;
    }

    .batch-errors-title {
        font-size: 14px;
        font-weight: bold;
        color: #e74c3c;
        margin-bottom: 10px;
    }

    .batch-errors-list {
        max-height: 200px;
        overflow-y: auto;
        background: #fff5f5;
        border: 1px solid #fecaca;
        border-radius: 5px;
        padding: 10px;
    }

    .batch-error-item {
        font-size: 12px;
        color: #dc2626;
        padding: 2px 0;
        border-bottom: 1px solid #fecaca;
    }

    .batch-error-item:last-child {
        border-bottom: none;
    }

    .batch-success-message {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        color: #333;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .batch-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .batch-action-button {
        padding: 8px 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #fff;
        color: #333;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .batch-action-button:hover {
        background: #f5f5f5;
        border-color: #999;
    }

    .batch-action-button.primary {
        background: #e60023;
        color: white;
        border-color: #e60023;
    }

    .batch-action-button.primary:hover {
        background: #cc001f;
        border-color: #cc001f;
    }

    .batch-processing-indicator {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
    }

    .batch-spinner {
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
</style>

<div class="batch-auto-tags-modal">
    <div class="batch-modal-header">
        <h3>批量生成标签</h3>
        <button class="batch-modal-close" onclick="closeBatchAutoTagsModal()">&times;</button>
    </div>
    
    <div class="batch-modal-content">
        <!-- 标签生成方式选择 -->
        <div class="batch-tag-method-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <div class="batch-method-title" style="font-weight: bold; margin-bottom: 10px;">选择标签生成方式:</div>
            <div class="batch-method-options">
                <label style="display: block; margin-bottom: 8px;">
                    <input type="radio" name="batch_tag_method" value="auto" checked style="margin-right: 8px;">
                    <span style="color: #667eea;">🔧 自动生成标签</span>
                    <small style="display: block; margin-left: 20px; color: #666;">基于文件名和相册信息生成标签</small>
                </label>
                <label style="display: block;">
                    <input type="radio" name="batch_tag_method" value="ai" style="margin-right: 8px;">
                    <span style="color: #e60023;">🤖 AI智能标签</span>
                    <small style="display: block; margin-left: 20px; color: #666;">使用AI图像识别技术生成标签（需要网络连接）</small>
                </label>
            </div>
        </div>
        
        <!-- 进度区域 -->
        <div class="batch-progress-section" style="display: none;">
            <div class="batch-processing-indicator">
                <div class="batch-spinner"></div>
                <span>正在处理中，请稍候...</span>
            </div>
            
            <div class="batch-progress-message">准备开始...</div>
            <div class="batch-progress-bar-container">
                <div class="batch-progress-bar" style="width: 0%;">0%</div>
            </div>
        </div>
        
        <!-- 结果区域 -->
        <div class="batch-results-section">
            <div class="batch-success-message" style="display: none;"></div>
            
            <div class="batch-results-stats">
                <div class="batch-stat-item">
                    <span class="batch-stat-number" id="batch-processed-count">0</span>
                    <span class="batch-stat-label">已处理图片</span>
                </div>
                <div class="batch-stat-item">
                    <span class="batch-stat-number" id="batch-tags-count">0</span>
                    <span class="batch-stat-label">生成标签</span>
                </div>
                <div class="batch-stat-item">
                    <span class="batch-stat-number" id="batch-errors-count">0</span>
                    <span class="batch-stat-label">处理错误</span>
                </div>
            </div>
            
            <!-- 错误列表 -->
            <div class="batch-errors-section" style="display: none;">
                <div class="batch-errors-title">处理错误详情：</div>
                <div class="batch-errors-list"></div>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="batch-modal-actions">
            <button class="batch-action-button" onclick="closeBatchAutoTagsModal()">关闭</button>
            <button class="batch-action-button primary" id="startBatchProcessing" onclick="startBatchTagGeneration()">开始处理</button>
        </div>
    </div>
</div>

<script>
    // 全局变量存储当前的自动标签处理器实例
    let currentBatchProcessor = null;
    
    function closeBatchAutoTagsModal() {
        // 如果正在处理中，询问用户是否确认关闭
        if (window.currentBatchProcessor && window.currentBatchProcessor.isProcessing) {
            if (!confirm('批量处理正在进行中，确定要关闭吗？')) {
                return;
            }
        }
        
        // 调用主类的关闭方法
        if (window.currentBatchProcessor && typeof window.currentBatchProcessor.closeBatchModal === 'function') {
            window.currentBatchProcessor.closeBatchModal();
        } else {
            // 备用关闭方法
            const modalBackdrop = document.querySelector('.modal-backdrop');
            if (modalBackdrop) {
                modalBackdrop.remove();
            }
            window.currentBatchProcessor = null;
        }
    }
    
    // 开始批量标签生成
    function startBatchTagGeneration() {
        // 检查是否已有处理器实例和相册ID
        if (!window.currentBatchProcessor) {
            alert('未找到批量处理器，请重新打开模态窗');
            return;
        }
        
        if (!window.currentBatchProcessor.currentAlbumId) {
            alert('未找到相册ID，请重新打开模态窗');
            return;
        }
        
        // 隐藏标签方式选择区域
        const methodSection = document.querySelector('.batch-tag-method-section');
        if (methodSection) {
            methodSection.style.display = 'none';
        }
        
        // 显示进度区域
        const progressSection = document.querySelector('.batch-progress-section');
        if (progressSection) {
            progressSection.style.display = 'block';
        }
        
        // 隐藏结果区域
        const resultsSection = document.querySelector('.batch-results-section');
        if (resultsSection) {
            resultsSection.style.display = 'none';
        }
        
        // 更新按钮状态
        const startButton = document.getElementById('startBatchProcessing');
        if (startButton) {
            startButton.disabled = true;
            startButton.textContent = '处理中...';
        }
        
        // 开始实际的批量处理
        const albumId = window.currentBatchProcessor.currentAlbumId;
        window.currentBatchProcessor.performBatchProcessing(albumId, startButton, '开始处理');
    }
    
    // 更新批量处理进度
    window.updateBatchProgress = function updateBatchProgress(percentage, message = '') {
        console.log('模态窗口更新进度:', percentage, message); // 调试信息
        const progressBar = document.querySelector('.batch-progress-bar');
        const progressMessage = document.querySelector('.batch-progress-message');
        
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.textContent = Math.round(percentage) + '%';
            console.log('进度条已更新:', percentage + '%'); // 调试信息
        } else {
            console.warn('找不到进度条元素');
        }
        
        if (progressMessage && message) {
            progressMessage.textContent = message;
            console.log('进度消息已更新:', message); // 调试信息
        } else if (message) {
            console.warn('找不到进度消息元素');
        }
    };
    
    // 显示批量处理结果
    window.showBatchResults = function showBatchResults(data) {
        // 隐藏进度指示器
        const processingIndicator = document.querySelector('.batch-processing-indicator');
        if (processingIndicator) {
            processingIndicator.style.display = 'none';
        }
        
        // 显示结果区域
        const resultsSection = document.querySelector('.batch-results-section');
        if (resultsSection) {
            resultsSection.style.display = 'block';
        }
        
        // 更新统计数据
        document.getElementById('batch-processed-count').textContent = data.processed || 0;
        document.getElementById('batch-tags-count').textContent = data.total_tags || 0;
        document.getElementById('batch-errors-count').textContent = (data.errors && data.errors.length) || 0;
        
        // 显示成功消息
        const successMessage = document.querySelector('.batch-success-message');
        if (successMessage) {
            let message = `批量处理完成！共处理 ${data.processed || 0} 张图片，生成 ${data.total_tags || 0} 个标签`;
            
            // 如果使用了降级方案，添加说明
            if (data.fallback_count && data.fallback_count > 0) {
                if (data.ai_available === false) {
                    message += `（AI服务不可用，已使用基础算法处理）`;
                } else {
                    message += `（其中 ${data.fallback_count} 张图片使用了基础算法）`;
                }
            }
            
            successMessage.textContent = message;
            successMessage.style.display = 'block';
        }
        
        // 显示错误列表（如果有错误）
        if (data.errors && data.errors.length > 0) {
            const errorsSection = document.querySelector('.batch-errors-section');
            const errorsList = document.querySelector('.batch-errors-list');
            
            if (errorsSection && errorsList) {
                errorsSection.style.display = 'block';
                errorsList.innerHTML = data.errors.map(error => 
                    `<div class="batch-error-item">${error}</div>`
                ).join('');
            }
        }
    };
    
    // 显示批量处理错误
    window.showBatchError = function showBatchError(message) {
        // 隐藏进度指示器
        const processingIndicator = document.querySelector('.batch-processing-indicator');
        if (processingIndicator) {
            processingIndicator.style.display = 'none';
        }
        
        // 显示错误消息
        const successMessage = document.querySelector('.batch-success-message');
        if (successMessage) {
            successMessage.textContent = '处理失败: ' + message;
            successMessage.style.background = '#f8d7da';
            successMessage.style.borderColor = '#f5c6cb';
            successMessage.style.color = '#721c24';
            successMessage.style.display = 'block';
        }
    };
</script>
