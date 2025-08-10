(function () {
    const getById = id => document.getElementById(id);
    const dropZone = getById('dropZone');
    const fileInput = getById('fileInput');
    const fileList = getById('fileList');
    const uploadFilesBtn = getById('uploadFilesBtn');
    const clearListBtn = getById('clearListBtn');
    const albumSelect = getById('albumSelect');
    const newAlbumContainer = getById('newAlbumContainer');
    const categorySelect = getById('categorySelect');
    const newCategoryContainer = getById('newCategoryContainer');
    const customOptions = getById('categorySelect').parentElement.querySelector('.custom-options');
    const associateSameNameBtn = getById('associateSameNameBtn');
    const parentCategorySelect = getById('parentCategorySelect');
    let filesArray = [];
    let fileGroupsByName = {};
    let groupedFilesArray = null;
    // 初始化时隐藏按钮
    clearListBtn.style.display = 'none';
    associateSameNameBtn.style.display = 'none';

    const toggleDisplay = (element, display) => element.style.display = display;
    const formatFileSize = bytes => bytes >= 1073741824 ? (bytes / 1073741824).toFixed(2) + ' GB' : bytes >= 1048576 ? (bytes / 1048576).toFixed(2) + ' MB' : bytes >= 1024 ? (bytes / 1024).toFixed(2) + ' KB' : bytes + ' B';

    categorySelect.addEventListener('change', () => {
        if (categorySelect.value === 'new') {
            toggleDisplay(newCategoryContainer, 'block');
        } else {
            toggleDisplay(newCategoryContainer, 'none');
        }
    });

    const fetchAndLoad = (url, body, callback) => {
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(body) })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    callback(data.data);  // 加载素材集数据
                } else {
                    // 如果数据为空或加载失败，确保仍然加载默认选项
                    callback([]); // 传递空数组
                }
            })
            .catch(error => console.error('Error fetching:', error));
    };

    // 检测当前页面是否是相册详情页
    const getCurrentAlbumId = () => {
        // 检查URL参数
        const urlParams = new URLSearchParams(window.location.search);
        const albumIdFromUrl = urlParams.get('album_id');

        // 检查页面是否包含相册详情元素
        const albumDetailsElement = document.querySelector('.dhs-album-details');
        const deleteButton = document.querySelector('#delete-check[data-album-id]');

        if (albumIdFromUrl) {
            return albumIdFromUrl;
        } else if (deleteButton) {
            return deleteButton.getAttribute('data-album-id');
        } else if (albumDetailsElement) {
            // 如果是相册详情页但没有找到album_id，尝试从其他元素获取
            const thumbnailButton = document.querySelector('#generate-thumbnails[data-album-id]');
            if (thumbnailButton) {
                return thumbnailButton.getAttribute('data-album-id');
            }
        }
        return null;
    };

    // 加载素材集列表的回调
    fetchAndLoad(dhs_ajax_obj.ajax_url + '?action=dhs_get_album_list', { _wpnonce: dhs_ajax_obj.dhs_nonce }, albums => {
        const currentAlbumId = getCurrentAlbumId();

        // 根据是否在相册详情页选择不同的默认选项
        if (currentAlbumId) {
            // 在相册详情页，先添加默认选项但不选中
            albumSelect.innerHTML = '<option value="" disabled>请选择素材集</option><option value="newAlbum">新建素材集</option>';
        } else {
            // 非相册详情页，显示"请选择素材集"为默认选中
            albumSelect.innerHTML = '<option value="" disabled selected>请选择素材集</option><option value="newAlbum">新建素材集</option>';
        }

        // 如果有素材集数据，填充素材集选项
        if (albums.length > 0) {
            albums.forEach(album => {
                const isSelected = currentAlbumId && album.id == currentAlbumId ? ' selected' : '';
                albumSelect.innerHTML += `<option value="${album.id}"${isSelected}>${album.album_name}</option>`;
            });
        }
    });


    albumSelect.addEventListener('change', () => {
        if (albumSelect.value === 'newAlbum') {
            newAlbumContainer.style.display = 'block';
            loadAlbumCategories();
        } else {
            newAlbumContainer.style.display = 'none';
            newCategoryContainer.style.display = 'none';
        }
    });


    const loadAlbumCategories = () => {
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
                    categorySelect.innerHTML = '<option value="">选择分类</option><option value="new">新建分类</option>';
                    parentCategorySelect.innerHTML = '<option value="none">无</option>';
                    categories.forEach(category => {
                        categorySelect.innerHTML += `<option value="${category.id}">${category.category_name}</option>`;
                        parentCategorySelect.innerHTML += `<option value="${category.id}">${category.category_name}</option>`;
                    });
                } else {
                    console.error('Failed to load categories:', data.data);
                }
            })
            .catch(error => {
                console.error('Error fetching categories:', error);
            });
    };

    albumSelect.addEventListener('change', () => {
        if (albumSelect.value === 'newAlbum') {
            newAlbumContainer.style.display = 'block';
            loadAlbumCategories();
        } else {
            newAlbumContainer.style.display = 'none';
            newCategoryContainer.style.display = 'none';
        }
    });





    // 新建分类表单提交处理
    newCategoryContainer.addEventListener('submit', function (e) {
        e.preventDefault();

        const categoryName = getById('newCategoryName').value;
        const parentId = getById('parentCategorySelect').value;
        const nonce = dhs_ajax_obj.dhs_nonce;

        if (!categoryName) {
            alert('分类名称不能为空');
            return;
        }

        fetch(dhs_ajax_obj.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'dhs_create_category',
                category_name: categoryName,
                parent_id: parentId,
                _wpnonce: nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('分类创建成功');
                    // 更新分类选项
                    const newOption = document.createElement('label');
                    newOption.innerHTML = `<input type="checkbox" value="${data.data.category_id}"> ${categoryName}`;
                    customOptions.appendChild(newOption);
                    toggleDisplay(newCategoryContainer, 'none');
                    getById('newCategoryName').value = '';
                    getById('parentCategorySelect').value = 'none';
                } else {
                    alert('分类创建失败: ' + data.data);
                }
            })
            .catch(error => {
                alert('AJAX 请求失败: ' + error);
            });
    });

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('active'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('active'));
    dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('active'); handleFiles(e.dataTransfer.files); });
    fileInput.addEventListener('change', () => handleFiles(fileInput.files));

    const handleFiles = files => {
        Array.from(files).forEach(file => {
            filesArray.push(file);
            const listItem = document.createElement('div');
            listItem.classList.add('file-list-item');
            const fileInfo = document.createElement('div');
            fileInfo.classList.add('file-info');
            const fileContainer = document.createElement('div');
            fileContainer.classList.add('file-container');
            const fileText = document.createElement('span');
            fileText.innerHTML = `${file.name} - ${formatFileSize(file.size)} - ${file.type || 'N/A'}`;
            const deleteBtn = document.createElement('button');
            deleteBtn.classList.add('delete-btn');
            deleteBtn.textContent = '删除';
            deleteBtn.style.marginLeft = '10px';
            deleteBtn.addEventListener('click', () => {
                fileList.removeChild(listItem);
                filesArray = filesArray.filter(f => f !== file);
                // 检查文件数组长度并隐藏按钮
                if (filesArray.length === 0) {
                    clearListBtn.style.display = 'none';
                    associateSameNameBtn.style.display = 'none';
                }
            });
            fileContainer.appendChild(fileText);
            fileContainer.appendChild(deleteBtn);
            fileInfo.appendChild(fileContainer);
            const progressBarupContainer = document.createElement('div');
            progressBarupContainer.classList.add('progress-bar-up-container');
            const progressBarup = document.createElement('div');
            progressBarup.classList.add('progress-bar-up');
            progressBarupContainer.appendChild(progressBarup);
            listItem.appendChild(fileInfo);
            listItem.appendChild(progressBarupContainer);
            fileList.appendChild(listItem);
            file.progressBarup = progressBarup;
        });
        // 检查文件数组长度并显示按钮
        if (filesArray.length > 0) {
            clearListBtn.style.display = 'block';
            associateSameNameBtn.style.display = 'block';
        }
    };

    clearListBtn.addEventListener('click', () => {
        fileList.innerHTML = '';
        filesArray = [];
        fileGroupsByName = {};
        // 清空列表后隐藏按钮
        clearListBtn.style.display = 'none';
        associateSameNameBtn.style.display = 'none';
    });

    associateSameNameBtn.addEventListener('click', () => {
        groupFilesByName();
        groupedFilesArray = Object.values(fileGroupsByName).flat();
        updateFileListDisplay();
    });

    const groupFilesByName = () => {
        filesArray.forEach(file => {
            const fileNameWithoutExtension = file.name.split('.').slice(0, -1).join('.');
            if (!fileGroupsByName[fileNameWithoutExtension]) fileGroupsByName[fileNameWithoutExtension] = [];
            fileGroupsByName[fileNameWithoutExtension].push(file);
        });
        filesArray = Object.values(fileGroupsByName).flat();
    };

    const updateFileListDisplay = () => {
        fileList.innerHTML = '';
        Object.keys(fileGroupsByName).forEach(group => fileList.appendChild(createFileListItem(group)));
    };

    const createFileListItem = group => {
        const listItem = document.createElement('div');
        listItem.classList.add('file-list-item');
        const fileInfo = document.createElement('div');
        fileInfo.classList.add('file-info');
        fileGroupsByName[group].forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.classList.add('file-item');
            fileItem.innerHTML = `<p>${file.name} - ${formatFileSize(file.size)} - ${file.type || 'N/A'}</p>`;
            fileInfo.appendChild(fileItem);
        });
        const deleteBtn = document.createElement('button');
        deleteBtn.classList.add('delete-btn');
        deleteBtn.textContent = '删除';
        deleteBtn.addEventListener('click', () => {
            fileList.removeChild(listItem);
            fileGroupsByName[group].forEach(file => filesArray = filesArray.filter(f => f !== file));
            if (filesArray.length === 0) {
                clearListBtn.style.display = 'none';
                associateSameNameBtn.style.display = 'none';
            }
        });
        const progressBarupContainer = document.createElement('div');
        progressBarupContainer.classList.add('progress-bar-up-container');
        const progressBarup = document.createElement('div');
        progressBarup.classList.add('progress-bar-up');
        progressBarupContainer.appendChild(progressBarup);
        listItem.appendChild(fileInfo);
        listItem.appendChild(deleteBtn);
        listItem.appendChild(progressBarupContainer);
        return listItem;
    };

    uploadFilesBtn.addEventListener('click', async () => {
        const filesToUpload = groupedFilesArray || filesArray;
        if (!filesToUpload.length) return alert('请选择要上传的文件。');

        // 获取素材集选择的值
        const selectedAlbum = albumSelect.value;
        if (!selectedAlbum) return alert('请选择一个素材集。');

        // 检查是否选择了新建素材集
        let albumId;
        let albumName;
        let categoryId;
        if (selectedAlbum === 'newAlbum') {
            // 获取新建素材集名称
            albumName = getById('newAlbumName').value.trim();
            if (!albumName) return alert('请填写素材集名称。');

            // 检查分类选择
            categoryId = categorySelect.value;
            if (!categoryId) return alert('请选择一个素材集分类。');

            // 如果选择了新建分类，获取分类名称和父类别
            if (categoryId === 'new') {
                const newCategoryName = getById('newCategoryName').value.trim();
                const parentCategoryId = parentCategorySelect.value;
                if (!newCategoryName) return alert('请填写新建分类名称。');

                // 推送新建分类信息到后台，获取新分类ID
                console.log("推送的新建分类信息:", newCategoryName, parentCategoryId);
                const newCategoryData = await createNewCategory(newCategoryName, parentCategoryId);
                console.log("新分类创建的响应数据:", newCategoryData);

                if (newCategoryData && newCategoryData.success) {
                    categoryId = newCategoryData.data.category_id; // 使用新建分类的 ID
                    console.log("新建分类成功，ID:", categoryId);
                } else {
                    return alert('新建分类失败，请重试。');
                }
            }
        } else {
            albumId = selectedAlbum; // 已选素材集的 ID
        }

        // 如果选择了新建素材集，需要先将素材集名称和分类推送到后台，创建素材集并获取素材集 ID
        if (albumName) {
            console.log("准备创建新素材集，名称:", albumName, "分类ID:", categoryId);
            const newAlbumData = await createNewAlbum(albumName, categoryId); // 将新建的 category_id 传入
            console.log("服务器返回的素材集创建响应数据:", newAlbumData);

            // 确保素材集创建成功并且有返回素材集 ID
            if (newAlbumData && newAlbumData.success && newAlbumData.data && newAlbumData.data.album_id) {
                albumId = newAlbumData.data.album_id; // 使用新建素材集的 ID
                console.log("新建素材集成功，ID:", albumId);
            } else {
                console.error('素材集创建成功，但没有返回 album_id');
                return alert('素材集创建成功，但无法获取素材集ID，请重试。');
            }
        }

        // 上传文件
        await uploadFiles(albumId);
    });

    const createNewAlbum = async (albumName, categoryId) => {
        console.log("准备创建素材集，名称:", albumName, "分类ID:", categoryId);

        try {
            const requestBody = new URLSearchParams({
                action: 'dhs_create_album',
                album: albumName,
                category_id: categoryId,  // 使用分类 ID
                _wpnonce: dhs_ajax_obj.dhs_nonce
            });

            console.log("请求体内容:", requestBody.toString());

            const response = await fetch(dhs_ajax_obj.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: requestBody
            });

            console.log("服务器返回响应:", response);

            let result;
            try {
                result = await response.json(); // 尝试解析 JSON
            } catch (jsonError) {
                console.error("解析响应 JSON 时出错:", jsonError);
                console.error("原始响应内容:", await response.text());
                alert("解析响应时出错，请检查服务器返回的内容。");
                return null;
            }

            console.log("服务器返回的 JSON 数据:", result);

            // 检查 result.success 是否存在并确保数据结构正确
            if (result && result.success) {
                console.log('素材集创建成功:', result);
                if (result.data && result.data.album_id) {
                    return result;  // 返回新创建的素材集数据
                } else {
                    console.error('素材集创建成功但没有返回 album_id');
                    alert('创建素材集失败: 没有返回素材集ID');
                    return null;
                }
            } else {
                console.error('创建素材集失败，返回的错误数据:', result);
                alert('创建素材集失败: ' + (result.data ? result.data : "未知错误"));
                return null;
            }
        } catch (error) {
            console.error('AJAX 请求出错:', error);
            alert('上传过程中发生错误，请重试。错误信息: ' + error.message);
            return null;
        }
    };


    const createNewCategory = async (categoryName, parentCategoryId) => {
        try {
            const response = await fetch(dhs_ajax_obj.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'dhs_create_category',
                    category_name: categoryName,
                    parent_id: parentCategoryId,
                    _wpnonce: dhs_ajax_obj.dhs_nonce
                })
            });
            const result = await response.json();

            if (result.success) {
                console.log('分类创建成功:', result);
                return result;  // 返回整个响应结果，而不是 result.data
            } else {
                console.error('创建分类失败:', result);
                alert('创建分类失败: ' + (result.data ? result.data : "未知错误"));
                return null;
            }
        } catch (error) {
            console.error('AJAX 请求出错:', error);
            alert('创建分类时发生错误，请重试。');
            return null;
        }
    };


    const uploadFiles = async (albumId) => {
        const uploadFileGroup = async (groupName, files) => {
            const formData = new FormData();
            files.forEach(file => formData.append('files[]', file));
            formData.append('album', albumId);
            formData.append('group_name', groupName);
            formData.append('_wpnonce', dhs_ajax_obj.dhs_nonce);

            try {
                const response = await fetch(dhs_ajax_obj.ajax_url + '?action=dhs_submit_file', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    console.log('文件上传成功:', result.data);
                } else {
                    console.error('文件上传失败:', result.data);
                }
            } catch (error) {
                console.error('上传过程中发生错误:', error);
            }
        };

        if (groupedFilesArray) {
            const groupedFiles = Object.entries(fileGroupsByName);
            for (const [groupName, files] of groupedFiles) {
                await uploadFileGroup(groupName, files);
            }
        } else {
            for (const file of filesArray) {
                const fileNameWithoutExtension = file.name.split('.').slice(0, -1).join('.');
                await uploadFileGroup(fileNameWithoutExtension, [file]);
            }
        }
        // 清空模态窗口数据
        clearModalData();

        // 关闭模态窗并刷新页面
        closeUploadModalAndRefresh();


    };
    // 清空模态窗口数据的函数
    const clearModalData = () => {
        // 清空文件列表
        fileList.innerHTML = '';
        filesArray = [];
        groupedFilesArray = null;

        // 清空其他模态窗口的输入字段
        getById('newAlbumName').value = '';  // 清空素材集名称
        categorySelect.value = '';           // 清空分类选择
        getById('newCategoryName').value = '';// 清空新分类名称

        // 这里可以清空其他你可能用到的输入字段
    };

    // 关闭上传模态窗并刷新页面的函数
    const closeUploadModalAndRefresh = () => {
        // 查找并关闭模态窗
        const modalBackdrop = document.querySelector('.modal-backdrop');
        if (modalBackdrop) {
            modalBackdrop.remove();
        }

        // 刷新页面以显示新上传的图片
        window.location.reload();
    };

})();