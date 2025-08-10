function loadMoreImages() {
    const albumImagesGrid = document.getElementById('albumImagesGrid');
    let isLoading = false; // 防止多次触发加载

    jQuery(document).ready(function ($) {
        var $grid = $('#albumImagesGrid');

        $grid.imagesLoaded(function () {
            $grid.masonry({
                itemSelector: '.dhs-album-image-item',
                percentPosition: true,
                columnWidth: '.dhs-album-image-item',
                gutter: 15
            });
        });

        function loadImages() {
            if (isLoading) return;
            isLoading = true;

            var offset = $grid.data('offset') || 0;
            var albumId = $grid.data('album-id');

            console.log("Requesting more images with offset:", offset, "albumId:", albumId);
            console.log("Ajax URL:", dhs_ajax_obj.ajax_url);

            if (!albumId) {
                console.error('Album ID is missing or undefined');
                isLoading = false;
                return;
            }

            $.post(dhs_ajax_obj.ajax_url, {
                action: 'load_more_images',
                album_id: albumId,
                offset: offset
            }).done(function (response) {
                if (response.success) {
                    var $items = $(response.data.html);
                    $grid.append($items).masonry('appended', $items);
                    $grid.data('offset', offset + $items.length);

                    initFavorites();
                    initLikes();

                    $grid.imagesLoaded(function () {
                        $grid.masonry('layout');
                    });

                    if (!response.data.has_more) {
                        $(window).off('scroll', checkScroll);
                    }
                } else {
                    console.error('服务器返回错误:', response.data ? response.data.message : '未知错误');
                }
            }).fail(function (xhr, status, error) {
                console.error('AJAX Error - Status:', status, 'Error:', error);
                console.error('XHR Response:', xhr.responseText);
                if (xhr.status === 0) {
                    console.error('网络连接问题或请求被取消');
                } else if (xhr.status === 404) {
                    console.error('请求的页面未找到 (404)');
                } else if (xhr.status === 500) {
                    console.error('内部服务器错误 (500)');
                } else {
                    console.error('其他错误:', xhr.status, xhr.statusText);
                }
            }).always(function () {
                isLoading = false;
            });
        }

        function checkScroll() {
            var scrollTop = $(window).scrollTop();
            var windowHeight = $(window).height();
            var documentHeight = $(document).height();

            if (scrollTop + windowHeight >= documentHeight - 100) {
                loadImages();
            }
        }

        $(window).on('scroll', checkScroll);

        $grid.data('offset', 20); // 初始化偏移量
    });
}