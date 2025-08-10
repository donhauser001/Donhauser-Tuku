/**
 * DHS图库相册JavaScript功能
 */

(function ($) {
    'use strict';

    // 相册项处理类
    class AlbumItemHandler {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.processAlbumItems();
        }

        bindEvents() {
            // 删除处理已移至 album-menu.js
            // $(document).on('click', '.delete-option', this.handleDelete.bind(this));
        }

        processAlbumItems() {
            const albumItems = document.querySelectorAll('.dhs-album-item');

            albumItems.forEach((item) => {
                const img = item.querySelector('img');

                if (img) {
                    if (img.complete) {
                        this.setAlbumColors(item, img);
                    } else {
                        img.addEventListener('load', () => {
                            this.setAlbumColors(item, img);
                        });
                    }
                }
            });
        }

        setAlbumColors(item, img) {
            try {
                const color = this.getDominantColor(img);
                const hasTransparency = this.checkTransparency(img);

                if (hasTransparency) {
                    // 透明背景使用浅灰色
                    item.style.backgroundColor = '#f0f0f0';
                    this.setTextColor(item, '#333');
                } else {
                    // 使用图片主色调
                    item.style.backgroundColor = color;
                    this.adjustTextColor(item, color);
                }
            } catch (error) {
                console.warn('Failed to process album colors:', error);
                // 降级到默认样式
                item.style.backgroundColor = '#f0f0f0';
                this.setTextColor(item, '#333');
            }
        }

        getDominantColor(image) {
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            // 设置画布大小
            canvas.width = Math.min(image.width, 100);
            canvas.height = Math.min(image.height, 100);

            context.drawImage(image, 0, 0, canvas.width, canvas.height);

            const data = context.getImageData(0, 0, canvas.width, canvas.height).data;
            let r = 0, g = 0, b = 0, count = 0;

            // 采样像素点（每隔40个像素采样一次）
            for (let i = 0; i < data.length; i += 40) {
                r += data[i];
                g += data[i + 1];
                b += data[i + 2];
                count++;
            }

            r = Math.floor(r / count);
            g = Math.floor(g / count);
            b = Math.floor(b / count);

            return `rgb(${r}, ${g}, ${b})`;
        }

        checkTransparency(image) {
            try {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');

                canvas.width = Math.min(image.width, 50);
                canvas.height = Math.min(image.height, 50);

                context.drawImage(image, 0, 0, canvas.width, canvas.height);

                const data = context.getImageData(0, 0, canvas.width, canvas.height).data;

                // 检查Alpha通道
                for (let i = 3; i < data.length; i += 4) {
                    if (data[i] < 255) {
                        return true; // 发现透明像素
                    }
                }

                return false;
            } catch (error) {
                console.warn('Failed to check transparency:', error);
                return false;
            }
        }

        adjustTextColor(item, color) {
            const brightness = this.calculateBrightness(color);
            const textColor = brightness > 200 ? '#333' : '#fff';
            this.setTextColor(item, textColor);
        }

        setTextColor(item, color) {
            const h3 = item.querySelector('h3');
            const metaElement = item.querySelector('.dhs-album-meta');

            if (h3) {
                h3.style.color = color;
            }

            if (metaElement) {
                metaElement.style.color = color;
                const pElements = metaElement.querySelectorAll('p');
                pElements.forEach(p => {
                    p.style.color = color;
                    p.style.marginBottom = '5px';
                });
            }
        }

        calculateBrightness(rgbColor) {
            const rgb = rgbColor.match(/\d+/g);
            if (!rgb || rgb.length < 3) return 128; // 默认中等亮度

            const r = parseInt(rgb[0]);
            const g = parseInt(rgb[1]);
            const b = parseInt(rgb[2]);

            // 使用相对亮度公式
            return Math.sqrt(0.299 * (r * r) + 0.587 * (g * g) + 0.114 * (b * b));
        }

        handleDelete(event) {
            event.preventDefault();

            const deleteButton = event.currentTarget;
            const albumId = deleteButton.getAttribute('data-album-id');
            const albumItem = deleteButton.closest('.dhs-album-item');

            if (!albumId) {
                this.showError('无效的相册ID');
                return;
            }

            if (!confirm(dhs_ajax_obj.messages.confirm_delete || '确定要删除这个相册吗？')) {
                return;
            }

            // 显示加载状态
            deleteButton.textContent = '删除中...';
            deleteButton.disabled = true;

            $.ajax({
                url: dhs_ajax_obj.ajax_url,
                method: 'POST',
                data: {
                    action: 'delete_album',
                    album_id: albumId,
                    _ajax_nonce: dhs_ajax_obj.dhs_nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('相册已成功删除');

                        // 添加删除动画
                        albumItem.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        albumItem.style.opacity = '0';
                        albumItem.style.transform = 'scale(0.8)';

                        setTimeout(() => {
                            albumItem.remove();
                        }, 300);
                    } else {
                        this.showError('删除相册时发生错误: ' + (response.data?.message || '未知错误'));
                        deleteButton.textContent = '删除';
                        deleteButton.disabled = false;
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Delete album error:', error);
                    this.showError(dhs_ajax_obj.messages.network_error || '删除相册时发生网络错误');
                    deleteButton.textContent = '删除';
                    deleteButton.disabled = false;
                }
            });
        }

        showSuccess(message) {
            this.showNotification(message, 'success');
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        showNotification(message, type = 'info') {
            // 移除现有通知
            $('.dhs-notification').remove();

            const className = `dhs-notification dhs-notification-${type}`;
            const backgroundColor = type === 'error' ? '#dc3545' :
                type === 'success' ? '#28a745' : '#007cba';

            const notification = $(`
                <div class="${className}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${backgroundColor};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 5px;
                    z-index: 9999;
                    max-width: 300px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    animation: slideInRight 0.3s ease;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>${message}</span>
                        <button class="close-notification" style="
                            background: none;
                            border: none;
                            color: white;
                            font-size: 18px;
                            cursor: pointer;
                            margin-left: 10px;
                            padding: 0;
                            line-height: 1;
                        ">×</button>
                    </div>
                </div>
            `);

            $('body').append(notification);

            // 绑定关闭事件
            notification.find('.close-notification').on('click', () => {
                notification.remove();
            });

            // 自动关闭
            setTimeout(() => {
                notification.fadeOut(300, () => notification.remove());
            }, 5000);
        }
    }

    // 初始化
    $(document).ready(() => {
        new AlbumItemHandler();
    });

    // 添加CSS动画
    if (!document.getElementById('dhs-albums-styles')) {
        const styles = `
            <style id="dhs-albums-styles">
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                .dhs-album-item {
                    transition: all 0.3s ease;
                }
                
                .dhs-album-options {
                    transition: all 0.2s ease;
                }
                
                .dhs-album-options:hover {
                    transform: scale(1.1);
                }
            </style>
        `;
        $('head').append(styles);
    }

})(jQuery);
