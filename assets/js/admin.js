/**
 * DHS图库插件后台管理脚本
 * 版本: 1.1.0
 */

(function ($) {
    'use strict';

    // 全局变量
    var DHS_Tuku_Admin = {
        init: function () {
            this.bindEvents();
            this.initializeComponents();
        },

        // 绑定事件
        bindEvents: function () {
            // 删除确认
            $(document).on('click', '.dhs-tuku-delete-btn', this.confirmDelete);

            // 批量操作
            $(document).on('change', '.dhs-tuku-bulk-select', this.handleBulkSelect);
            $(document).on('click', '.dhs-tuku-bulk-action', this.handleBulkAction);

            // 搜索功能
            $(document).on('input', '.dhs-tuku-search', this.handleSearch);

            // 排序功能
            $(document).on('click', '.dhs-tuku-sort', this.handleSort);

            // 分页
            $(document).on('click', '.dhs-tuku-pagination a', this.handlePagination);

            // 模态窗口
            $(document).on('click', '.dhs-tuku-modal-trigger', this.openModal);
            $(document).on('click', '.dhs-tuku-modal-close', this.closeModal);

            // 表单提交
            $(document).on('submit', '.dhs-tuku-form', this.handleFormSubmit);

            // 图片上传
            $(document).on('change', '.dhs-tuku-image-upload', this.handleImageUpload);

            // 标签管理
            $(document).on('click', '.dhs-tuku-tag', this.handleTagClick);
            $(document).on('click', '.dhs-tuku-add-tag', this.addNewTag);
        },

        // 初始化组件
        initializeComponents: function () {
            // 初始化日期选择器
            if ($.fn.datepicker) {
                $('.dhs-tuku-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }

            // 初始化颜色选择器
            if ($.fn.wpColorPicker) {
                $('.dhs-tuku-color-picker').wpColorPicker();
            }

            // 初始化媒体上传器
            if (typeof wp !== 'undefined' && wp.media) {
                this.initMediaUploader();
            }

            // 初始化拖拽排序
            this.initSortable();

            // 初始化工具提示
            this.initTooltips();
        },

        // 删除确认
        confirmDelete: function (e) {
            e.preventDefault();
            var message = $(this).data('confirm') || '确定要删除这个项目吗？';

            if (confirm(message)) {
                var url = $(this).attr('href');
                if (url) {
                    window.location.href = url;
                } else {
                    $(this).closest('form').submit();
                }
            }
        },

        // 处理批量选择
        handleBulkSelect: function () {
            var checked = $('.dhs-tuku-bulk-select:checked').length;
            var total = $('.dhs-tuku-bulk-select').length;

            if (checked === total) {
                $('.dhs-tuku-bulk-select-all').prop('checked', true);
            } else {
                $('.dhs-tuku-bulk-select-all').prop('checked', false);
            }

            // 更新批量操作按钮状态
            if (checked > 0) {
                $('.dhs-tuku-bulk-actions').removeClass('disabled');
            } else {
                $('.dhs-tuku-bulk-actions').addClass('disabled');
            }
        },

        // 处理批量操作
        handleBulkAction: function (e) {
            e.preventDefault();
            var action = $(this).data('action');
            var selected = $('.dhs-tuku-bulk-select:checked');

            if (selected.length === 0) {
                alert('请先选择要操作的项目');
                return;
            }

            if (confirm('确定要执行这个操作吗？')) {
                var ids = [];
                selected.each(function () {
                    ids.push($(this).val());
                });

                // 发送AJAX请求
                $.ajax({
                    url: dhs_tuku_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dhs_tuku_bulk_action',
                        bulk_action: action,
                        ids: ids,
                        nonce: dhs_tuku_admin.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('操作失败：' + response.data);
                        }
                    },
                    error: function () {
                        alert('网络错误，请重试');
                    }
                });
            }
        },

        // 处理搜索
        handleSearch: function () {
            var query = $(this).val();
            var table = $(this).closest('.dhs-tuku-admin-container').find('table');

            if (query.length > 2) {
                table.find('tbody tr').each(function () {
                    var text = $(this).text().toLowerCase();
                    if (text.indexOf(query.toLowerCase()) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                table.find('tbody tr').show();
            }
        },

        // 处理排序
        handleSort: function (e) {
            e.preventDefault();
            var column = $(this).data('column');
            var order = $(this).data('order') || 'asc';

            // 切换排序方向
            var newOrder = order === 'asc' ? 'desc' : 'asc';
            $(this).data('order', newOrder);

            // 更新排序图标
            $(this).find('.sort-icon').text(newOrder === 'asc' ? '↑' : '↓');

            // 发送排序请求
            $.ajax({
                url: dhs_tuku_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dhs_tuku_sort',
                    column: column,
                    order: newOrder,
                    nonce: dhs_tuku_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        },

        // 处理分页
        handlePagination: function (e) {
            e.preventDefault();
            var page = $(this).data('page');

            $.ajax({
                url: dhs_tuku_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dhs_tuku_pagination',
                    page: page,
                    nonce: dhs_tuku_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('.dhs-tuku-content').html(response.data);
                    }
                }
            });
        },

        // 打开模态窗口
        openModal: function (e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            var modal = $('#' + modalId);

            if (modal.length) {
                modal.show();
                $('body').addClass('modal-open');
            }
        },

        // 关闭模态窗口
        closeModal: function (e) {
            e.preventDefault();
            var modal = $(this).closest('.dhs-tuku-modal');
            modal.hide();
            $('body').removeClass('modal-open');
        },

        // 处理表单提交
        handleFormSubmit: function (e) {
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();

            // 显示加载状态
            submitBtn.prop('disabled', true).text('处理中...');

            // 添加加载动画
            submitBtn.append('<span class="dhs-tuku-loading"></span>');

            // 表单验证
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                submitBtn.prop('disabled', false).text(originalText);
                submitBtn.find('.dhs-tuku-loading').remove();
                return;
            }

            // 发送AJAX请求
            $.ajax({
                url: form.attr('action') || dhs_tuku_admin.ajax_url,
                type: form.attr('method') || 'POST',
                data: form.serialize(),
                success: function (response) {
                    if (response.success) {
                        DHS_Tuku_Admin.showNotice('操作成功！', 'success');
                        if (response.data.redirect) {
                            setTimeout(function () {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        DHS_Tuku_Admin.showNotice('操作失败：' + response.data, 'error');
                    }
                },
                error: function () {
                    DHS_Tuku_Admin.showNotice('网络错误，请重试', 'error');
                },
                complete: function () {
                    submitBtn.prop('disabled', false).text(originalText);
                    submitBtn.find('.dhs-tuku-loading').remove();
                }
            });
        },

        // 处理图片上传
        handleImageUpload: function (e) {
            var input = $(this);
            var preview = input.siblings('.dhs-tuku-image-preview');
            var file = e.target.files[0];

            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.html('<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px;">');
                };
                reader.readAsDataURL(file);
            }
        },

        // 处理标签点击
        handleTagClick: function (e) {
            e.preventDefault();
            var tag = $(this).text();
            var searchInput = $('.dhs-tuku-search');

            searchInput.val(tag);
            searchInput.trigger('input');
        },

        // 添加新标签
        addNewTag: function (e) {
            e.preventDefault();
            var tagInput = $('.dhs-tuku-new-tag');
            var tag = tagInput.val().trim();

            if (tag) {
                $.ajax({
                    url: dhs_tuku_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dhs_tuku_add_tag',
                        tag: tag,
                        nonce: dhs_tuku_admin.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            tagInput.val('');
                            DHS_Tuku_Admin.showNotice('标签添加成功！', 'success');
                            // 刷新标签列表
                            location.reload();
                        } else {
                            DHS_Tuku_Admin.showNotice('标签添加失败：' + response.data, 'error');
                        }
                    }
                });
            }
        },

        // 初始化媒体上传器
        initMediaUploader: function () {
            $('.dhs-tuku-media-upload').on('click', function (e) {
                e.preventDefault();
                var button = $(this);
                var preview = button.siblings('.dhs-tuku-image-preview');
                var input = button.siblings('input[type="hidden"]');

                var frame = wp.media({
                    title: '选择图片',
                    button: {
                        text: '使用此图片'
                    },
                    multiple: false
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    input.val(attachment.id);
                    preview.html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 200px;">');
                });

                frame.open();
            });
        },

        // 初始化拖拽排序
        initSortable: function () {
            if ($.fn.sortable) {
                $('.dhs-tuku-sortable').sortable({
                    handle: '.dhs-tuku-sort-handle',
                    update: function (event, ui) {
                        var order = $(this).sortable('toArray', { attribute: 'data-id' });

                        $.ajax({
                            url: dhs_tuku_admin.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'dhs_tuku_update_order',
                                order: order,
                                nonce: dhs_tuku_admin.nonce
                            }
                        });
                    }
                });
            }
        },

        // 初始化工具提示
        initTooltips: function () {
            $('[data-tooltip]').each(function () {
                var tooltip = $('<div class="dhs-tuku-tooltiptext">' + $(this).data('tooltip') + '</div>');
                $(this).append(tooltip);
            });
        },

        // 显示通知
        showNotice: function (message, type) {
            var notice = $('<div class="dhs-tuku-notice dhs-tuku-notice-' + type + '">' + message + '</div>');
            $('.dhs-tuku-admin-container').prepend(notice);

            // 自动隐藏
            setTimeout(function () {
                notice.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        },

        // 显示加载状态
        showLoading: function (container) {
            var loading = $('<div class="dhs-tuku-loading-overlay"><div class="dhs-tuku-loading"></div></div>');
            container.append(loading);
        },

        // 隐藏加载状态
        hideLoading: function (container) {
            container.find('.dhs-tuku-loading-overlay').remove();
        }
    };

    // 页面加载完成后初始化
    $(document).ready(function () {
        DHS_Tuku_Admin.init();
    });

    // 导出到全局作用域
    window.DHS_Tuku_Admin = DHS_Tuku_Admin;

    // 为兼容性添加全局 showNotice 函数
    window.showNotice = function (message, type) {
        DHS_Tuku_Admin.showNotice(message, type);
    };

})(jQuery);
