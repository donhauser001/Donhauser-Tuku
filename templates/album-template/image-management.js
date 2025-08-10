function initImageManagement(albumId) {

    const deleteButton = document.getElementById('delete-images');
    const generateThumbnailsButton = document.getElementById('generate-thumbnails');
    const selectAllButton = document.getElementById('select-all');
    const deselectAllButton = document.getElementById('deselect-all');
    const checkboxes = document.querySelectorAll('.image-checkbox');
    const selectionActions = document.querySelector('.selection-actions');
    const deleteCheckButton = document.getElementById('delete-check');

    // 调试信息
    console.log('Image management elements found:', {
        deleteButton: !!deleteButton,
        generateThumbnailsButton: !!generateThumbnailsButton,
        selectAllButton: !!selectAllButton,
        deselectAllButton: !!deselectAllButton,
        deleteCheckButton: !!deleteCheckButton,
        checkboxes: checkboxes.length,
        selectionActions: !!selectionActions
    });

    // 只检查最关键的元素
    if (!generateThumbnailsButton) {
        console.warn('Generate thumbnails button not found');
        return;
    }



    // 删除按钮点击事件 - 切换复选框显示
    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            console.log('Delete button clicked');

            const isVisible = checkboxes.length > 0 && checkboxes[0].classList.contains('show');

            if (isVisible) {
                // 隐藏复选框和操作按钮
                checkboxes.forEach(checkbox => {
                    checkbox.classList.remove('show');
                    setTimeout(() => checkbox.style.display = 'none', 400);
                });
                if (selectionActions) {
                    selectionActions.classList.remove('show');
                    setTimeout(() => selectionActions.style.display = 'none', 400);
                }
            } else {
                // 显示复选框和操作按钮
                checkboxes.forEach(checkbox => {
                    checkbox.style.display = 'block';
                    setTimeout(() => checkbox.classList.add('show'), 10);
                });
                if (selectionActions) {
                    selectionActions.style.display = 'block';
                    setTimeout(() => selectionActions.classList.add('show'), 10);
                }
            }
        });
    }

    // 处理缩略图生成逻辑 - 打开新的模态窗口
    generateThumbnailsButton.addEventListener('click', function () {
        var albumId = generateThumbnailsButton.getAttribute('data-album-id');

        if (!albumId) {
            alert('未找到相册 ID');
            return;
        }

        // 获取相册信息
        const albumNameElement = document.querySelector('.header-container h2');
        const albumName = albumNameElement ? albumNameElement.textContent.trim() : '未知相册';
        const imageCount = document.querySelectorAll('.dhs-album-image-item').length;

        // 打开新的生成预览图模态窗口
        openGenerateThumbnailsModal(albumId, albumName, imageCount);
    });

    // 生成预览图模态窗口函数
    function openGenerateThumbnailsModal(albumId, albumName, imageCount) {
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

        // 加载新的模态窗口内容
        fetch(`${window.location.origin}/wp-content/plugins/dhs-tuku/templates/modals/modal-handler.php?modal=generate-thumbnails`)
            .then(response => response.text())
            .then(html => {
                modal.innerHTML = html;
                modalBackdrop.appendChild(modal);
                document.body.appendChild(modalBackdrop);

                // 手动执行脚本标签
                const scripts = modal.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    newScript.textContent = script.textContent;
                    document.head.appendChild(newScript);
                    document.head.removeChild(newScript);
                });

                // 设置相册信息
                setTimeout(() => {
                    if (typeof window.setAlbumInfo === 'function') {
                        window.setAlbumInfo(albumId, albumName, imageCount);
                    }
                }, 100);

                // 点击背景关闭模态窗口
                modalBackdrop.addEventListener('click', (e) => {
                    if (e.target === modalBackdrop) {
                        if (typeof window.isGeneratingThumbnails !== 'undefined' && window.isGeneratingThumbnails &&
                            !confirm('预览图生成正在进行中，确定要关闭吗？')) {
                            return;
                        }
                        modalBackdrop.remove();
                    }
                });
            })
            .catch(error => {
                console.error('加载预览图生成模态窗口失败:', error);
                alert('无法打开预览图生成窗口');
            });
    }

    // 选择框的显示/隐藏逻辑
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (anyChecked) {
                checkboxes.forEach(cb => {
                    if (!cb.checked) {
                        cb.style.display = 'inline';
                    }
                });
                selectionActions.style.display = 'block';
                setTimeout(() => selectionActions.classList.add('show'), 10);
            } else {
                checkboxes.forEach(cb => {
                    setTimeout(() => cb.style.display = 'none', 200);
                });
                selectionActions.classList.remove('show');
                setTimeout(() => selectionActions.style.display = 'none', 400);
            }
        });
    });

    if (selectAllButton) {
        selectAllButton.addEventListener('click', function () {
            checkboxes.forEach(checkbox => checkbox.checked = true);
            // 触发change事件以更新选择状态
            if (checkboxes.length > 0) {
                checkboxes[0].dispatchEvent(new Event('change'));
            }
        });
    }

    if (deselectAllButton) {
        deselectAllButton.addEventListener('click', function () {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.classList.remove('show');
                setTimeout(() => checkbox.style.display = 'none', 400);
            });
            if (selectionActions) {
                selectionActions.classList.remove('show');
                setTimeout(() => selectionActions.style.display = 'none', 400);
            }
        });
    }

    if (deleteCheckButton) {
        deleteCheckButton.addEventListener('click', function () {
            const selectedImages = [];
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedImages.push(checkbox.closest('.dhs-album-image-item').getAttribute('data-image-name'));
                }
            });

            if (selectedImages.length > 0) {
                if (confirm(`确定要删除选中的 ${selectedImages.length} 张图片吗？`)) {
                    const albumId = deleteCheckButton.getAttribute('data-album-id');

                    fetch(dhs_ajax_obj.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'delete_images',
                            album_id: albumId,
                            images: selectedImages.join(','),
                            _ajax_nonce: dhs_ajax_obj.dhs_nonce
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // 删除成功，刷新页面或重新加载图片
                                selectedImages.forEach(imageName => {
                                    const imageItem = document.querySelector(`[data-image-name="${imageName}"]`);
                                    if (imageItem) {
                                        imageItem.remove();
                                    }
                                });

                                // 重新计算 Masonry 布局
                                if (typeof $ !== 'undefined' && $('.dhs-album-images-grid').masonry) {
                                    $('.dhs-album-images-grid').masonry('reloadItems').masonry();
                                }

                                // 隐藏选择操作区域
                                if (selectionActions) {
                                    selectionActions.classList.remove('show');
                                    setTimeout(() => selectionActions.style.display = 'none', 400);
                                }
                            } else {
                                alert('删除失败: ' + (data.data ? data.data.message : '未知错误'));
                            }
                        })
                        .catch(error => {
                            console.error('删除图片时出错:', error);
                            alert('删除操作失败，请稍后重试');
                        });
                }
            } else {
                alert('请先选择要删除的图片');
            }
        });
    }
}