<?php
/**
 * DHS图库资源管理类
 * 处理CSS、JS文件的加载
 */

class DHS_Tuku_Assets
{
    public static function init()
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_filter('admin_body_class', [__CLASS__, 'add_admin_body_class']);
        add_action('wp_footer', [__CLASS__, 'add_modal_logic']);
    }

    /**
     * 加载前端资源
     */
    public static function enqueue_frontend_assets()
    {
        // Font Awesome
        wp_enqueue_style(
            'dhs-tuku-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            [],
            '6.0.0'
        );

        // 主样式文件
        wp_enqueue_style(
            'dhs-tuku-style',
            DHS_TUKU_ASSETS_URL . 'css/style.css',
            [],
            DHS_TUKU_VERSION
        );

        // 模态窗口样式
        wp_enqueue_style(
            'dhs-tuku-modal',
            DHS_TUKU_ASSETS_URL . 'css/modal-styles.css',
            ['dhs-tuku-style'],
            DHS_TUKU_VERSION
        );

        // 自动标签样式
        wp_enqueue_style(
            'dhs-tuku-auto-tagger',
            DHS_TUKU_ASSETS_URL . 'css/auto-tagger.css',
            ['dhs-tuku-style'],
            DHS_TUKU_VERSION
        );

        // URL修复脚本（优先加载）
        wp_enqueue_script(
            'dhs-tuku-url-fix',
            DHS_TUKU_ASSETS_URL . 'js/url-fix.js',
            ['jquery'],
            DHS_TUKU_VERSION,
            true
        );

        // 主脚本文件
        wp_enqueue_script(
            'dhs-tuku-script',
            DHS_TUKU_ASSETS_URL . 'js/script.js',
            ['jquery', 'dhs-tuku-url-fix'],
            DHS_TUKU_VERSION,
            true
        );

        // Masonry布局
        wp_enqueue_script(
            'dhs-tuku-masonry',
            'https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js',
            ['jquery'],
            '4.2.2',
            true
        );

        // 图片加载检测
        wp_enqueue_script(
            'dhs-tuku-imagesloaded',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/4.1.4/imagesloaded.pkgd.min.js',
            ['jquery'],
            '4.1.4',
            true
        );

        // 相册菜单交互脚本
        wp_enqueue_script(
            'dhs-tuku-album-menu',
            DHS_TUKU_ASSETS_URL . 'js/album-menu.js',
            ['jquery', 'dhs-tuku-script'],
            DHS_TUKU_VERSION,
            true
        );

        // 自动标签脚本
        wp_enqueue_script(
            'dhs-tuku-auto-tagger',
            DHS_TUKU_ASSETS_URL . 'js/auto-tagger.js',
            ['jquery', 'dhs-tuku-script'],
            DHS_TUKU_VERSION,
            true
        );

        // 模板相关脚本
        $template_scripts = [
            'main' => 'templates/album-template/main.js',
            'load-more' => 'templates/album-template/load-more.js',
            'favorites' => 'templates/album-template/favorites.js',
            'likes' => 'templates/album-template/likes.js',
            'image-management' => 'templates/album-template/image-management.js'
        ];

        foreach ($template_scripts as $handle => $path) {
            wp_enqueue_script(
                "dhs-tuku-{$handle}",
                DHS_TUKU_PLUGIN_URL . $path,
                ['jquery', 'dhs-tuku-script'],
                DHS_TUKU_VERSION,
                true
            );
        }

        // 本地化脚本
        wp_localize_script('dhs-tuku-script', 'dhs_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'dhs_nonce' => wp_create_nonce('dhs_nonce'),
            'plugin_url' => DHS_TUKU_PLUGIN_URL,
            'site_url' => site_url(),
            'user_id' => get_current_user_id(),
            'current_user_name' => wp_get_current_user()->display_name,
            'messages' => [
                'confirm_delete' => __('确定要删除吗？', 'dhs-tuku'),
                'upload_error' => __('上传失败', 'dhs-tuku'),
                'network_error' => __('网络错误', 'dhs-tuku'),
            ]
        ]);

        // Masonry初始化脚本
        wp_add_inline_script('dhs-tuku-imagesloaded', self::get_masonry_script());
    }

    /**
     * 加载后台资源
     */
    public static function enqueue_admin_assets($hook)
    {
        // 只在插件页面加载后台资源
        if (strpos($hook, 'dhs-tuku') === false) {
            return;
        }

        // 确保 dashicons 被加载 - 使用更高优先级
        wp_enqueue_style('dashicons');
        
        // 添加内联CSS确保图标字体被正确加载
        wp_add_inline_style('dashicons', '
            .dhs-tuku-admin .dashicons,
            .dhs-tuku-admin i.dashicons {
                font-family: dashicons !important;
                font-style: normal !important;
                font-weight: normal !important;
                display: inline-block !important;
            }
        ');

        wp_enqueue_style(
            'dhs-tuku-admin-style',
            DHS_TUKU_ASSETS_URL . 'css/admin.css',
            ['dashicons'],
            DHS_TUKU_VERSION
        );

        wp_enqueue_script(
            'dhs-tuku-admin-script',
            DHS_TUKU_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            DHS_TUKU_VERSION,
            true
        );
    }

    /**
     * 为插件后台页面添加标识类，便于样式作用域控制
     */
    public static function add_admin_body_class($classes)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_plugin_page = false;
        if (isset($_GET['page'])) {
            $page = sanitize_text_field($_GET['page']);
            if (in_array($page, ['dhs-tuku', 'dhs-tuku-settings', 'dhs-tuku-tag-manager'], true)) {
                $is_plugin_page = true;
            }
        }
        if ($screen && strpos((string)$screen->id, 'dhs-tuku') !== false) {
            $is_plugin_page = true;
        }
        if ($is_plugin_page) {
            $classes .= ' dhs-tuku-admin';
        }
        return $classes;
    }

    /**
     * 添加模态窗口逻辑
     */
    public static function add_modal_logic()
    {
        ?>
        <script>
        (function($){
            $(document).ready(function() {
                $(".dhs-tuku-custom-modal").remove();
            });

            // 全局变量
            window.albumId = null;
            window.imageId = null;
            window.favoriteId = null;
            window.categoryId = null;

            $(document).on("click", ".dhs-tuku-open-modal", function(e) {
                e.preventDefault();
                var modalData = $(this).data("modal");
                var modalParts = modalData.split(":");
                var modalName = modalParts[0];
                var modalWidth = modalParts[1] || "50";
                var modalHeight = modalParts[2] || "auto";

                // 获取相关属性
                window.albumId = $(this).data("album-id");
                window.imageId = $(this).data("image-id");
                window.favoriteId = $(this).data("favorite-id"); 
                window.categoryId = $(this).data("category-id");

                // AJAX加载模态内容
                $.ajax({
                    url: "<?php echo DHS_TUKU_PLUGIN_URL; ?>templates/modals/modal-handler.php",
                    method: "GET",
                    data: { modal: modalName },
                    success: function(response) {
                        $(".dhs-tuku-custom-modal").remove();
                        var modalContainer = $("<div class='dhs-tuku-custom-modal'>" +
                            "<div class='dhs-tuku-custom-modal-content' style='width:" + modalWidth + "%; height:" + modalHeight + "%;'>" +
                            "<span class='dhs-tuku-close-modal'>×</span>" +
                            response +
                            "</div></div>");
                        $("body").append(modalContainer);
                        modalContainer.show();

                        // 加载对应的JS文件
                        var scriptUrl = "<?php echo DHS_TUKU_PLUGIN_URL; ?>templates/modals/" + modalName + ".js";
                        $.getScript(scriptUrl).done(function() {
                            if (typeof initModal === "function") {
                                initModal();
                            }
                        });

                        // 事件监听
                        modalContainer.on("click", function(e) {
                            if ($(e.target).is(".dhs-tuku-custom-modal")) {
                                modalContainer.remove();
                            }
                        });

                        modalContainer.find(".dhs-tuku-close-modal").on("click", function() {
                            modalContainer.remove();
                        });
                    },
                    error: function() {
                        alert('<?php echo esc_js(__("加载模态窗口失败", "dhs-tuku")); ?>');
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * 获取Masonry初始化脚本
     */
    private static function get_masonry_script()
    {
        return "
        jQuery(document).ready(function($) {
            var \$grid = $('#albumImagesGrid, #likedImagesGrid, #searchResultsGrid');
            if (\$grid.length) {
                \$grid.imagesLoaded(function() {
                    \$grid.masonry({
                        itemSelector: '.dhs-album-image-item',
                        percentPosition: true,
                        columnWidth: '.dhs-album-image-item',
                        gutter: 15
                    });
                });
            }
        });
        ";
    }
}
