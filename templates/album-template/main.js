document.addEventListener('DOMContentLoaded', function () {
    // 初始化加载更多图片的功能
    loadMoreImages();

    // 初始化收藏夹相关功能
    initFavorites();

    // 初始化点赞功能
    initLikes();

    // 获取并初始化相册管理功能
    var albumId = document.getElementById('generate-thumbnails')?.dataset.albumId;
    initImageManagement(albumId);

});