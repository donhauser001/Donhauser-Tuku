import sys
import os

# 接收路径
image_path = sys.argv[1]

# 去除路径中的多余转义符号
image_path = image_path.replace("\\(", "(").replace("\\)", ")").replace("\\\\", "\\")

# 打印处理后的路径，确保正确
print(f"Processed image path: {image_path}")

# 检查文件是否存在
if not os.path.exists(image_path):
    print(f"Error: File does not exist: {image_path}")
    sys.exit(1)

# 继续处理
import torch
import clip
from PIL import Image
from transformers import MarianMTModel, MarianTokenizer

# 配置标签文件路径
label_file_path = '/Users/mac/Sites/dong/wp-content/plugins/dhs-tuku/lib/imagenet-labels.txt'

# 初始化模型和设备
device = "cuda" if torch.cuda.is_available() else "cpu"
model, preprocess = clip.load("ViT-B/32", device=device)  # 使用CLIP模型

# 使用 ImageNet 的标签
with open(label_file_path, 'r') as f:
    labels = [line.strip() for line in f.readlines()]

# 初始化翻译器
model_name = "Helsinki-NLP/opus-mt-en-zh"
tokenizer = MarianTokenizer.from_pretrained(model_name)
translation_model = MarianMTModel.from_pretrained(model_name)

# 准备图片
def generate_image_tags(image_path):
    try:
        image = preprocess(Image.open(image_path)).unsqueeze(0).to(device)

        # 将标签转化为 CLIP 所需的输入格式
        text_inputs = torch.cat([clip.tokenize(f"a photo of {c}") for c in labels]).to(device)

        # 使用CLIP进行预测
        with torch.no_grad():
            image_features = model.encode_image(image)
            text_features = model.encode_text(text_inputs)

            # 计算相似度
            logits_per_image, logits_per_text = model(image, text_inputs)
            probs = logits_per_image.softmax(dim=-1)  # 保持为 Tensor 类型

        # 输出最有可能的标签
        top_probs, top_labels = torch.topk(probs, k=3)  # 输出前3个最可能的标签
        tags = [labels[i] for i in top_labels[0].cpu().numpy()]

        # 去掉标签中的 "a" 或 "an"
        tags = [tag.replace('a ', '').replace('an ', '') for tag in tags]

        # 将标签翻译为中文
        translated_tags = []
        for tag in tags:
            translated = translation_model.generate(**tokenizer(tag, return_tensors="pt", padding=True))
            translated_tags.append(tokenizer.decode(translated[0], skip_special_tokens=True))
        
        print(f"Image: {image_path}")
        print("Predicted tags:", tags)
        print("Translated tags:", translated_tags)
        
    except Exception as e:
        print(f"Error: {str(e)}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        img_path = sys.argv[1]
        generate_image_tags(img_path)
    else:
        print("Please provide an image path as an argument.")