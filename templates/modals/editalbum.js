function initModal() {
    if (!window.albumId) {
        console.error('全局相册ID未定义');
        return;
    }

    console.log('获取到的相册ID:', window.albumId);

    // 先加载分类和父类别，完成后再获取相册详情
    loadAlbumCategories().then(() => {
        console.log('分类加载完成，开始获取相册详情...');
        fetchAlbumDetails(window.albumId);
    }).catch(err => {
        console.error('分类加载失败:', err);
    });

    // 添加事件监听，检测分类选择的变化
    document.getElementById('categorySelect').addEventListener('change', function () {
        const newCategoryContainer = document.getElementById('newCategoryContainer');

        if (this.value === 'new') {
            newCategoryContainer.style.display = 'block';  // 显示新建分类部分
        } else {
            newCategoryContainer.style.display = 'none';   // 隐藏新建分类部分
        }
    });

    // 添加封面上传预览
    document.getElementById('albumCover').addEventListener('change', function (event) {
        const file = event.target.files[0];
        const preview = document.getElementById('coverPreview');

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });

    document.getElementById('uploadFilesBtn').addEventListener('click', function (e) {
        e.preventDefault();

        const form = document.getElementById('editalbumForm');
        const formData = new FormData(form);

        formData.append('album_id', window.albumId);  // 假设相册ID是全局变量
        formData.append('_ajax_nonce', dhs_ajax_obj.dhs_nonce);  // 添加nonce
        formData.append('action', 'update_album_settings');  // 指定要调用的后端函数

        const albumCover = document.getElementById('albumCover').files[0];
        if (albumCover) {
            formData.append('album_cover', albumCover);
        }

        // 如果选择了新建分类，附加新分类名称和父类别
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect.value === 'new') {
            const newCategoryName = document.getElementById('newCategoryName').value.trim();
            const parentCategory = document.getElementById('parentCategorySelect').value;

            if (newCategoryName === '') {
                alert('请填写新分类名称');
                return;
            }

            formData.append('new_category_name', newCategoryName);
            formData.append('parent_category', parentCategory);
        }

        fetch(dhs_ajax_obj.ajax_url, {
            method: 'POST',
            body: formData,
        })
            .then(response => {
                console.log('响应状态:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('服务器响应:', data);
                if (data.success) {
                    alert('设置更新成功');
                } else {
                    console.error('更新失败:', data.message);
                    alert('更新失败: ' + data.message);
                }
            })
            .catch(error => {
                console.error('更新过程中发生错误:', error);
            });
    });
}

async function fetchAlbumDetails(albumId) {
    try {
        const response = await fetch(dhs_ajax_obj.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'get_album_details',
                album_id: albumId,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            })
        });

        const data = await response.json();
        console.log("服务器响应数据: ", data);

        if (data.success && data.data.album) {
            const album = data.data.album;
            console.log('获取到的相册详情:', album);

            document.getElementById('AlbumName').value = album.album_name || '';
            document.getElementById('newAlbumDescription').value = album.description || '';

            const categorySelect = document.getElementById('categorySelect');
            const parentCategorySelect = document.getElementById('parentCategorySelect');

            if (categorySelect && album.category_id) {
                let categoryFound = false;
                for (let i = 0; i < categorySelect.options.length; i++) {
                    if (String(categorySelect.options[i].value) === String(album.category_id)) {
                        categorySelect.options[i].selected = true;
                        categoryFound = true;
                        break;
                    }
                }
                if (!categoryFound) {
                    console.error('相册分类未找到: ', album.category_id);
                }
            }

            if (parentCategorySelect && album.parent_category) {
                let parentCategoryFound = false;
                for (let i = 0; i < parentCategorySelect.options.length; i++) {
                    if (String(parentCategorySelect.options[i].value) === String(album.parent_category)) {
                        parentCategorySelect.options[i].selected = true;
                        parentCategoryFound = true;
                        break;
                    }
                }
                if (!parentCategoryFound) {
                    console.error('父类别未找到: ', album.parent_category);
                }
            }
            if (album.cover_image) {
                const coverPreview = document.getElementById('coverPreview');

                // 使用 wp_localize_script 传递的 site_url 来补齐封面图片地址
                const fullCoverImageUrl = dhs_ajax_obj.site_url + '/wp-content/uploads' + album.cover_image;

                coverPreview.src = fullCoverImageUrl;  // 设置封面图片的src
                coverPreview.style.display = 'block';  // 显示图片预览
            }
            console.log('相册详情加载完成');
        } else {
            console.error('相册信息获取失败，响应:', data);
            alert('获取相册信息时发生错误: ' + (data.message || '未知错误'));
        }
    } catch (error) {
        console.error('获取相册信息时发生网络错误或服务器未响应:', error);
    }
}

const loadAlbumCategories = () => {
    return new Promise((resolve, reject) => {
        fetch(dhs_ajax_obj.ajax_url + '?action=dhs_get_category_list', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const categories = data.data;
                    console.log("加载的分类: ", categories);

                    const categorySelect = document.getElementById('categorySelect');
                    const parentCategorySelect = document.getElementById('parentCategorySelect');

                    categorySelect.innerHTML = '<option value="">选择分类</option><option value="new">新建分类</option>';
                    parentCategorySelect.innerHTML = '<option value="none">无</option>';

                    categories.forEach(category => {
                        categorySelect.innerHTML += `<option value="${category.id}">${category.category_name}</option>`;
                        parentCategorySelect.innerHTML += `<option value="${category.id}">${category.category_name}</option>`;
                    });

                    resolve();
                } else {
                    console.error('Failed to load categories:', data.data);
                    reject('Failed to load categories');
                }
            })
            .catch(error => {
                console.error('Error fetching categories:', error);
                reject(error);
            });
    });
};