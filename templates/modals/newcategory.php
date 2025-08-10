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

<h2>新建分类</h2>
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
        <button type="submit" class="btn btn-primary">保存分类</button>
    </div>
</form>

<script>
    (function() {
        const form = document.getElementById('edit-category-form');
        const categoryNameInput = document.getElementById('category-name');
        const parentCategorySelect = document.getElementById('parent-category');
        const categoryIdInput = document.getElementById('category-id');

        // 获取父分类选项
        fetch(dhs_ajax_obj.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'dhs_get_category_list',
                    _wpnonce: dhs_ajax_obj.dhs_nonce // 确保包含 nonce
                })
            })
            .then(response => {
                console.log('AJAX response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('AJAX response data:', data);
                if (data.success) {
                    // 动态生成父分类选项
                    parentCategorySelect.innerHTML = '<option value="0">无父分类</option>';
                    data.data.forEach(function(category) {
                        parentCategorySelect.innerHTML += `<option value="${category.id}">${category.category_name}</option>`;
                    });
                } else {
                    alert('加载父分类选项失败: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error during AJAX request:', error);
            });

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const data = new FormData(form);
            const categoryId = categoryIdInput.value.trim();

            if (categoryId === '' || categoryId === '0') {
                data.append('action', 'dhs_create_category'); // 使用创建分类的 AJAX 钩子
            } else {
                data.append('action', 'edit_category'); // 如果已有 ID，仍然可以用编辑的钩子
            }

            data.append('_wpnonce', dhs_ajax_obj.dhs_nonce); // 确保包含 nonce

            fetch(dhs_ajax_obj.ajax_url, {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('分类已保存');
                        location.reload();
                    } else {
                        alert('保存分类失败: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });

    })();
</script>