# KE SEO Booster Pro

A full-featured, AI-powered WordPress SEO plugin with OpenAI integration, Google Keyword Planner, and comprehensive SEO tools.

## Features

- **SEO Meta Box**: Custom title, description, keywords, focus keyword, noindex/nofollow controls
- **AI Content Generation**: OpenAI-powered meta title and description generation
- **Social Media Tags**: Open Graph and Twitter Card support
- **Schema Markup**: JSON-LD structured data for articles, products, and organizations
- **XML Sitemap**: Automatic sitemap generation
- **Robots.txt Editor**: Custom robots.txt management
- **Keyword Suggestions**: Google Keyword Planner integration
- **Bulk Audit**: Mass SEO optimization and monitoring
- **Internal Linking**: Automatic internal link suggestions
- **Caching**: Transient-based caching for performance
- **WP-CLI Support**: Command-line tools for bulk operations

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- OpenAI API key (for AI features)
- Google Ads API credentials (for keyword suggestions)

## Installation

1. Download the plugin zip file
2. Upload to WordPress via Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Configure settings in SEO Booster → Settings

## Configuration

### OpenAI API Setup
1. Get your API key from [OpenAI](https://platform.openai.com/)
2. Go to SEO Booster → Settings → AI Generator
3. Enter your API key and save

### Google Ads API Setup
1. Create a Google Ads API account
2. Go to SEO Booster → Settings → Keyword Suggestions
3. Enter your credentials

## Usage

### Basic SEO Setup
1. Edit any post or page
2. Scroll down to the "SEO Booster" meta box
3. Fill in SEO title, description, and focus keyword
4. Use AI generation buttons for automatic content
5. Preview your snippet in the Preview tab

### Customizing Meta Output

You can filter the generated meta data using the `kseo_meta_data` hook:

```php
// Add this to your theme's functions.php or a custom plugin

add_filter('kseo_meta_data', function($meta_data, $post_id) {
    // Modify the meta data
    $meta_data['title'] = 'Custom Title: ' . $meta_data['title'];
    $meta_data['description'] = 'Custom Description: ' . $meta_data['description'];
    
    return $meta_data;
}, 10, 2);
```

### WP-CLI Commands

```bash
# Regenerate sitemap
wp kseo regenerate sitemap

# Clear all caches
wp kseo clear-cache

# Bulk optimize posts
wp kseo bulk-optimize

# Generate meta for all posts
wp kseo generate-meta
```

## Modules

The plugin is modular and you can enable/disable features:

- **Meta Box**: SEO fields in post editor
- **Meta Output**: Frontend meta tag injection
- **Social Tags**: Open Graph and Twitter Cards
- **Schema**: JSON-LD structured data
- **Sitemap**: XML sitemap generation
- **Robots**: robots.txt editor
- **Keyword Suggest**: Google Keyword Planner
- **AI Generator**: OpenAI content generation
- **Bulk Audit**: Mass optimization tools
- **Internal Link**: Automatic internal linking

## Hooks and Filters

### Available Filters

- `kseo_meta_data` - Modify generated meta data
- `kseo_schema_data` - Modify schema markup
- `kseo_sitemap_items` - Modify sitemap entries
- `kseo_keyword_suggestions` - Modify keyword suggestions

### Available Actions

- `kseo_meta_generated` - Fired when meta is generated
- `kseo_schema_generated` - Fired when schema is generated
- `kseo_sitemap_generated` - Fired when sitemap is generated

## Performance

- Meta tags are cached for 30 minutes using transients
- Sitemap is cached and regenerated on post updates
- Schema markup is cached per post
- AI API calls are rate-limited and cached

## Security & Compliance

- Nonces for AJAX, capability checks on all admin actions
- Prepared statements for DB, escaping output in views
- API: Bearer tokens (no query string), RBAC, rate limiting
- Secrets: Envelope encryption at rest (XChaCha20-Poly1305 if available). Store keys as constants: `KSEO_ACTIVE_KEY_ID`, `KSEO_APP_KEY_{ID}` (base64). Options are stored with `enc:` prefix and decrypted only at use time.
- Key rotation: Set new `KSEO_APP_KEY_{NEW}` and switch `KSEO_ACTIVE_KEY_ID` to the new id; keep old keys defined for decrypt; re-encrypt on next save.

## Troubleshooting

### Common Issues

1. **Plugin won't activate**: Check WordPress and PHP version requirements
2. **AI generation not working**: Verify OpenAI API key is correct
3. **Meta tags not showing**: Check if Meta Output module is enabled
4. **Caching issues**: Clear transients via WP-CLI or deactivate/reactivate

### Debug Mode

Enable debug mode in Settings → Advanced to see detailed error messages.

## Support

- GitHub Issues: [Report bugs](https://github.com/portrara/kel-seobooster/issues)
- Documentation: [Read the docs](https://github.com/portrara/kel-seobooster/wiki)
- Support Email: support@keseobooster.com

## Changelog

### Version 2.0.0
- Complete rewrite with modular architecture
- PSR-4 autoloading
- OpenAI integration
- Google Keyword Planner
- Bulk audit tools
- WP-CLI support
- Comprehensive caching
- Security improvements

## License

GPL v2 or later

## Credits

Developed by Krish Yadav 