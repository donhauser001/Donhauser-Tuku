/**
 * DHS Tuku 自动标签生成器 JavaScript
 */

class AutoTagger {
    constructor() {
        this.isProcessing = false;
        this.batchProgress = 0;
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // 单个图片自动标签
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('auto-tag-single')) {
                e.preventDefault();
                const imageId = e.target.dataset.imageId;
                if (imageId) {
                    this.generateSingleAutoTags(imageId);
                }
            }
        });

        // 单个图片AI标签
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('ai-tag-single')) {
                e.preventDefault();
                const imageId = e.target.dataset.imageId;
                if (imageId) {
                    this.generateSingleAITags(imageId);
                }
            }
        });

        // 批量自动标签
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('auto-tag-batch') || e.target.id === 'generate-auto-tags') {
                e.preventDefault();
                const albumId = e.target.dataset.albumId || 0;
                this.generateBatchAutoTags(albumId);
            }
        });
    }

    /**
     * 为单个图片生成自动标签
     */
    async generateSingleAutoTags(imageId) {
        if (this.isProcessing) return;

        this.isProcessing = true;
        const button = document.querySelector(`[data-image-id="${imageId}"]`);
        const originalText = button.innerHTML;

        // 更新按钮状态
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 生成中...';

        try {
            // 调试信息
            console.log('发送AJAX请求:', {
                url: dhs_ajax_obj.ajax_url,
                imageId: imageId,
                nonce: dhs_ajax_obj.dhs_nonce
            });

            const response = await fetch(dhs_ajax_obj.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'generate_auto_tags',
                    image_id: imageId,
                    auto_confirm: 'true',
                    _ajax_nonce: dhs_ajax_obj.dhs_nonce
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const data = await response.json();

            if (data.success) {
                this.showAutoTagResults(data.data, button);
                this.showSuccess(`成功生成 ${data.data.count} 个标签`);
            } else {
                const errorMessage = data.data ? data.data.message : data.message || '自动标签生成失败';
                this.showError(errorMessage);
            }
        } catch (error) {
            console.error('自动标签生成错误:', error);
            this.showError('网络错误，请重试');
        } finally {
            // 恢复按钮状态
            button.disabled = false;
            button.innerHTML = originalText;
            this.isProcessing = false;
        }
    }

    /**
     * 为单个图片生成AI标签
     */
    async generateSingleAITags(imageId) {
        if (this.isProcessing) return;

        this.isProcessing = true;
        const button = document.querySelector(`.ai-tag-single[data-image-id="${imageId}"]`);
        const originalText = button.innerHTML;

        // 更新按钮状态
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI生成中...';

        try {
            console.log('发送AI标签生成请求:', {
                url: dhs_ajax_obj.ajax_url,
                imageId: imageId,
                nonce: dhs_ajax_obj.dhs_nonce
            });

            const response = await fetch(dhs_ajax_obj.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'generate_ai_tags',
                    image_id: imageId,
                    _ajax_nonce: dhs_ajax_obj.dhs_nonce
                })
            });

            const data = await response.json();

            if (data.success) {
                // 显示成功消息
                this.showSuccess(`AI成功生成 ${data.data.tags.length} 个标签`);

                // 刷新图片详情以显示新标签
                if (typeof fetchImageDetails === 'function') {
                    fetchImageDetails(imageId);
                }
            } else {
                // 处理错误响应，确保正确显示错误信息
                const errorMessage = data.data?.message || data.data || 'AI标签生成失败';
                this.showError(errorMessage);
            }
        } catch (error) {
            console.error('AI标签生成错误:', error);
            this.showError('网络错误，请重试');
        } finally {
            // 恢复按钮状态
            button.disabled = false;
            button.innerHTML = originalText;
            this.isProcessing = false;
        }
    }

    /**
     * 批量生成自动标签
     */
    async generateBatchAutoTags(albumId = 0) {
        if (this.isProcessing) return;

        // 只打开模态窗，不立即开始处理
        await this.openBatchModal();

        // 存储相册ID供后续使用
        this.currentAlbumId = albumId;
    }

    /**
     * 执行批量处理
     */
    async performBatchProcessing(albumId, button, originalText) {
        let offset = 0;
        let totalProcessed = 0;
        let totalTags = 0;
        let allErrors = [];

        try {
            // 等待一小段时间确保模态窗口DOM完全加载
            await new Promise(resolve => setTimeout(resolve, 100));

            // 检测用户选择的标签生成方式
            const selectedMethod = document.querySelector('input[name="batch_tag_method"]:checked')?.value || 'auto';
            const action = selectedMethod === 'ai' ? 'batch_generate_ai_tags' : 'batch_generate_auto_tags';
            const methodName = selectedMethod === 'ai' ? 'AI智能标签' : '自动生成标签';

            this.updateBatchProgress(0, `开始${methodName}批量处理...`);

            do {
                const response = await fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: action,
                        album_id: albumId,
                        limit: selectedMethod === 'ai' ? 5 : 10, // AI处理较慢，减少批量大小
                        offset: offset,
                        _ajax_nonce: dhs_ajax_obj.dhs_nonce
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.data.message || '批量处理失败');
                }

                const result = data.data;
                totalProcessed += result.processed;
                totalTags += result.total_tags;
                allErrors = allErrors.concat(result.errors || []);

                // 更新进度
                if (result.has_more) {
                    offset = result.next_offset;
                    const progress = Math.min((offset / (offset + 10)) * 100, 95);
                    this.updateBatchProgress(progress, `已处理 ${totalProcessed} 张图片...`);
                } else {
                    this.updateBatchProgress(100, '处理完成');
                    break;
                }

                // 添加小延迟避免服务器过载
                await new Promise(resolve => setTimeout(resolve, 500));

            } while (true);

            // 显示最终结果
            this.showBatchModalResults({
                processed: totalProcessed,
                total_tags: totalTags,
                errors: allErrors
            });

        } catch (error) {
            console.error('批量自动标签生成错误:', error);
            this.showBatchModalError('批量处理失败: ' + error.message);
        } finally {
            // 恢复按钮状态
            if (button) {
                button.disabled = false;
                button.textContent = '开始处理';
            }
            this.isProcessing = false;
        }
    }

    /**
     * 显示结果容器
     */
    showResultsContainer() {
        const resultsContainer = document.querySelector('.auto-tagger-results-container');
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
        }
    }

    /**
     * 隐藏结果容器
     */
    hideResultsContainer() {
        const resultsContainer = document.querySelector('.auto-tagger-results-container');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }

    /**
     * 显示进度条
     */
    showProgressBar() {
        const progressContainer = document.querySelector('.auto-tag-progress');
        if (progressContainer) {
            progressContainer.style.display = 'block';
        }
    }

    /**
     * 隐藏进度条
     */
    hideProgressBar() {
        const progressContainer = document.querySelector('.auto-tag-progress');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
    }

    /**
     * 更新进度
     */
    updateProgress(percentage, message = '') {
        const progressBar = document.querySelector('.progress-bar');
        const progressMessage = document.querySelector('.progress-message');

        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.textContent = Math.round(percentage) + '%';
        }

        if (progressMessage && message) {
            progressMessage.textContent = message;
        }
    }

    /**
     * 显示单个图片的自动标签结果
     */
    showAutoTagResults(data, button) {
        // 在按钮附近显示生成的标签
        const existingResults = button.parentNode.querySelector('.auto-tag-results');
        if (existingResults) {
            existingResults.remove();
        }

        if (data.tags && data.tags.length > 0) {
            const resultsDiv = document.createElement('div');
            resultsDiv.className = 'auto-tag-results';
            resultsDiv.style.display = 'block';

            resultsDiv.innerHTML = `
                <h4>生成的标签:</h4>
                <div class="generated-tags">
                    ${data.tags.map(tag =>
                `<span class="generated-tag new-tag">
                            <i class="fas fa-tag"></i>
                            ${this.escapeHtml(tag)}
                        </span>`
            ).join('')}
                </div>
            `;

            button.parentNode.appendChild(resultsDiv);
        }
    }

    /**
     * 显示批量处理结果
     */
    showBatchResults(data) {
        const resultsContainer = document.querySelector('.auto-tag-batch-results');
        if (!resultsContainer) return;

        resultsContainer.style.display = 'block';
        resultsContainer.innerHTML = `
            <div class="auto-tag-stats">
                <div class="stat-item">
                    <span class="stat-number">${data.processed}</span>
                    <span class="stat-label">已处理图片</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${data.total_tags}</span>
                    <span class="stat-label">生成标签</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${data.errors.length}</span>
                    <span class="stat-label">错误数量</span>
                </div>
            </div>
            ${data.errors.length > 0 ? `
                <div class="auto-tag-errors" style="display: block;">
                    <h4>处理错误:</h4>
                    <ul class="error-list">
                        ${data.errors.slice(0, 10).map(error =>
            `<li>${this.escapeHtml(error)}</li>`
        ).join('')}
                        ${data.errors.length > 10 ? `<li>...还有 ${data.errors.length - 10} 个错误</li>` : ''}
                    </ul>
                </div>
            ` : ''}
        `;
    }

    /**
     * 显示成功消息
     */
    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    /**
     * 显示错误消息
     */
    showError(message) {
        this.showMessage(message, 'error');
    }

    /**
     * 显示消息
     */
    showMessage(message, type = 'info') {
        // 创建消息元素
        const messageDiv = document.createElement('div');
        messageDiv.className = `auto-tag-message auto-tag-message-${type}`;
        messageDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${this.escapeHtml(message)}
        `;

        // 添加样式
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
            color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
            border-radius: 6px;
            padding: 12px 16px;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;

        document.body.appendChild(messageDiv);

        // 自动移除消息
        setTimeout(() => {
            messageDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 5000);
    }

    /**
     * HTML转义
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 打开批量处理模态窗口
     */
    openBatchModal() {
        return new Promise((resolve, reject) => {
            // 创建模态窗口容器
            const modalBackdrop = document.createElement('div');
            modalBackdrop.className = 'modal-backdrop';
            modalBackdrop.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            `;

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.cssText = `
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                max-width: 90vw;
                max-height: 90vh;
                overflow: hidden;
            `;

            // 加载模态窗口内容
            fetch(`${window.location.origin}/wp-content/plugins/dhs-tuku/templates/modals/modal-handler.php?modal=batch-auto-tags`)
                .then(response => response.text())
                .then(html => {
                    console.log('模态窗口HTML已加载，长度:', html.length); // 调试信息
                    modal.innerHTML = html;
                    modalBackdrop.appendChild(modal);
                    document.body.appendChild(modalBackdrop);

                    // 手动执行脚本标签 - innerHTML 不会自动执行 script 标签
                    const scripts = modal.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            newScript.src = script.src;
                        } else {
                            newScript.textContent = script.textContent;
                        }
                        document.head.appendChild(newScript);
                        document.head.removeChild(newScript); // 立即移除，因为已经执行
                    });

                    // 设置全局处理器引用
                    window.currentBatchProcessor = this;

                    // 等待DOM更新和脚本执行
                    setTimeout(() => {
                        console.log('检查全局函数是否可用:');
                        console.log('- updateBatchProgress:', typeof window.updateBatchProgress);
                        console.log('- showBatchResults:', typeof window.showBatchResults);
                        console.log('- showBatchError:', typeof window.showBatchError);

                        // 模态窗口加载完成，resolve Promise
                        resolve();
                    }, 50);

                    // 点击背景关闭模态窗口
                    modalBackdrop.addEventListener('click', (e) => {
                        if (e.target === modalBackdrop) {
                            if (!this.isProcessing || confirm('批量处理正在进行中，确定要关闭吗？')) {
                                this.closeBatchModal();
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('加载批量处理模态窗口失败:', error);
                    this.showError('无法打开批量处理窗口');
                    reject(error);
                });
        });
    }

    /**
     * 关闭批量处理模态窗口
     */
    closeBatchModal() {
        const modalBackdrop = document.querySelector('.modal-backdrop');
        if (modalBackdrop) {
            modalBackdrop.remove();
        }
        window.currentBatchProcessor = null;
    }

    /**
     * 更新批量处理进度
     */
    updateBatchProgress(percentage, message = '') {
        console.log('更新进度:', percentage, message); // 调试信息
        if (typeof window.updateBatchProgress === 'function') {
            window.updateBatchProgress(percentage, message);
        } else {
            console.warn('window.updateBatchProgress 函数不可用');
        }
    }

    /**
     * 显示批量处理结果
     */
    showBatchModalResults(data) {
        if (typeof window.showBatchResults === 'function') {
            window.showBatchResults(data);
        }
    }

    /**
     * 显示批量处理错误
     */
    showBatchModalError(message) {
        if (typeof window.showBatchError === 'function') {
            window.showBatchError(message);
        }
    }
}

// 初始化自动标签器
document.addEventListener('DOMContentLoaded', () => {
    new AutoTagger();
});

// 添加必要的CSS动画
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
