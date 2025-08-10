function initLikes() {
    const likeIcons = document.querySelectorAll('.like-icon');

    // 移除现有的事件处理程序
    likeIcons.forEach(function (icon) {
        const clone = icon.cloneNode(true);
        icon.parentNode.replaceChild(clone, icon);
    });

    // 重新查询并绑定事件处理程序
    const newLikeIcons = document.querySelectorAll('.like-icon');

    newLikeIcons.forEach(function (icon) {
        const imageItem = icon.closest('.dhs-album-image-item');
        const imageId = imageItem.getAttribute('data-image-id');

        // 检查当前用户是否已经喜欢了这张图片
        checkIfLiked(imageId, icon);

        icon.addEventListener('click', function (event) {
            event.preventDefault();
            const isLiked = !icon.classList.contains('liked'); // 如果已经喜欢，则取消喜欢

            updateLikeStatus(imageId, isLiked, icon);
        });
    });

    function checkIfLiked(imageId, icon) {
        fetch(dhs_ajax_obj.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'check_like_status',
                image_id: imageId,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.is_liked) {
                    icon.classList.add('liked'); // 应用红色样式
                }
            })
            .catch(error => {
                console.error('检查喜欢状态时出错:', error);
            });
    }


    function updateLikeStatus(imageId, isLiked, icon) {
        fetch(dhs_ajax_obj.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'update_like_status',
                image_id: imageId,
                is_liked: isLiked ? 1 : 0,
                _ajax_nonce: dhs_ajax_obj.dhs_nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 根据返回的结果更新前端显示
                    if (isLiked) {
                        icon.classList.add('liked');
                    } else {
                        icon.classList.remove('liked');
                    }
                } else {
                    alert('操作失败：' + (data.message || '未知错误'));
                }
            })
            .catch(error => {
                alert('更新失败，请稍后重试');
                console.error('更新喜欢状态时出错:', error);
            });
    }
}