(function () {
    const getById = id => document.getElementById(id);
    const albumSelect = getById('albumSelect');
    const initializeForm = getById('initializeForm');

    const fetchAndLoad = (url, body, callback) => {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(body)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    callback(data.data);
                } else {
                    callback([]);
                }
            })
            .catch(error => console.error('Error fetching:', error));
    };

    // 加载素材集列表
    fetchAndLoad(dhs_ajax_obj.ajax_url + '?action=dhs_get_album_list', { _wpnonce: dhs_ajax_obj.dhs_nonce }, albums => {
        albumSelect.innerHTML = '<option value="" disabled selected>请选择素材集</option><option value="newAlbum">新建素材集</option>';
        if (albums.length > 0) {
            albums.forEach(album => {
                albumSelect.innerHTML += `<option value="${album.id}">${album.album_name}</option>`;
            });
        }
    });

    // 当用户点击“初始化”按钮时，弹出密码输入框
    initializeForm.addEventListener('submit', function (event) {
        event.preventDefault(); // 阻止表单默认提交行为

        const password = prompt('请输入管理员密码：');

        if (password === null || password === '') {
            alert('密码不能为空');
            return;
        }

        const selectedAlbumId = albumSelect.value;

        if (!selectedAlbumId || selectedAlbumId === '') {
            alert('请选择一个素材集');
            return;
        }

        fetch(dhs_ajax_obj.ajax_url + '?action=verify_user_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                user_id: 1,
                password: password,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 密码验证成功后，删除所选相册的条目
                    deleteAlbumEntries(selectedAlbumId);
                } else {
                    // 密码错误或 nonce 验证失败，显示错误信息
                    alert(data.data || '密码错误或验证失败，请重试。');
                }
            })
            .catch(error => console.error('Error:', error));
    });

    function deleteAlbumEntries(albumId) {
        fetch(dhs_ajax_obj.ajax_url + '?action=delete_album_entries', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 调用删除文件夹的操作
                    deleteThumbnailsFolder(albumId);
                } else {
                    alert('删除条目失败，请重试。');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function deleteThumbnailsFolder(albumId) {
        fetch(dhs_ajax_obj.ajax_url + '?action=delete_thumbnails_folder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 删除缩略图文件夹成功后，添加时间戳
                    appendTimestamp(albumId);
                } else {
                    // 处理缩略图文件夹不存在的情况并继续下一步操作
                    console.log('缩略图文件夹不存在或删除失败，继续执行后续操作。');
                    appendTimestamp(albumId);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function appendTimestamp(albumId) {
        fetch(dhs_ajax_obj.ajax_url + '?action=append_timestamp_to_files', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('所有文件已成功添加时间戳。');
                    // 时间戳添加成功后，自动触发文件处理和数据库更新
                    processFilesAndInsert(albumId);
                } else {
                    alert('添加时间戳失败，请重试。');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function processFilesAndInsert(albumId) {
        fetch(dhs_ajax_obj.ajax_url + '?action=process_files_and_insert', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                album_id: albumId,
                _wpnonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('文件处理和数据库更新成功！');
                } else {
                    alert('文件处理失败，请重试。');
                }
            })
            .catch(error => console.error('Error:', error));
    }
})();