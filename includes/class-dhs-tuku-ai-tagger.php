<?php
/**
 * DHS Tuku AI标签生成器
 * 支持本地 LM Studio（OpenAI 兼容接口）与 OpenAI；Hugging Face 作为备选
 */
class DHS_Tuku_AI_Tagger
{
    // 支持多种AI服务
    private $ai_services = [
        'lmstudio' => [
            // 可通过后台选项 dhs_lmstudio_api_url 覆盖，默认本地 LM Studio 接口
            'api_url' => 'http://localhost:1234/v1/chat/completions',
            // 可通过后台选项 dhs_lmstudio_model 覆盖
            'model'   => 'llava',
            'enabled' => true
        ],
        'openai' => [
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'model' => 'gpt-4o-mini',
            'enabled' => true
        ],
        'huggingface' => [
            'api_url' => 'https://api-inference.huggingface.co/models/',
            'models' => [
                'image_classification' => 'google/vit-base-patch16-224',
                'image_captioning' => 'Salesforce/blip-image-captioning-base',
                'object_detection' => 'facebook/detr-resnet-50'
            ],
            'enabled' => false
        ]
    ];

    /**
     * 生成AI标签
     */
    public function generate_ai_tags($image_path, $filename, $album_name = '')
    {
        // 读取并编码图片
        $image_data = file_get_contents($image_path);
        if ($image_data === false) {
            return [];
        }
        $base64_image = base64_encode($image_data);
        $mime_type = mime_content_type($image_path);

        // 优先使用 LM Studio（本地）
        if ($this->ai_services['lmstudio']['enabled']) {
            $tags = $this->generate_compatible_tags('lmstudio', $base64_image, $mime_type, $filename, $album_name);
            if (!empty($tags)) {
                return $tags;
            }
        }

        // 其次使用 OpenAI（云端）
        if ($this->ai_services['openai']['enabled']) {
            $tags = $this->generate_compatible_tags('openai', $base64_image, $mime_type, $filename, $album_name);
            if (!empty($tags)) {
                return $tags;
            }
        }
        
        // 降级到Hugging Face
        if ($this->ai_services['huggingface']['enabled']) {
            return $this->generate_huggingface_tags($image_path, $filename, $album_name);
        }

        return [];
    }

    /**
     * 使用 OpenAI 兼容接口（LM Studio/OpenAI）生成标签
     */
    private function generate_compatible_tags($serviceKey, $base64_image, $mime_type, $filename, $album_name)
    {
        try {
            // 根据设置构建提示语
            $language = get_option('dhs_ai_tag_language', 'chinese');
            $prompt = $this->build_ai_prompt($language);
            if (!empty($album_name)) { $prompt .= "\n相册名称：" . $album_name; }
            if (!empty($filename)) { $prompt .= "\n文件名：" . $filename; }

            // 准备API请求（OpenAI Chat Completions 格式，带 image_url）
            $payload = [
                'model' => $this->get_model_for_service($serviceKey),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [ 'type' => 'text', 'text' => $prompt ],
                            [ 'type' => 'image_url', 'image_url' => [ 'url' => "data:{$mime_type};base64,{$base64_image}" ] ]
                        ]
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.3
            ];

            $response = $this->call_compatible_api($payload, $serviceKey);
            if (isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];
                $tags = $this->parse_tags_from_content($content);
                return $tags;
            }
        } catch (Exception $e) {
            error_log(strtoupper($serviceKey) . ' 标签生成失败: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * 从模型返回的 content 中解析标签
     * 支持中文逗号、顿号、分号、句号、换行等分隔符
     */
    private function parse_tags_from_content($content)
    {
        if (!is_string($content)) { return []; }
        // 统一全角标点为空格，便于切分
        $normalized = str_replace(["\r"], '', $content);
        // 按常见分隔符切分：中文逗号、英文逗号、顿号、分号、句号、换行、制表符
        $parts = preg_split('/[，,、；;。\n\t]+/u', $normalized);
        if (!is_array($parts)) { $parts = [$normalized]; }

        $clean = [];
        foreach ($parts as $p) {
            // 去除首尾空白与引号
            $t = trim($p);
            $t = trim($t, "\'\"“”‘’·•·.");
            // 过滤空/过短/过长
            if ($t !== '' && mb_strlen($t) >= 2 && mb_strlen($t) <= 20) {
                $clean[] = $t;
            }
        }

        // 去重并重建索引
        $clean = array_values(array_unique($clean));
        return $clean;
    }

    /**
     * 使用Hugging Face生成标签（备选方案）
     */
    private function generate_huggingface_tags($image_path, $filename, $album_name = '')
    {
        $tags = [];
        try {
            $classification_tags = $this->get_huggingface_classification_tags($image_path);
            if (!empty($classification_tags)) { $tags = array_merge($tags, $classification_tags); }
            $caption_tags = $this->get_huggingface_caption_tags($image_path);
            if (!empty($caption_tags)) { $tags = array_merge($tags, $caption_tags); }
            $object_tags = $this->get_huggingface_object_detection_tags($image_path);
            if (!empty($object_tags)) { $tags = array_merge($tags, $object_tags); }
        } catch (Exception $e) {
            error_log('Hugging Face标签生成错误: ' . $e->getMessage());
            return [];
        }
        $tags = $this->clean_and_filter_tags($tags);
        $tags = $this->translate_to_chinese($tags);
        return array_unique($tags);
    }

    private function get_huggingface_classification_tags($image_path)
    {
        $image_data = $this->prepare_image_data($image_path);
        if (!$image_data) return [];
        $response = $this->call_huggingface_api($this->ai_services['huggingface']['models']['image_classification'], $image_data);
        $tags = [];
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $result) {
                if (isset($result['label']) && isset($result['score']) && $result['score'] > 0.1) {
                    $tags[] = $this->clean_label($result['label']);
                }
            }
        }
        return array_slice($tags, 0, 5);
    }

    private function get_huggingface_caption_tags($image_path)
    {
        $image_data = $this->prepare_image_data($image_path);
        if (!$image_data) return [];
        $response = $this->call_huggingface_api($this->ai_services['huggingface']['models']['image_captioning'], $image_data);
        $tags = [];
        if (isset($response['data'][0]['generated_text'])) {
            $caption = $response['data'][0]['generated_text'];
            $keywords = $this->extract_keywords_from_caption($caption);
            $tags = array_merge($tags, $keywords);
        }
        return $tags;
    }

    private function get_huggingface_object_detection_tags($image_path)
    {
        $image_data = $this->prepare_image_data($image_path);
        if (!$image_data) return [];
        $response = $this->call_huggingface_api($this->ai_services['huggingface']['models']['object_detection'], $image_data);
        $tags = [];
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $object) {
                if (isset($object['label']) && isset($object['score']) && $object['score'] > 0.5) {
                    $tags[] = $this->clean_label($object['label']);
                }
            }
        }
        return array_unique($tags);
    }

    private function prepare_image_data($image_path)
    {
        if (!file_exists($image_path)) { return false; }
        $image_info = getimagesize($image_path);
        if (!$image_info) { return false; }
        $image_data = file_get_contents($image_path);
        if ($image_data === false) { return false; }
        return base64_encode($image_data);
    }

    private function call_huggingface_api($model, $image_data, $max_retries = 3)
    {
        $url = $this->ai_services['huggingface']['api_url'] . $model;
        for ($retry = 0; $retry < $max_retries; $retry++) {
            $response = wp_remote_post($url, [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body' => json_encode([ 'inputs' => $image_data ]),
                'timeout' => 30,
                'sslverify' => false
            ]);
            if (is_wp_error($response)) {
                if ($retry < $max_retries - 1) { sleep(2); continue; }
                return false;
            }
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (isset($data['error']) && strpos($data['error'], 'loading') !== false) {
                if ($retry < $max_retries - 1) { sleep(10); continue; }
            }
            if ($data) { return ['data' => $data]; }
        }
        return false;
    }

    private function extract_keywords_from_caption($caption)
    {
        $stop_words = ['a','an','the','is','are','was','were','in','on','at','to','for','of','with','by'];
        $words = preg_split('/[\s,\.!?]+/', strtolower($caption));
        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stop_words)) { $keywords[] = $word; }
        }
        return array_slice($keywords, 0, 8);
    }

    private function clean_label($label)
    {
        $label = preg_replace('/[0-9]+/', '', $label);
        $label = preg_replace('/[^a-zA-Z\s\-_]/', '', $label);
        $label = trim($label);
        $label = strtolower($label);
        return $label;
    }

    private function clean_and_filter_tags($tags)
    {
        $cleaned_tags = [];
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (mb_strlen($tag) >= 2 && mb_strlen($tag) <= 20) { $cleaned_tags[] = $tag; }
        }
        return array_unique($cleaned_tags);
    }

    private function translate_to_chinese($tags)
    {
        $translation_map = [
            'cat' => '猫','dog' => '狗','bird' => '鸟','fish' => '鱼','horse' => '马','cow' => '牛','elephant' => '大象','tiger' => '老虎','lion' => '狮子',
            'car' => '汽车','truck' => '卡车','bus' => '公交车','bicycle' => '自行车','motorcycle' => '摩托车','airplane' => '飞机','train' => '火车','boat' => '船',
            'house' => '房子','building' => '建筑','bridge' => '桥','tower' => '塔','church' => '教堂','castle' => '城堡',
            'tree' => '树','flower' => '花','grass' => '草','mountain' => '山','sky' => '天空','cloud' => '云','sun' => '太阳','moon' => '月亮','star' => '星星','water' => '水','river' => '河','lake' => '湖','ocean' => '海洋','beach' => '海滩','forest' => '森林',
            'food' => '食物','fruit' => '水果','apple' => '苹果','banana' => '香蕉','orange' => '橙子','bread' => '面包','cake' => '蛋糕',
            'red' => '红色','blue' => '蓝色','green' => '绿色','yellow' => '黄色','black' => '黑色','white' => '白色','purple' => '紫色','pink' => '粉色','orange' => '橙色','brown' => '棕色','gray' => '灰色',
            'person' => '人','man' => '男人','woman' => '女人','child' => '孩子','baby' => '婴儿',
            'beautiful' => '美丽','large' => '大','small' => '小','old' => '旧','new' => '新','indoor' => '室内','outdoor' => '户外',
        ];
        $chinese_tags = [];
        foreach ($tags as $tag) {
            $chinese_tags[] = $translation_map[$tag] ?? $tag;
        }
        return $chinese_tags;
    }

    /**
     * 检查AI服务是否可用
     */
    public function is_ai_service_available()
    {
        $ai_enabled = get_option('dhs_enable_ai_tags', 1);
        if (!$ai_enabled) { return false; }

        // 先测 LM Studio（本地）
        if ($this->ai_services['lmstudio']['enabled']) {
            $api_url = $this->get_api_url_for_service('lmstudio');
            // 尝试访问 /v1/models
            $models_url = str_replace('/chat/completions', '/models', $api_url);
            $test = wp_remote_get($models_url, [ 'timeout' => 3, 'sslverify' => false ]);
            if (!is_wp_error($test) && (int)wp_remote_retrieve_response_code($test) === 200) {
                return true;
            }
        }

        // 再测 OpenAI（需要 key）
        if ($this->ai_services['openai']['enabled']) {
            $api_key = $this->get_api_key('openai');
            if (!empty($api_key)) { return true; }
        }

        // 可选：Hugging Face（此处简单探测）
        if ($this->ai_services['huggingface']['enabled']) {
            $test_response = wp_remote_get('https://api-inference.huggingface.co/', [ 'timeout' => 5, 'sslverify' => false ]);
            if (!is_wp_error($test_response) && (int)wp_remote_retrieve_response_code($test_response) === 200) { return true; }
        }

        return false;
    }

    /**
     * 兼容接口调用（LM Studio / OpenAI）
     */
    private function call_compatible_api($payload, $serviceKey)
    {
        $api_url = $this->get_api_url_for_service($serviceKey);
        $headers = [ 'Content-Type' => 'application/json' ];
        $api_key = $this->get_api_key($serviceKey);
        if (!empty($api_key)) { $headers['Authorization'] = 'Bearer ' . $api_key; }

        $response = wp_remote_post($api_url, [
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => 60,
            'sslverify' => false
        ]);
        if (is_wp_error($response)) {
            throw new Exception(strtoupper($serviceKey) . ' API请求失败: ' . $response->get_error_message());
        }
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($status_code < 200 || $status_code >= 300) {
            throw new Exception(strtoupper($serviceKey) . ' API返回错误: ' . $status_code . ' - ' . $body);
        }
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(strtoupper($serviceKey) . ' API响应格式错误');
        }
        return $data;
    }

    private function get_api_url_for_service($serviceKey)
    {
        if ($serviceKey === 'lmstudio') {
            $url = get_option('dhs_lmstudio_api_url', 'http://localhost:1234/v1/chat/completions');
            return !empty($url) ? $url : $this->ai_services['lmstudio']['api_url'];
        }
        if ($serviceKey === 'openai') {
            return $this->ai_services['openai']['api_url'];
        }
        return '';
    }

    private function get_model_for_service($serviceKey)
    {
        if ($serviceKey === 'lmstudio') {
            $model = get_option('dhs_lmstudio_model', $this->ai_services['lmstudio']['model']);
            return $model;
        }
        if ($serviceKey === 'openai') {
            return $this->ai_services['openai']['model'];
        }
        return '';
    }

    private function get_api_key($serviceKey)
    {
        if ($serviceKey === 'openai') {
            // 优先从WordPress设置中获取
            $api_key = get_option('dhs_openai_api_key', '');
            if (empty($api_key)) { $api_key = defined('DHS_OPENAI_API_KEY') ? DHS_OPENAI_API_KEY : ''; }
            if (empty($api_key)) { $api_key = getenv('OPENAI_API_KEY'); }
            return $api_key;
        }
        if ($serviceKey === 'lmstudio') {
            // LM Studio 通常不需要 API Key，但保留可配置项
            $api_key = get_option('dhs_lmstudio_api_key', '');
            return $api_key;
        }
        return '';
    }

    /**
     * 生成基础标签作为AI的降级方案
     */
    public function generate_fallback_tags($image_path, $filename, $album_name = '')
    {
        $tags = [];
        $filename_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        $filename_parts = preg_split('/[_\-\s\.]+/', $filename_without_ext);
        foreach ($filename_parts as $part) { $part = trim($part); if (mb_strlen($part) > 1 && !is_numeric($part)) { $tags[] = $part; } }
        if (!empty($album_name)) {
            $album_parts = preg_split('/[_\-\s\.]+/', $album_name);
            foreach ($album_parts as $part) { $part = trim($part); if (mb_strlen($part) > 1 && !is_numeric($part)) { $tags[] = $part; } }
        }
        $image_info = getimagesize($image_path);
        if ($image_info) {
            $width = $image_info[0]; $height = $image_info[1];
            if ($width > $height) { $tags[] = '横向'; } elseif ($height > $width) { $tags[] = '纵向'; } else { $tags[] = '正方形'; }
            if ($width >= 1920 || $height >= 1080) { $tags[] = '高清'; }
        }
        $tags = array_unique(array_filter($tags, function ($tag) { return !empty(trim($tag)) && mb_strlen($tag) >= 2 && mb_strlen($tag) <= 20; }));
        return array_values($tags);
    }

    private function build_ai_prompt($language = 'chinese')
    {
        $prompts = [
            'chinese' => "请分析这张图片并生成5-10个中文标签。要求：\n1. 描述图片的主要内容、对象、风格、颜色\n2. 只返回标签，用逗号分隔\n3. 标签要简洁具体，2-8个字\n4. 包含艺术风格、颜色、主题等关键词\n5. 不要包含'图片'、'素材'等通用词",
            'english' => "Analyze this image and generate 5-10 English tags. Requirements:\n1. Describe main content, objects, style, colors\n2. Return only tags, separated by commas\n3. Tags should be concise and specific, 2-20 characters\n4. Include artistic style, colors, themes\n5. Avoid generic words like 'image', 'photo'",
            'auto' => "Analyze this image and generate 5-10 descriptive tags in the most appropriate language (Chinese preferred for Asian content, English for others). Requirements:\n1. Describe main content, objects, style, colors\n2. Return only tags, separated by commas  \n3. Tags should be concise and specific\n4. Include artistic style, colors, themes\n5. Avoid generic words"
        ];
        return $prompts[$language] ?? $prompts['chinese'];
    }
}
