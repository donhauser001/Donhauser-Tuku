<h2>编辑收藏夹</h2>
<form id="edit-favorite-form" class="edit-favorite-form">
    <div class="form-group">
        <label for="favorite-name">收藏夹名称:</label>
        <input type="text" id="favorite-name" name="favorite_name" value="" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="favorite-private">
            <input type="checkbox" id="favorite-private" name="favorite_private" class="form-checkbox">
            设为私密
        </label>
    </div>

    <input type="hidden" id="favorite-id" name="favorite_id" value="">
    <button type="submit" class="btn btn-primary">保存修改</button>
</form>

<style>
    /* 表单容器样式 */
    .edit-favorite-form {
        margin: 0 auto;
        padding: 0;
    }

    h2 {
        margin: 0;
        font-size: 24px;
    }



    /* 表单组样式 */
    .form-group {
        margin-bottom: 15px;
    }

    /* 标签样式 */
    .form-group label {
        display: flex;
        align-items: center;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
        margin-top: 20px;
    }

    .form-group input {
        font-size: 14px;
        padding: 0 8px;
    }

    /* 输入框样式 */
    .form-control {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        background-color: #fff;
        transition: border-color 0.3s ease;
    }

    /* 输入框获得焦点时样式 */
    .form-control:focus {
        border-color: #4caf50;
        outline: none;
    }

    /* 复选框样式 */
    .form-checkbox {
        margin-right: 10px;
        transform: scale(1.2);
        cursor: pointer;
        margin-left: 5px;
    }


    /* 主按钮样式 */
    .btn-primary {
        font-size: 14px !important;
        color: #4f5564;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
        border-radius: 5px;
        border: 1px solid #ccc;
        background-color: #fff;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        color: #4f5564;

        background-color: #f6f6f6;
        border: 1px solid #4f5564;
    }

    /* 错误消息样式 */
    .error {
        color: red;
        font-size: 14px;
        margin-top: 10px;
    }
</style>

<script>
    (function() {
        const form = document.getElementById('edit-favorite-form');
        const favoriteNameInput = document.getElementById('favorite-name');
        const favoritePrivateInput = document.getElementById('favorite-private');
        const favoriteIdInput = document.getElementById('favorite-id');

        // 在模态窗口初始化时，设置 favoriteId
        if (window.favoriteId) {
            favoriteIdInput.value = window.favoriteId; // 将全局变量的值传递给模态窗口的输入框

            // 添加调试信息
            console.log('Favorite ID:', window.favoriteId);

            if (!window.favoriteId) {
                console.error('Favorite ID is not set.');
                return;
            }

            // 获取收藏夹详情
            fetch(dhs_ajax_obj.ajax_url, { // 使用 dhs_ajax_obj.ajax_url 来代替直接写 admin_url
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'get_favorite_details',
                        favorite_id: window.favoriteId,
                        security: dhs_ajax_obj.dhs_nonce // 确保包含 nonce
                    })
                })
                .then(response => {
                    console.log('AJAX response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('AJAX response data:', data);
                    if (data.success) {
                        // 填充表单数据
                        favoriteNameInput.value = data.data.name;

                        // 添加调试信息，查看is_public字段的值
                        console.log('is_public value:', data.data.is_public);

                        // 如果 is_public 为 0，则表示收藏夹是私密的，所以需要勾选复选框
                        favoritePrivateInput.checked = (data.data.is_public == 0);

                        // 也可以改用这种更直观的写法
                        // favoritePrivateInput.checked = !data.data.is_public;
                    } else {
                        alert('加载收藏夹详情失败: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error during AJAX request:', error);
                });
        } else {
            console.error('window.favoriteId is not set.');
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const data = new FormData(form);
            data.append('action', 'edit_favorite');
            data.append('security', dhs_ajax_obj.dhs_nonce); // 确保包含 nonce

            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('收藏夹已更新');
                        location.reload();
                    } else {
                        alert('更新失败: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    })();
</script>