# WordPress Accept-Language Utilities

A lean WordPress utility for parsing and working with HTTP Accept-Language headers. Built for multilingual sites,
content negotiation, and language-based conditional functionality.

## Features

* 🌍 **Language Detection** - Parse Accept-Language header with quality values
* 🎯 **Best Match** - Find the best language from your available translations
* 🔄 **RTL Detection** - Identify right-to-left language preferences
* 📊 **Quality Values** - Access preference weights for each language
* 🛠️ **Normalization** - Consistent locale formatting (en-US, de-AT)

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later

## Installation

```bash
composer require arraypress/wp-accept-language-utils
```

## Usage

### Basic Language Detection

```php
use ArrayPress\AcceptLanguageUtils\AcceptLanguage;

// Get the user's primary preferred language
$primary = AcceptLanguage::get_primary();
// Returns: "en-US", "de", "fr-CA", etc.

// Get just the language code (without region)
$language = AcceptLanguage::get_primary_language();
// Returns: "en", "de", "fr"

// Get the region code
$region = AcceptLanguage::get_primary_region();
// Returns: "US", "CA", "GB", or null
```

### Finding Best Match

```php
// Your site's available languages
$available = ['en', 'de', 'fr', 'es'];

// Find the best match for the user
$best = AcceptLanguage::get_best_match( $available, 'en' );
// Returns best match or 'en' as default

// WordPress integration
$locale = AcceptLanguage::get_best_match( 
    get_available_languages(), 
    'en_US' 
);
```

### Checking Language Acceptance

```php
// Check if user accepts a language
if ( AcceptLanguage::accepts( 'de' ) ) {
    // Show German content option
}

// Exact match only (e.g., en-US vs en-GB)
if ( AcceptLanguage::accepts( 'en-US', true ) ) {
    // US English specifically accepted
}

// Get the quality/preference value (0.0 to 1.0)
$quality = AcceptLanguage::get_quality( 'fr' );
// Returns: 0.8, 1.0, etc. or null
```

### RTL Detection

```php
// Check if user prefers RTL language
if ( AcceptLanguage::is_rtl() ) {
    wp_enqueue_style( 'theme-rtl', 'css/rtl.css' );
}
```

### Parsing the Full Header

```php
// Get all languages with quality values
$languages = AcceptLanguage::parse();
// Returns: ['en-US' => 1.0, 'en' => 0.9, 'de' => 0.8]

// Get all language codes (without quality values)
$all = AcceptLanguage::get_all();
// Returns: ['en-US', 'en', 'de']

// Get unique base languages only
$unique = AcceptLanguage::get_languages();
// Returns: ['en', 'de'] (no duplicates)
```

### Utility Functions

```php
// Normalize a language code
$normalized = AcceptLanguage::normalize( 'EN-us' );
// Returns: "en-US"

// Extract language from locale
$lang = AcceptLanguage::extract_language( 'en-US' );
// Returns: "en"

// Extract region from locale
$region = AcceptLanguage::extract_region( 'en-US' );
// Returns: "US"
```

## Common Use Cases

### Multilingual Content Switching

```php
function get_user_content_language() {
    $available = [ 'en', 'de', 'fr', 'es', 'ja' ];
    
    // Check for user preference cookie first
    if ( isset( $_COOKIE['preferred_lang'] ) ) {
        return $_COOKIE['preferred_lang'];
    }
    
    // Fall back to Accept-Language header
    return AcceptLanguage::get_best_match( $available, 'en' );
}
```

### Conditional Asset Loading

```php
function enqueue_language_assets() {
    // Load RTL stylesheet if needed
    if ( AcceptLanguage::is_rtl() ) {
        wp_enqueue_style( 'theme-rtl', get_template_directory_uri() . '/rtl.css' );
    }
    
    // Load language-specific scripts
    $lang = AcceptLanguage::get_primary_language();
    $script = "js/i18n/{$lang}.js";
    
    if ( file_exists( get_template_directory() . '/' . $script ) ) {
        wp_enqueue_script( 'theme-i18n', get_template_directory_uri() . '/' . $script );
    }
}
add_action( 'wp_enqueue_scripts', 'enqueue_language_assets' );
```

### E-commerce Localization

```php
function set_store_locale() {
    $available = [
        'en-US' => 'USD',
        'en-GB' => 'GBP', 
        'de-DE' => 'EUR',
        'ja-JP' => 'JPY',
    ];
    
    $locale = AcceptLanguage::get_best_match( array_keys( $available ), 'en-US' );
    
    return [
        'locale'   => $locale,
        'currency' => $available[ $locale ],
    ];
}
```

### Language Preference Logging

```php
function log_visitor_language( $order_id ) {
    $order = wc_get_order( $order_id );
    
    $order->update_meta_data( 'browser_language', AcceptLanguage::get_primary() );
    $order->update_meta_data( 'browser_languages', implode( ', ', AcceptLanguage::get_all() ) );
    $order->save();
}
add_action( 'woocommerce_new_order', 'log_visitor_language' );
```

## Helper Functions

Global functions are available for common operations:

```php
// Get primary language
$lang = get_accept_language();

// Find best match from available
$best = get_preferred_language( ['en', 'de', 'fr'], 'en' );

// Check if language is accepted
if ( accepts_language( 'de' ) ) {
    // ...
}

// Check RTL preference
if ( is_rtl_language() ) {
    // ...
}
```

## API Reference

| Method                                   | Description                        | Returns   |
|------------------------------------------|------------------------------------|-----------|
| `get_header()`                           | Get raw Accept-Language header     | `?string` |
| `get_primary()`                          | Get preferred language code        | `?string` |
| `get_primary_language()`                 | Get language without region        | `?string` |
| `get_primary_region()`                   | Get region code only               | `?string` |
| `parse()`                                | Parse header to array with quality | `array`   |
| `get_all()`                              | Get all language codes             | `array`   |
| `get_languages()`                        | Get unique base languages          | `array`   |
| `accepts( $lang, $exact )`               | Check if language accepted         | `bool`    |
| `get_quality( $lang )`                   | Get quality value for language     | `?float`  |
| `get_best_match( $available, $default )` | Find best match                    | `?string` |
| `is_rtl()`                               | Check if primary is RTL            | `bool`    |
| `normalize( $code )`                     | Normalize language code            | `string`  |
| `extract_language( $locale )`            | Extract language from locale       | `string`  |
| `extract_region( $locale )`              | Extract region from locale         | `?string` |
| `get_common_languages( $as_options )`    | Get language list for dropdowns    | `array`   |

## Supported RTL Languages

Arabic, Hebrew, Persian/Farsi, Urdu, Yiddish, Pashto, Sindhi, Uyghur, Kurdish (Sorani), Divehi

## License

GPL-2.0-or-later

## Support

- [Documentation](https://github.com/arraypress/wp-accept-language-utils)
- [Issue Tracker](https://github.com/arraypress/wp-accept-language-utils/issues)