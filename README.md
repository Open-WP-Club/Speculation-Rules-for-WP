# Speculation Rules for WP

## Description

Speculation Rules for WP is a WordPress plugin that adds support for the Speculation Rules API. This API allows for dynamic prefetching or prerendering of URLs based on user interaction, potentially improving the perceived loading speed of your website.

## Features

- Choose between prefetch and prerender modes
- Set eagerness levels for speculation (conservative, moderate, eager)
- Specify URLs to include or exclude from speculation
- Apply rules selectively to different post types
- Debug mode for troubleshooting
- Performance optimization through caching

## Installation

1. Upload the `speculation-rules-for-wp` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Speculation Rules for WP to configure the plugin

## Usage

### Basic Configuration

1. **Type**: Choose between 'Prefetch' (load only the page) or 'Prerender' (fully load the page and all subresources).
2. **Eagerness**: Select how aggressively the browser should apply the speculation rules:
   - Conservative: Typically on click
   - Moderate: Typically on hover
   - Eager: On the slightest suggestion
3. **Match URLs**: Enter the URLs you want to apply the speculation rules to, one per line. You can use wildcards, e.g., `/products/*`.
4. **Exclude URLs**: Enter any URLs you want to exclude from the speculation rules, one per line.
5. **Apply to Post Types**: Select which post types should have the speculation rules applied.
6. **Debug Mode**: Toggle this on to see additional debugging information in your page source.

### Advanced Usage

- Use wildcards in your URL patterns for broader matching, e.g., `/*` to match all pages.
- Combine include and exclude rules to create sophisticated patterns, e.g., include `/products/*` but exclude `/products/out-of-stock/*`.
- Use the debug mode to understand how the rules are being applied and troubleshoot any issues.

## Browser Compatibility

The Speculation Rules API is a relatively new feature and may not be supported by all browsers. As of the last update of this plugin:

- Chrome/Chromium-based browsers (version 113 and later) support the Speculation Rules API.
- Firefox, Safari, and other browsers may not support this feature.

It's important to note that the use of this plugin will not negatively affect browsers that don't support the Speculation Rules API - they will simply ignore the rules.

For the most up-to-date browser compatibility information, please check [Can I use](https://caniuse.com/?search=Speculation%20Rules).

## Troubleshooting

If you're having issues with the plugin:

1. Enable the Debug Mode in the plugin settings.
2. Check your browser's console for any error messages.
3. Verify that your browser supports the Speculation Rules API.
4. Ensure that your URL patterns are correctly formatted.

If problems persist, please contact the plugin author or open an issue on the plugin's GitHub repository.

## Support

For support, feature requests, or bug reports, please use the plugin's GitHub repository or contact the author directly.

## License

This plugin is licensed under the GPL v2 or later.

---

Thank you for using Speculation Rules for WP!
