# Donhauser-Tuku (DHS Gallery)

A WordPress image and album management plugin designed specifically for designers, featuring AI-powered intelligent tag generation, multi-user permission management, album categorization, and more.

## 🚀 Key Features

- **AI Intelligent Tag Generation** - Supports multiple AI services for automatic image tagging
- **Multi-User Permission Management** - Complete user permission control system
- **Album Management** - Album creation, categorization, and permission settings
- **Image Management** - Batch upload, tag management, and favorites functionality
- **Search Functionality** - Intelligent search based on tags and albums
- **Responsive Design** - Mobile and desktop support
- **Caching System** - Built-in caching mechanism for performance enhancement

## 📋 System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory Limit**: Recommended minimum 256MB

## 🛠️ Installation Methods

### Method 1: Install via WordPress Admin
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Install Plugins
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin

### Method 2: Manual Installation
1. Upload the plugin folder to `/wp-content/plugins/` directory
2. Activate the plugin in WordPress Admin
3. Go to settings page to configure AI services

## 🤖 AI Configuration Guide

### Supported AI Services

This plugin supports multiple AI services, ranked by priority:

#### 1. LM Studio (Local Deployment) - Recommended
- **Advantages**: Completely local, no network dependency, privacy and security
- **Configuration Requirements**: Local LM Studio service running
- **Default Settings**: `http://localhost:1234/v1/chat/completions`

**Configuration Steps**:
1. Download and install [LM Studio](https://lmstudio.ai/)
2. Download models that support image understanding (such as llava)
3. Start LM Studio service
4. Configure API address and model name in plugin settings

**Recommended Models**:
- `llava-v1.5-7b` - Balanced performance and accuracy
- `llava-v1.6-vicuna-13b` - Higher accuracy
- `llava-v1.6-mistral-7b` - Fast processing

#### 2. OpenAI API (Cloud Service)
- **Advantages**: Stable and reliable, high model quality
- **Configuration Requirements**: OpenAI API key
- **Default Model**: `gpt-4o-mini`

**Configuration Steps**:
1. Register an [OpenAI](https://openai.com/) account
2. Obtain API key
3. Enter API key in plugin settings
4. Select appropriate model

#### 3. Hugging Face (Alternative Option)
- **Advantages**: Open-source models, lower cost
- **Configuration Requirements**: Hugging Face API key
- **Supported Models**: Image classification, image description, object detection

### AI Settings Configuration

#### Basic Settings
1. Go to **WordPress Admin** → **DHS Gallery** → **Settings**
2. Find the **AI Tag Generation** section
3. Enable AI tag generation functionality

#### LM Studio Configuration
```php
// Configure the following options in the settings page:
LM Studio API Address: http://localhost:1234/v1/chat/completions
Model Name: llava-v1.5-7b
API Key: (Optional, if configured)
```

#### OpenAI Configuration
```php
// Configure the following options in the settings page:
OpenAI API Key: sk-xxxxxxxxxxxxxxxxxxxxxxxx
Model Selection: gpt-4o-mini (Recommended) or gpt-4-vision-preview
```

#### Language Settings
- **Chinese**: Generate Chinese tags (default)
- **English**: Generate English tags (requires AI model support)
- **Auto-detect**: Automatically select language based on image content

> **Note**: English tag generation requires AI model support for English output, while the interface language remains Chinese.

### AI Tag Generation Usage

#### Single Image Tag Generation
1. In the image management page, click the "AI Tags" button below the image
2. System automatically calls the configured AI service
3. After generating tags, you can edit, confirm, or reject them

#### Batch Tag Generation
1. In the album management page, select "Batch AI Tags"
2. Choose the album or image range to process
3. Set processing quantity limit (recommended no more than 50 per batch)
4. System automatically processes in batch and generates tags

#### Tag Quality Optimization
- **Adjust Prompts**: Customize prompt templates in AI settings
- **Tag Filtering**: Automatically filter duplicate and low-quality tags
- **Manual Review**: Generated tags require manual confirmation before taking effect

## 📁 Feature Module Details

### Album Management
- **Create Albums**: Support for album names, descriptions, and cover settings
- **Permission Control**: Public, private, password-protected access permissions
- **Category Management**: Support for album categorization and tagging
- **Batch Operations**: Batch delete, move, and permission modification

### Image Management
- **Upload Functionality**: Support for drag-and-drop and batch uploads
- **Format Support**: JPG, PNG, TIFF, GIF, and other mainstream formats
- **Tag System**: Manual tags, AI tags, and tag categorization
- **Favorites Function**: User personal favorites folder
- **Search Functionality**: Intelligent search based on tags, filenames, and albums

### User Permissions
- **Role Management**: Administrator, Editor, Contributor, Subscriber
- **Permission Control**: Upload, edit, delete, manage albums, and other permissions
- **Content Ownership**: Users can only manage content they uploaded

### Shortcode Usage
```php
// Display album list
[dhs_tuku_albums]

// Display images from specified album
[dhs_tuku_gallery album_id="1"]

// Display user favorites
[dhs_tuku_favorites]

// Display upload form
[dhs_tuku_upload]
```

## 🔧 Advanced Configuration

### Cache Settings
- Enable/disable cache functionality
- Set cache expiration time
- Manual cache clearing

### Upload Settings
- Maximum file size limit
- Allowed file types
- Image quality compression settings

### Performance Optimization
- Image lazy loading
- Thumbnail generation
- CDN support configuration

## 🚨 Troubleshooting

### AI Service Connection Issues
1. **Check LM Studio Service Status**
   ```bash
   curl http://localhost:1234/v1/models
   ```

2. **Verify API Key**
   - Ensure OpenAI API key is valid
   - Check if API quota is sufficient

3. **Network Connection Issues**
   - Check firewall settings
   - Verify proxy configuration

### Common Errors and Solutions

#### "LM Studio Unavailable" Error
- Ensure LM Studio service is running
- Check if port 1234 is occupied
- Verify model is correctly loaded

#### "Insufficient Permissions" Error
- Check user role permission settings
- Confirm WordPress user permission configuration
- Verify nonce security validation

#### "Image Upload Failed" Error
- Check file size limit
- Verify file type is allowed
- Confirm directory write permissions

## 📊 Performance Monitoring

### System Statistics
- Total images and storage space
- User activity statistics
- AI tag generation success rate
- System performance metrics

### Log Recording
- Error log recording
- User operation logs
- AI service call logs
- Performance monitoring logs

## 🔒 Security Features

- **CSRF Protection**: All forms have nonce verification
- **Permission Verification**: Strict user permission checks
- **File Type Validation**: Strict file upload security checks
- **SQL Injection Protection**: Uses WordPress prepared statements
- **XSS Protection**: Automatic output content escaping

## 🌐 Internationalization Support

- **Current Status**: Chinese interface only
- **Technical Foundation**: WordPress internationalization framework integrated
- **Extensibility**: Support for adding other language packs
- **Timezone Support**: Support for timezone and date format localization

> **Note**: Currently, the plugin interface only provides Chinese version, English support is under development.

## 📝 Development Documentation

### Hook System
```php
// Image upload hook
do_action('dhs_tuku_image_uploaded', $image_id, $album_id);

// AI tag generation hook
do_action('dhs_tuku_ai_tags_generated', $image_id, $tags);

// Album creation hook
do_action('dhs_tuku_album_created', $album_id);
```

### Filter System
```php
// Custom AI prompts
add_filter('dhs_tuku_ai_prompt', 'custom_ai_prompt');

// Custom tag processing
add_filter('dhs_tuku_process_tags', 'custom_tag_processor');
```

## 🤝 Contributing Guidelines

Welcome to submit Issues and Pull Requests!

### Development Environment Setup
1. Fork the project repository
2. Clone to local machine
3. Create feature branch
4. Submit changes
5. Create Pull Request

### Code Standards
- Follow WordPress coding standards
- Use meaningful variable and function names
- Add appropriate comments and documentation
- Ensure code passes PHP_CodeSniffer checks

## 📄 License

This project is licensed under [GPL v2](LICENSE).

## 👨‍💻 Author

- **Developer**: aiden
- **Project**: Donhauser-Tuku
- **Version**: 1.2.0

## 🙏 Acknowledgments

Thanks to the following open-source projects and services:
- WordPress community
- OpenAI API
- Hugging Face
- LM Studio
- All contributors and users

## 📞 Support and Feedback

If you encounter issues or have suggestions, please:
1. Check the troubleshooting section of this document
2. Submit an Issue on GitHub
3. Contact the development team

---

**Note**: When using AI features, please ensure compliance with relevant service terms of use and privacy policies.