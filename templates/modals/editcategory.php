<h2>编辑分类</h2>
<form id="edit-category-form" class="edit-category-form">
    <div class="form-group">
        <label for="category-name">分类名称:</label>
        <input type="text" id="category-name" name="category_name" value="" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="parent-category">父分类:</label>
        <select id="parent-category" name="parent_category" class="form-control">
            <option value="0">无父分类</option>
            <!-- 动态加载父分类选项 -->
        </select>
    </div>

    <input type="hidden" id="category-id" name="category_id" value="">

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">保存修改</button>
        <button type="button" class="btn btn-danger" id="delete-category">删除分类</button>
    </div>
</form>

<style>
    /* 表单容器样式 */
    .edit-category-form {
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

    .form-group input,
    .form-group select {
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

    .btn-danger {
        font-size: 14px !important;
        color: #fff;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
        border-radius: 5px;
        border: 1px solid #f44336;
        background-color: #f44336;
        transition: background-color 0.3s ease;
    }

    .btn-danger:hover {
        background-color: #d32f2f;
        border-color: #d32f2f;
    }

    /* 错误消息样式 */
    .error {
        color: red;
        font-size: 14px;
        margin-top: 10px;
    }

    /* 表单操作区样式 */
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
</style>

<script>
    (function() {
        const form = document.getElementById('edit-category-form');
        const categoryNameInput = document.getElementById('category-name');
        const parentCategorySelect = document.getElementById('parent-category');
        const categoryIdInput = document.getElementById('category-id');
        const deleteButton = document.getElementById('delete-category');

        // 在模态窗口初始化时，设置 categoryId
        if (window.categoryId) {
            categoryIdInput.value = window.categoryId; // 将全局变量的值传递给模态窗口的输入框

            // 添加调试信息
            console.log('Category ID:', window.categoryId);

            if (!window.categoryId) {
                console.error('Category ID is not set.');
                return;
            }

            // 获取分类详情
            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'get_category_details',
                        category_id: window.categoryId,
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
                        categoryNameInput.value = data.data.name;

                        // 动态生成父分类选项
                        parentCategorySelect.innerHTML = '<option value="0">无父分类</option>';
                        data.data.available_categories.forEach(function(category) {
                            const selected = category.id == data.data.parent_id ? 'selected' : '';
                            parentCategorySelect.innerHTML += `<option value="${category.id}" ${selected}>${category.name}</option>`;
                        });
                    } else {
                        alert('加载分类详情失败: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error during AJAX request:', error);
                });
        } else {
            console.error('window.categoryId is not set.');
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const data = new FormData(form);
            data.append('action', 'edit_category');
            data.append('security', dhs_ajax_obj.dhs_nonce); // 确保包含 nonce

            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('分类已更新');
                        location.reload();
                    } else {
                        alert('更新失败: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });

        // 删除分类功能
        deleteButton.addEventListener('click', function() {
            if (confirm('确定要删除这个分类吗？删除后无法恢复！')) {
                fetch(dhs_ajax_obj.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'delete_category',
                            category_id: window.categoryId,
                            security: dhs_ajax_obj.dhs_nonce // 确保包含 nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('分类已删除');
                            location.reload();
                        } else {
                            alert('删除失败: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error during AJAX request:', error);
                    });
            }
        });
    })();
</script>