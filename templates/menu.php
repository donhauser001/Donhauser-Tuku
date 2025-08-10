<div class="dhs-tuku-menu">
    <nav class="dhs-tuku-nav">
        <ul>
            <li><a href="<?php echo esc_url(site_url('/tuku')); ?>"><i class="fas fa-images"></i> 图库</a></li>
            <li><a href="#categories" id="categories-toggle"><i class="fas fa-th-large"></i> 分类查看</a></li>
            <?php

            // 只有登录用户才显示个人相关菜单（我的图库、收藏、喜欢）
            if (is_user_logged_in()) {
                // 添加我的图库菜单
                $query = new WP_Query([
                    'name' => 'my_albums', // 页面别名为 'my_albums'
                    'post_type' => 'any', // 查找所有类型的文章/页面
                    'post_status' => ['publish', 'private', 'draft'], // 包括所有状态
                ]);

                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $link = get_permalink();
                        echo '<li><a href="' . esc_url($link) . '"><i class="fas fa-folder-open"></i> 我的图库</a></li>';
                    }
                } else {
                    error_log('Page with slug "my_albums" not found.');
                }

                // 继续显示收藏和喜欢菜单
                $query = new WP_Query([
                    'name' => 'favorites',
                    'post_type' => 'any', // 查找所有类型的文章/页面
                    'post_status' => ['publish', 'private', 'draft'], // 包括所有状态
                ]);

                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $link = get_permalink();
                        echo '<li><a href="' . esc_url($link) . '"><i class="fas fa-star"></i> 我收藏的</a></li>';
                    }
                } else {
                    error_log('Page with slug "favorites" not found.');
                }

                $query = new WP_Query([
                    'name' => 'liked_images', // 页面别名为 'liked_images'
                    'post_type' => 'any', // 查找所有类型的文章/页面
                    'post_status' => ['publish', 'private', 'draft'], // 包括所有状态
                ]);

                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $link = get_permalink();
                        echo '<li><a href="' . esc_url($link) . '"><i class="fas fa-heart"></i> 我喜欢的</a></li>';
                    }
                } else {
                    error_log('Page with slug "liked_images" not found.');
                }
            }


            $query = new WP_Query([
                'name' => 'tuku_search', // 页面别名为 'tuku_search'
                'post_type' => 'any', // 查找所有类型的文章/页面
                'post_status' => ['publish', 'private', 'draft'], // 包括所有状态
            ]);

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $search_page_link = get_permalink(); // 获取搜索页面的链接
            ?>
                    <li class="search-menu-item">
                        <a href="#search" id="search-toggle"><i class="fas fa-search"></i> 搜索</a>
                        <div class="dhs-search-container" id="search-container">
                            <form method="get" action="<?php echo esc_url($search_page_link); ?>">
                                <input type="text" id="search-input" name="searchtuku" placeholder="搜索..." />
                                <button type="submit" id="search-button">搜</button>
                            </form>
                        </div>
                    </li>
            <?php
                }
            } else {
                error_log('Page with slug "tuku_search" not found.');
            }
            wp_reset_postdata(); // 重置查询
            ?>
        </ul>
        <?php if (is_user_logged_in()) : ?>
            <!-- DEBUG: 用户已登录，显示按钮 -->
            <div class="dhs-tuku-buttons">
                <!-- DEBUG: 标签管理按钮应该在这里 -->
                <a href="javascript:void(0)" class="btn-upload-material dhs-tuku-open-modal" data-modal="upload:40">
                    <i class="fas fa-upload"></i> 上传素材
                </a>
            </div>
        <?php else : ?>
            <!-- DEBUG: 用户未登录，不显示按钮 -->
        <?php endif; ?>
    </nav>


    <?php
    global $wpdb;
    $table_name_category = $wpdb->prefix . 'dhs_gallery_categories';

    // 获取所有顶级分类（parent_id 为 NULL）
    $categories = $wpdb->get_results("SELECT id, category_name FROM {$table_name_category} WHERE parent_id IS NULL ORDER BY category_name ASC");

    // 获取所有子分类，并按 parent_id 分组
    $subcategories = $wpdb->get_results("SELECT id, category_name, parent_id FROM {$table_name_category} WHERE parent_id IS NOT NULL ORDER BY category_name ASC");

    $subcategories_grouped = [];
    foreach ($subcategories as $subcategory) {
        $subcategories_grouped[$subcategory->parent_id][] = $subcategory;
    }
    ?>

    <!-- 分类菜单 -->
    <div class="category-menu" id="category-menu">
        <div class="menu-header">

            <i class="fa fa-cog settings-icon" id="settings-icon"></i> <!-- 设置图标 -->
            <a href="javascript:void(0)" class="dhs-tuku-open-modal" data-modal="newcategory:30">
                <i class="fa fa-plus-circle new-category-icon" id="new-category-icon"></i> <!-- 新建图标 -->
            </a>
        </div>
        <?php foreach ($categories as $category): ?>
            <div class="category-column">

                <a class="zhu-category" href="<?php echo esc_url(add_query_arg('category_id', $category->id, site_url('/tuku/tuku_categories'))); ?>">
                    <?php echo esc_html($category->category_name); ?>
                </a>
                <a href="javascript:void(0)" class="edit-link dhs-tuku-open-modal" data-modal="editcategory:30" data-category-id="<?php echo esc_attr($category->id); ?>">
                    <i class=" fa fa-pencil edit-icon" style="display: none;"></i> <!-- 编辑图标 -->
                </a>
                <ul>
                    <?php if (!empty($subcategories_grouped[$category->id])): ?>
                        <?php foreach ($subcategories_grouped[$category->id] as $subcategory): ?>
                            <li>

                                <a href="<?php echo esc_url(add_query_arg('category_id', $subcategory->id, site_url('/tuku/tuku_categories'))); ?>">
                                    <?php echo esc_html($subcategory->category_name); ?>
                                </a>
                                <a href="javascript:void(0)" class="edit-link dhs-tuku-open-modal" data-modal="editcategory:30" data-category-id="<?php echo esc_attr($subcategory->id); ?>"> <!-- 确保这里使用的是子分类的ID -->
                                    <i class=" fa fa-pencil edit-icon" style="display: none;"></i> <!-- 编辑图标 -->
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="javascript:void(0)">暂无子分类</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>





<style>
    /* 设置图标样式 */
    .settings-icon,
    .new-category-icon {
        font-size: 20px;
        color: #b6bac6;
        cursor: pointer;
        position: absolute;
        right: 20px;

    }

    /* 设置图标样式 */
    .settings-icon {
        top: 50px;
    }

    .new-category-icon {
        top: 20px;
    }

    .new-category-icon:hover {
        color: #fff;
    }


    .settings-icon:hover {
        color: #fff;
    }


    /* 分类菜单头部样式 */
    .menu-header {
        height: 40px;
        margin-bottom: 10px;
        text-align: right;
        /* 确保图标在右侧 */
    }

    /* 编辑图标样式 */
    .edit-icon {
        padding: 5px;
        font-size: 14px;
        color: #b6bac6;
        cursor: pointer;
        vertical-align: middle;
        border-radius: 50px;
        background-color: transparent;
        transition: background-color 0.6s ease;
        margin-left: 10px;

    }

    /* 编辑图标样式 */
    .edit-icon:hover {
        color: #4f5564;
        background-color: #fff;

    }



    /* 菜单栏样式 */
    .dhs-tuku-menu {
        background-color: none;
        padding: 10px 0;
        text-align: center;
        margin-bottom: 20px;
        margin-top: 20px;
        position: relative;

    }

    .dhs-tuku-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin: 0 auto;
    }

    .dhs-tuku-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
    }

    .dhs-tuku-nav ul li {
        background-color: none;
        margin-right: 20px;
        position: relative;
    }

    .zhu-category {
        margin-left: 10px;
        font-size: 18px;
        font-weight: bold;
        color: #b6bac6;
        transition: color 0.3s ease;
    }

    .zhu-category:hover {
        color: #fff;
    }

    .dhs-tuku-nav ul li a {
        border-radius: 4px;
        color: #4f5564;
        text-decoration: none;
        font-size: 16px;
        padding: 5px 10px;
        transition: background-color 0.3s ease;
    }

    .dhs-tuku-nav ul li a i {
        margin-right: 5px;
        font-size: 12px;
        color: #4f5564;
    }

    .dhs-tuku-nav ul li a:hover {
        background-color: #f6f6f6;
        font-weight: bold;
    }


    #category-menu {
        position: absolute;
        width: 100%;
        left: 0;
        background: linear-gradient(180deg, #4f5564, #818796);
        border-radius: 6px;
        padding: 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 100;
        display: flex;
        flex-wrap: wrap;
        transform-origin: top left;
        /* 从顶部左侧弹开 */
        transform: scaleY(0);
        /* Y 轴缩小 */
        transition: transform 0.4s ease, padding 0.4s ease, opacity 0.4s ease;
        opacity: 0;
        height: auto;
        overflow: hidden;
    }

    #category-menu.active {
        transform: scaleY(1);
        /* Y 轴展开 */
        opacity: 1;
        /* 完全显示 */
        padding: 20px;
        /* 添加内边距 */
    }

    .category-column {
        text-align: left;
        flex: 1 1 18%;
        /* 保证每个分类列占 18% 的宽度，五列布局 */
        min-width: 180px;
        /* 设置最小宽度以避免列太窄 */
        margin-bottom: 20px;
        padding: 10px;
        margin-right: 15px;
    }

    .category-column h4 {
        font-size: 16px;
        color: #fff;
        margin-left: 10px;
        margin-bottom: 10px;
    }

    .category-column ul {
        list-style: none;
        padding: 0;
    }

    .category-column ul li {
        margin: 0px 3px 0px 0px;
        padding: 5px 10px;
        background-color: rgba(79, 85, 100, 0.0);
        border-radius: 0px;
        transition: background-color 0.3s ease, border-radius 0.8s ease;
    }

    .category-column ul li:hover {
        background-color: #454b5b;
        border-radius: 50px;
    }

    .category-column ul li a {
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        color: #b6bac6;
    }

    .category-column ul li a:hover {
        color: #fff;
        font-weight: bold;
    }

    .dhs-tuku-buttons {
        display: flex;
    }

    .dhs-tuku-buttons a {
        border-radius: 4px;
        margin-left: 10px;
        color: #4f5564;
        text-decoration: none;
        font-size: 16px;
        padding: 5px 10px;
        transition: background-color 0.3s ease, border-color 0.3s ease;
        /* 添加边框颜色的过渡 */
        border: 1px solid #dcdcdc;
        /* 添加2px的实线边框，颜色与文本颜色相同 */
    }

    .dhs-tuku-buttons a i {
        margin-right: 5px;
        font-size: 12px;
        color: #4f5564;
    }

    .dhs-tuku-buttons a:hover {
        background-color: #f6f6f6;
        font-weight: bold;
        border: 1px solid #4f5564;

    }

    /* 搜索框动画样式 */
    .dhs-search-container {
        position: absolute;
        top: 50%;
        left: 110%;
        transform: translateY(-50%);
        background-color: #f6f6f6;
        padding: 5px;
        border-radius: 4px;
        align-items: center;
        z-index: 100;
        width: 0;
        /* 初始宽度为0 */
        opacity: 0;
        /* 初始透明度为0 */
        overflow: hidden;
        transition: width 0.4s ease, opacity 0.4s ease;
        /* 动画效果 */
        display: flex;
        /* 保持输入框和按钮在一行 */
    }

    .dhs-search-container form {
        display: flex;
        align-items: center;
    }

    .dhs-search-container.active {
        width: 250px;
        opacity: 1;
    }

    .dhs-search-container input {
        height: 30px;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 5px 10px;
        margin-right: 10px;
        font-size: 14px;
        width: 100%;
    }

    .dhs-search-container button {
        margin-right: 10px;
        background-color: #EB0024;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        white-space: nowrap;
    }

    .dhs-search-container button:hover {
        background-color: #8b0015;

    }

</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var searchToggle = document.getElementById('search-toggle');
        var searchContainer = document.getElementById('search-container');
        var categoriesToggle = document.getElementById('categories-toggle');
        var categoryMenu = document.getElementById('category-menu');

        // 切换搜索框的显示和隐藏
        searchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // 阻止事件冒泡，避免点击搜索按钮时关闭搜索框
            searchContainer.classList.toggle('active'); // 切换active类，控制动画显示和隐藏
        });

        // 点击空白区域隐藏搜索框
        document.addEventListener('click', function(e) {
            if (!searchContainer.contains(e.target) && e.target !== searchToggle) {
                searchContainer.classList.remove('active');
            }
        });

        // 监听分类查看按钮的点击事件
        categoriesToggle.addEventListener('click', function(e) {
            e.preventDefault();
            categoryMenu.classList.toggle('active'); // 切换菜单的显示状态
        });

        // 点击空白区域隐藏分类菜单
        document.addEventListener('click', function(e) {
            if (!categoryMenu.contains(e.target) && e.target !== categoriesToggle) {
                categoryMenu.classList.remove('active');
            }
        });

        const settingsIcon = document.getElementById('settings-icon');
        const editIcons = document.querySelectorAll('.edit-icon');

        // 切换编辑图标显示/隐藏
        settingsIcon.addEventListener('click', function() {
            editIcons.forEach(icon => {
                if (icon.style.display === 'none') {
                    icon.style.display = 'inline-block';
                } else {
                    icon.style.display = 'none';
                }
            });
        });
    });
</script>