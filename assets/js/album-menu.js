/**
 * DHS图库相册菜单交互脚本
 * 处理点击显示/隐藏操作菜单
 */

(function ($) {
    'use strict';

    class AlbumMenuHandler {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // 点击操作图标显示/隐藏菜单
            $(document).on('click', '.dhs-album-options', this.toggleMenu.bind(this));

            // 点击菜单项后隐藏菜单
            $(document).on('click', '.dhs-album-options .options-menu a', this.handleMenuItemClick.bind(this));

            // 点击其他区域关闭所有菜单
            $(document).on('click', this.closeAllMenus.bind(this));

            // 阻止菜单容器的点击事件冒泡
            $(document).on('click', '.dhs-album-options .options-menu', function (e) {
                e.stopPropagation();
            });
        }

        toggleMenu(e) {
            e.preventDefault();
            e.stopPropagation();

            const $target = $(e.currentTarget);
            const $menu = $target.find('.options-menu');
            const $icon = $target.find('i');

            // 关闭其他所有菜单
            this.closeOtherMenus($target);

            // 切换当前菜单的显示状态
            if ($menu.hasClass('show')) {
                this.closeMenu($target);
            } else {
                this.openMenu($target);
            }
        }

        openMenu($optionsContainer) {
            const $menu = $optionsContainer.find('.options-menu');
            const $icon = $optionsContainer.find('i');

            $menu.addClass('show');
            $optionsContainer.addClass('active');

            // 改变图标状态
            $icon.removeClass('fa-ellipsis-h').addClass('fa-times');

            // 添加动画效果
            $menu.css({
                opacity: 0,
                transform: 'translateY(-5px)'
            }).animate({
                opacity: 1
            }, 200).css('transform', 'translateY(0)');
        }

        closeMenu($optionsContainer) {
            const $menu = $optionsContainer.find('.options-menu');
            const $icon = $optionsContainer.find('i');

            $menu.removeClass('show');
            $optionsContainer.removeClass('active');

            // 恢复图标状态
            $icon.removeClass('fa-times').addClass('fa-ellipsis-h');
        }

        closeOtherMenus($currentContainer) {
            $('.dhs-album-options').not($currentContainer).each((index, element) => {
                this.closeMenu($(element));
            });
        }

        closeAllMenus(e) {
            // 如果点击的不是操作按钮或菜单，关闭所有菜单
            if (!$(e.target).closest('.dhs-album-options').length) {
                $('.dhs-album-options').each((index, element) => {
                    this.closeMenu($(element));
                });
            }
        }

        handleMenuItemClick(e) {
            const $menuItem = $(e.currentTarget);
            const $optionsContainer = $menuItem.closest('.dhs-album-options');

            // 如果是删除操作，需要确认
            if ($menuItem.hasClass('delete-option')) {
                e.preventDefault();
                this.handleDeleteClick($menuItem, $optionsContainer);
                return;
            }

            // 对于其他操作（如编辑），等待一小段时间再关闭菜单
            // 这样用户可以看到点击反馈
            setTimeout(() => {
                this.closeMenu($optionsContainer);
            }, 150);
        }

        handleDeleteClick($deleteButton, $optionsContainer) {
            const albumId = $deleteButton.data('album-id');
            const $albumItem = $optionsContainer.closest('.dhs-album-item');

            // 显示确认对话框
            if (confirm(dhs_ajax_obj.messages.confirm_delete || '确定要删除这个相册吗？')) {
                // 关闭菜单
                this.closeMenu($optionsContainer);

                // 显示加载状态
                $optionsContainer.html('<i class="fa fa-spinner fa-spin"></i>');

                // 执行删除操作
                this.executeDelete(albumId, $albumItem);
            } else {
                // 用户取消删除，关闭菜单
                this.closeMenu($optionsContainer);
            }
        }

        executeDelete(albumId, $albumItem) {
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
                        this.showMessage('相册已成功删除', 'success');

                        // 添加删除动画
                        $albumItem.css({
                            transition: 'all 0.3s ease',
                            opacity: 0,
                            transform: 'scale(0.8)'
                        });

                        setTimeout(() => {
                            $albumItem.remove();
                        }, 300);
                    } else {
                        this.showMessage('删除相册时发生错误: ' + (response.data?.message || '未知错误'), 'error');
                        // 恢复操作按钮
                        this.restoreOptionsButton($albumItem, albumId);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Delete album error:', error);
                    this.showMessage(dhs_ajax_obj.messages.network_error || '删除相册时发生网络错误', 'error');
                    // 恢复操作按钮
                    this.restoreOptionsButton($albumItem, albumId);
                }
            });
        }

        restoreOptionsButton($albumItem, albumId) {
            const $optionsContainer = $albumItem.find('.dhs-album-options');
            $optionsContainer.html(`
                <i class="fa fa-ellipsis-h"></i>
                <div class="options-menu">
                    <a href="javascript:void(0)" class="edit-option dhs-tuku-open-modal" data-modal="editalbum:40" data-album-id="${albumId}">编辑</a>
                    <a href="javascript:void(0)" class="delete-option" data-album-id="${albumId}">删除</a>
                </div>
            `);
        }

        showMessage(message, type = 'info') {
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

    // 页面加载完成后初始化
    $(document).ready(() => {
        new AlbumMenuHandler();
    });

    // 添加CSS动画
    if (!document.getElementById('dhs-album-menu-styles')) {
        const styles = `
            <style id="dhs-album-menu-styles">
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                .dhs-album-options.active {
                    background-color: rgba(0, 0, 0, 0.7) !important;
                    transform: scale(1.1);
                }
                
                .dhs-album-options .options-menu {
                    transition: all 0.2s ease;
                }
            </style>
        `;
        $('head').append(styles);
    }

})(jQuery);

