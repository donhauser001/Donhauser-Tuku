<?php
/**
 * DHS Tuku 自动标签生成器
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DHS_Tuku_Auto_Tagger
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // 私有构造函数，防止直接实例化
    }

    /**
     * 从文件名提取标签
     * 
     * @param string $filename 文件名
     * @return array 提取的标签数组
     */
    public function extract_tags_from_filename($filename)
    {
        $tags = [];
        
        // 移除文件扩展名
        $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        // 常见分隔符：下划线、中划线、空格、点号
        $separators = ['_', '-', ' ', '.', '、', '，', ','];
        
        // 替换所有分隔符为统一分隔符
        $normalized_name = str_replace($separators, '|', $name_without_ext);
        
        // 分割成标签候选
        $tag_candidates = explode('|', $normalized_name);
        
        foreach ($tag_candidates as $candidate) {
            $candidate = trim($candidate);
            
            // 过滤掉数字、很短的字符串和特殊字符
            if (strlen($candidate) >= 2 && !is_numeric($candidate) && !preg_match('/^[0-9\-_\.]+$/', $candidate)) {
                $tags[] = $candidate;
            }
        }
        
        return array_unique($tags);
    }

    /**
     * 从EXIF数据提取标签
     * 
     * @param string $image_path 图片路径
     * @return array 提取的标签数组
     */
    public function extract_tags_from_exif($image_path)
    {
        $tags = [];
        
        if (!function_exists('exif_read_data') || !file_exists($image_path)) {
            return $tags;
        }
        
        try {
            $exif = exif_read_data($image_path, 'IFD0,EXIF');
            
            if ($exif !== false) {
                // 相机品牌和型号
                if (!empty($exif['Make'])) {
                    $tags[] = trim($exif['Make']);
                }
                if (!empty($exif['Model'])) {
                    $tags[] = trim($exif['Model']);
                }
                
                // 拍摄日期（提取年份和月份）
                if (!empty($exif['DateTime'])) {
                    $date = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTime']);
                    if ($date) {
                        $tags[] = $date->format('Y年');
                        $tags[] = $date->format('n月');
                    }
                }
                
                // GPS信息（如果有的话）
                if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
                    $tags[] = 'GPS定位';
                }
                
                // 关键词（某些相机或软件会写入）
                if (!empty($exif['Keywords'])) {
                    $keywords = explode(',', $exif['Keywords']);
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        if (!empty($keyword)) {
                            $tags[] = $keyword;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('EXIF读取失败: ' . $e->getMessage());
        }
        
        return array_unique($tags);
    }

    /**
     * 基于相册名称生成标签
     * 
     * @param string $album_name 相册名称
     * @return array 标签数组
     */
    public function extract_tags_from_album($album_name)
    {
        if (empty($album_name)) {
            return [];
        }
        
        // 使用相同的文件名标签提取逻辑
        return $this->extract_tags_from_filename($album_name);
    }

    /**
     * 基于图片内容的智能标签（颜色分析）
     * 
     * @param string $image_path 图片路径
     * @return array 标签数组
     */
    public function extract_color_tags($image_path)
    {
        $tags = [];
        
        if (!file_exists($image_path) || !extension_loaded('gd')) {
            return $tags;
        }
        
        try {
            $image_info = getimagesize($image_path);
            if ($image_info === false) {
                return $tags;
            }
            
            $image = null;
            switch ($image_info[2]) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($image_path);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($image_path);
                    break;
                default:
                    return $tags;
            }
            
            if ($image === false) {
                return $tags;
            }
            
            // 缩小图片以提高分析速度
            $width = imagesx($image);
            $height = imagesy($image);
            $new_width = min(100, $width);
            $new_height = ($height * $new_width) / $width;
            
            $small_image = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($small_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // 分析主要颜色
            $color_counts = [];
            for ($x = 0; $x < $new_width; $x += 2) {
                for ($y = 0; $y < $new_height; $y += 2) {
                    $rgb = imagecolorat($small_image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    
                    // 将颜色分类到基本颜色
                    $color_name = $this->get_color_name($r, $g, $b);
                    if ($color_name) {
                        $color_counts[$color_name] = ($color_counts[$color_name] ?? 0) + 1;
                    }
                }
            }
            
            // 清理内存
            imagedestroy($image);
            imagedestroy($small_image);
            
            // 获取最主要的颜色（占比超过10%）
            $total_pixels = ($new_width / 2) * ($new_height / 2);
            foreach ($color_counts as $color => $count) {
                if ($count / $total_pixels > 0.1) {
                    $tags[] = $color;
                }
            }
            
        } catch (Exception $e) {
            error_log('颜色分析失败: ' . $e->getMessage());
        }
        
        return $tags;
    }

    /**
     * 将RGB值转换为颜色名称
     * 
     * @param int $r 红色值
     * @param int $g 绿色值
     * @param int $b 蓝色值
     * @return string|null 颜色名称
     */
    private function get_color_name($r, $g, $b)
    {
        // 基本颜色阈值判断
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        
        // 灰度
        if ($max - $min < 30) {
            if ($max < 50) return '黑色';
            if ($max > 200) return '白色';
            return '灰色';
        }
        
        // 主要颜色判断
        if ($r > $g && $r > $b && $r > 100) {
            if ($g > 100) return '橙色';
            return '红色';
        }
        
        if ($g > $r && $g > $b && $g > 100) {
            if ($b > 100) return '青色';
            return '绿色';
        }
        
        if ($b > $r && $b > $g && $b > 100) {
            if ($r > 100) return '紫色';
            return '蓝色';
        }
        
        if ($r > 150 && $g > 150 && $b < 100) {
            return '黄色';
        }
        
        return null;
    }

    /**
     * 综合自动标签生成
     * 
     * @param string $image_path 图片路径
     * @param string $filename 文件名
     * @param string $album_name 相册名称
     * @return array 所有自动生成的标签
     */
    public function generate_auto_tags($image_path, $filename, $album_name = '')
    {
        $all_tags = [];
        
        // 1. 从文件名提取标签
        $filename_tags = $this->extract_tags_from_filename($filename);
        $all_tags = array_merge($all_tags, $filename_tags);
        
        // 2. 从EXIF数据提取标签
        $exif_tags = $this->extract_tags_from_exif($image_path);
        $all_tags = array_merge($all_tags, $exif_tags);
        
        // 3. 从相册名称提取标签
        if (!empty($album_name)) {
            $album_tags = $this->extract_tags_from_album($album_name);
            $all_tags = array_merge($all_tags, $album_tags);
        }
        
        // 4. 颜色分析标签
        $color_tags = $this->extract_color_tags($image_path);
        $all_tags = array_merge($all_tags, $color_tags);
        
        // 清理标签内容
        $all_tags = array_map([$this, 'clean_tag'], $all_tags);
        
        // 清理和去重
        $all_tags = array_unique(array_filter($all_tags, [$this, 'is_valid_tag']));
        
        return array_values($all_tags);
    }

    /**
     * 清理标签内容
     * 移除数字括号、年月等后缀
     * 
     * @param string $tag 原始标签
     * @return string 清理后的标签
     */
    private function clean_tag($tag)
    {
        if (empty($tag)) {
            return '';
        }
        
        // 移除数字加括号的后缀，例如：橙色(67) -> 橙色
        $tag = preg_replace('/\(\d+\)$/', '', $tag);
        
        // 移除年月日后缀，例如：设计2019年 -> 设计
        $tag = preg_replace('/\d{4}年\d{1,2}月?$/', '', $tag);
        $tag = preg_replace('/\d{4}年$/', '', $tag);
        $tag = preg_replace('/\d{1,2}月$/', '', $tag);
        
        // 清理多余的空白字符
        $tag = trim($tag);
        
        return $tag;
    }

    /**
     * 验证标签是否有效
     * 过滤掉年份、月份、纯数字、数字加括号等无效标签
     * 
     * @param string $tag 待验证的标签
     * @return bool 是否有效
     */
    private function is_valid_tag($tag)
    {
        // 基本检查：非空且长度合适
        if (empty($tag) || strlen($tag) < 2 || strlen($tag) > 50) {
            return false;
        }
        
        // 清理标签（移除多余空格）
        $tag = trim($tag);
        
        // 过滤纯数字
        if (is_numeric($tag)) {
            return false;
        }
        
        // 过滤年份格式（1900-2099年）
        if (preg_match('/^(19|20)\d{2}年?$/', $tag)) {
            return false;
        }
        
        // 过滤月份格式（1-12月）
        if (preg_match('/^(0?[1-9]|1[0-2])月$/', $tag)) {
            return false;
        }
        
        // 注意：数字加括号已在clean_tag中处理，此处不需要过滤
        
        // 过滤纯数字加单位（如：2019年、4月等）
        if (preg_match('/^\d+[年月日]$/', $tag)) {
            return false;
        }
        
        // 过滤包含大量数字的标签（超过50%是数字）
        $digit_count = preg_match_all('/\d/', $tag);
        $total_length = strlen($tag);
        if ($digit_count > 0 && ($digit_count / $total_length) > 0.5) {
            return false;
        }
        
        // 过滤单个字符或过短的无意义标签
        if (strlen($tag) < 2) {
            return false;
        }
        
        return true;
    }

    /**
     * 保存自动生成的标签到数据库
     * 
     * @param int $image_id 图片ID
     * @param array $tags 标签数组
     * @param bool $auto_confirm 是否自动确认（不需要用户审核）
     * @return bool 成功/失败
     */
    public function save_auto_tags($image_id, $tags, $auto_confirm = false)
    {
        global $wpdb;
        
        if (empty($tags) || !is_array($tags)) {
            return false;
        }
        
        try {
            $tags_table = $wpdb->prefix . 'dhs_gallery_tags';
            $image_tags_table = $wpdb->prefix . 'dhs_gallery_image_tag';
            $pending_tags_table = $wpdb->prefix . 'dhs_gallery_pending_tags';
            
            foreach ($tags as $tag_name) {
                $tag_name = trim($tag_name);
                if (empty($tag_name)) continue;
                
                // 检查标签是否已存在
                $tag_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$tags_table} WHERE tag_name = %s",
                    $tag_name
                ));
                
                // 如果标签不存在，创建新标签
                if (!$tag_id) {
                    $wpdb->insert(
                        $tags_table,
                        ['tag_name' => $tag_name],
                        ['%s']
                    );
                    $tag_id = $wpdb->insert_id;
                }
                
                if ($tag_id) {
                    if ($auto_confirm) {
                        // 直接关联标签
                        $wpdb->replace(
                            $image_tags_table,
                            [
                                'image_id' => $image_id,
                                'tag_id' => $tag_id
                            ],
                            ['%d', '%d']
                        );
                    } else {
                        // 保存到待审核表（需要先创建这个表）
                        // 这里暂时直接关联，后续可以添加审核机制
                        $wpdb->replace(
                            $image_tags_table,
                            [
                                'image_id' => $image_id,
                                'tag_id' => $tag_id
                            ],
                            ['%d', '%d']
                        );
                    }
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('保存自动标签失败: ' . $e->getMessage());
            return false;
        }
    }
}
