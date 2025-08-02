# KE Lubricants SEO Booster

A comprehensive WordPress plugin for automated SEO optimization using OpenAI API and Google Keyword Planner integration.

## 🚀 Features

- **AI-Powered SEO Generation**: Automatically generate SEO titles, descriptions, and keywords using OpenAI
- **Google Keyword Research**: Get keyword suggestions and search volume data from Google Ads API
- **Meta Tag Management**: Easy-to-use interface for managing SEO meta tags
- **Open Graph Tags**: Automatic generation of social media meta tags
- **Schema Markup**: Structured data markup for better search engine understanding
- **Bulk Operations**: Process multiple posts at once
- **Rate Limiting**: Built-in protection against API abuse
- **Error Handling**: Comprehensive error handling and logging
- **Security**: Input validation and output sanitization

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Memory**: 128MB or higher recommended
- **OpenAI API Key**: Required for AI-powered features
- **Google Ads API**: Optional for keyword research

## 🔧 Installation

1. **Download the plugin** to your WordPress site
2. **Upload to plugins directory**: `/wp-content/plugins/ke-lubricants-seo-booster/`
3. **Activate the plugin** through WordPress admin
4. **Configure API keys** in the plugin settings

## ⚙️ Configuration

### OpenAI API Setup

1. Get your API key from [OpenAI Platform](https://platform.openai.com/)
2. Go to **KE SEO Booster > Settings > API Settings**
3. Enter your OpenAI API key
4. Test the connection using the "Test API Key" button

### Google Ads API Setup (Optional)

1. Create a Google Ads API application
2. Get your Client ID, Client Secret, Developer Token, and Refresh Token
3. Enter the credentials in the API Settings tab
4. Test the connection

## 🎯 Usage

### Individual Post SEO

1. **Edit any post** in WordPress admin
2. **Find the "KE SEO Booster" meta box**
3. **Enter or generate** SEO title, description, and keywords
4. **Save the post**

### Bulk SEO Generation

1. **Go to KE SEO Booster dashboard**
2. **Select posts** for bulk processing
3. **Choose SEO fields** to generate
4. **Run the bulk operation**

### AI-Powered Generation

- **Click "Generate with AI"** for individual fields
- **Click "Generate All SEO Content"** for all fields at once
- **Review and edit** generated content as needed

## 🔍 Troubleshooting

### Common Issues

#### Plugin Won't Activate

**Problem**: Plugin causes fatal error on activation

**Solutions**:
- Check PHP version (requires 7.4+)
- Check WordPress version (requires 5.0+)
- Check memory limit (recommended 128MB+)
- Deactivate conflicting SEO plugins
- Check error logs for specific issues

#### API Connection Issues

**Problem**: OpenAI API calls failing

**Solutions**:
- Verify API key is correct
- Check internet connectivity
- Ensure API key has sufficient credits
- Check rate limiting settings
- Review error logs for specific messages

#### Meta Tags Not Appearing

**Problem**: SEO meta tags not showing on frontend

**Solutions**:
- Check if meta tags are enabled in settings
- Verify theme compatibility
- Check for JavaScript errors
- Ensure proper WordPress hooks are firing

#### Performance Issues

**Problem**: Plugin causing slow page loads

**Solutions**:
- Increase PHP memory limit
- Enable object caching
- Reduce API call frequency
- Check for conflicting plugins

### Error Logging

The plugin logs all errors to the WordPress error log. Check your server's error log for detailed information about issues.

Common log locations:
- `/wp-content/debug.log` (if WP_DEBUG_LOG is enabled)
- Server error logs (var/log/apache2/error.log, etc.)

### Debug Mode

Enable WordPress debug mode to get more detailed error information:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 🛡️ Security Features

- **Input Sanitization**: All user inputs are sanitized
- **Output Escaping**: All outputs are properly escaped
- **Nonce Verification**: AJAX requests are protected with nonces
- **Capability Checks**: Admin functions require proper permissions
- **Rate Limiting**: API calls are rate-limited to prevent abuse
- **Error Handling**: Sensitive information is not exposed in errors

## 📊 API Rate Limits

### OpenAI API
- **Requests per minute**: 10
- **Token limit per request**: 150
- **Timeout**: 30 seconds

### Google Ads API
- **Requests per minute**: 5
- **Timeout**: 30 seconds

## 🔧 Advanced Configuration

### Custom Post Types

Add custom post types to SEO optimization:

1. Go to **Settings > General**
2. Check the post types you want to optimize
3. Save settings

### Schema Markup

Enable automatic schema markup generation:

1. Go to **Settings > General**
2. Check "Enable Schema Markup"
3. Save settings

### Open Graph Tags

Enable automatic social media meta tags:

1. Go to **Settings > General**
2. Check "Enable Open Graph Tags"
3. Save settings

## 📝 Changelog

### Version 2.0.0
- Complete rewrite with modular architecture
- Enhanced error handling and crash prevention
- Improved security with input validation
- Added comprehensive logging
- Rate limiting for API calls
- Better admin interface
- Open Graph and Schema markup support

### Version 1.1
- Initial release
- Basic SEO generation features
- OpenAI integration

## 🤝 Support

### Getting Help

1. **Check the troubleshooting section** above
2. **Review error logs** for specific issues
3. **Test with default theme** to rule out theme conflicts
4. **Disable other plugins** to check for conflicts

### Reporting Issues

When reporting issues, please include:
- WordPress version
- PHP version
- Plugin version
- Error messages from logs
- Steps to reproduce the issue

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🙏 Credits

- **OpenAI** for AI-powered content generation
- **Google Ads API** for keyword research
- **WordPress** for the amazing platform

## 🔄 Updates

The plugin checks for updates automatically. You can also manually check for updates in the WordPress admin.

## 📚 Documentation

For more detailed documentation, visit the plugin's documentation site or check the inline code comments.

---

**Note**: This plugin requires active internet connectivity for API features to work. Ensure your server can make outbound HTTPS requests to OpenAI and Google APIs. 