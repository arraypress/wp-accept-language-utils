# Accept-Language

Work out which language a visitor would prefer, from the header their browser
already sends.

## What it does

Every request carries an `Accept-Language` header saying which languages the
visitor reads and how strongly they prefer each one. It is a small grammar
with quality values, regional variants and a wildcard, and parsing it by hand
usually means splitting on commas and hoping.

This parses it properly and answers the question you actually have: of the
languages this site has, which should this person get?

## Features

* Pick the best match from the languages you offer, with a fallback
* Get the visitor's primary language and region separately
* Ask whether a specific language is accepted, exactly or by base language
* Read the quality value behind a preference, when ranking matters
* Tell whether the preferred language is right-to-left, before rendering
* Normalise a language code, so `en_GB`, `en-gb` and `EN-GB` agree

## Installation

```bash
composer require arraypress/wp-accept-language-utils
```

## Quick start

Redirect a visitor to the version of the site they can read:

```php
$locale = get_preferred_language( [ 'en', 'fr', 'de' ], 'en' );

if ( 'fr' === $locale ) {
	// ...
}
```

And for a layout that has to flip:

```php
if ( is_rtl_language() ) {
	// ...
}
```

## What it does not do

The header is a preference, not an instruction. Somebody browsing from a
borrowed laptop gets its owner's languages — so use this to choose a default,
and let people override it.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
