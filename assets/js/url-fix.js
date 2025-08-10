/**
 * DHS图库URL修复脚本
 * 自动将HTTP图片URL转换为HTTPS
 */

(function ($) {
    'use strict';

    // URL修复函数
    function fixImageUrls() {
        // 检查当前页面是否使用HTTPS
        if (window.location.protocol === 'https:') {
            // 查找所有图片元素
            $('img').each(function () {
                var $img = $(this);
                var src = $img.attr('src');

                if (src && src.startsWith('http://')) {
                    // 将HTTP替换为HTTPS
                    var newSrc = src.replace('http://', 'https://');
                    $img.attr('src', newSrc);
                }
            });

            // 修复背景图片
            $('[style*="background-image"]').each(function () {
                var $element = $(this);
                var style = $element.attr('style');

                if (style && style.includes('http://')) {
                    var newStyle = style.replace(/http:\/\//g, 'https://');
                    $element.attr('style', newStyle);
                }
            });

            // 修复动态加载的内容
            $(document).on('DOMNodeInserted', function (e) {
                var $target = $(e.target);

                if ($target.is('img')) {
                    var src = $target.attr('src');
                    if (src && src.startsWith('http://')) {
                        $target.attr('src', src.replace('http://', 'https://'));
                    }
                }
            });
        }
    }

    // 页面加载完成后执行
    $(document).ready(function () {
        fixImageUrls();
    });

    // 图片加载错误时的处理
    $(document).on('error', 'img', function () {
        var $img = $(this);
        var src = $img.attr('src');

        // 如果HTTPS图片加载失败，尝试HTTP版本
        if (src && src.startsWith('https://')) {
            var httpSrc = src.replace('https://', 'http://');

            // 避免无限循环
            if (!$img.data('fallback-tried')) {
                $img.data('fallback-tried', true);
                $img.attr('src', httpSrc);
            }
        }
    });

    // 为AJAX加载提供全局URL修复函数
    window.dhsTukuFixUrls = fixImageUrls;

})(jQuery);
