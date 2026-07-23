# hirasso/php-autolinker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/autolinker?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/hirasso/autolinker)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/php-autolinker/ci.yml?style=flat-square&logo=github&label=tests)](https://github.com/hirasso/php-autolinker/actions/workflows/ci.yml)
[![Code Coverage](https://img.shields.io/codecov/c/github/hirasso/php-autolinker?style=flat-square&logo=codecov&logoColor=white&label=coverage%20%28whatever%20that%20entails%29)](https://app.codecov.io/gh/hirasso/php-autolinker)

**Automatically link URLs and email addresses in a given block of text/HTML 🖇️**

Framework-agnostic: pass an HTML fragment, get an HTML fragment back with plain
urls and email addresses turned into links. Wire it into your CMS / framework
yourself (e.g. a WordPress `acf/format_value` filter).

## Requirements

- PHP 8.4+ (uses the new `Dom\HTMLDocument` API)

## Installation

```bash
composer require hirasso/autolinker
```

## Usage

Pass a string, get a string back:

```php
use Hirasso\Autolinker\Autolinker;

echo Autolinker::link('Visit https://example.com or mail me@example.com');
// Visit <a href="https://example.com">example.com</a>
// or mail <a href="mailto:me@example.com">me@example.com</a>
```

`Autolinker::link()` accepts an HTML fragment, a full (or partial) HTML document
or an existing `Dom\HTMLDocument`. Only text is linked. Elements where autolinking doesn't make sense (like e.g. `button, script, style, svg, noscript, title, textarea, select, pre, code, etc...`),
are skipped.

If you pass a full or partial document, only the body's inner HTML is linked;
the doctype, `<head>`, body attributes, comments and anything wrapping the body
are preserved verbatim:

```php
echo Autolinker::link(
    '<!doctype html><html><head><title>t</title></head>'
    . '<body class="x">Visit https://example.com</body></html>'
);
// <!doctype html><html><head><title>t</title></head>
// <body class="x">Visit <a href="https://example.com">example.com</a></body></html>
```

### What gets linked

- `http://` and `https://` urls
- Schemeless `www.` urls (the href is prefixed with `https://`)
- Email addresses (turned into `mailto:` links)

Trailing sentence punctuation stays out of the link:

```php
echo Autolinker::link('See https://example.com.');
// See <a href="https://example.com">example.com</a>.
```

### Options

Pass an `AutolinkerOptions` object as the second argument to configure the output:

```php
use Dom\HTMLElement;
use Hirasso\Autolinker\Autolinker;
use Hirasso\Autolinker\AutolinkerOptions;

echo Autolinker::link($html, new AutolinkerOptions(
    urls: true,          // link urls (default: true)
    emails: true,        // link emails (default: true)
    stripScheme: true,   // strip the scheme from link texts (default: true)
    truncateText: 50,    // truncate link texts, ellipsis included; 0 disables (default: 50)
    postProcess: function (HTMLElement $a) {
        // called for each anchor this library creates
        $a->setAttribute('rel', 'noopener');
    },
));
```

`AutolinkerOptions` is immutable and every option is optional, so you only need
to set the ones you want to change. `stripScheme` only affects the visible text,
never the `href`:

```php
echo Autolinker::link('https://example.com/path');
// <a href="https://example.com/path">example.com/path</a>

echo Autolinker::link('https://example.com/path', new AutolinkerOptions(stripScheme: false));
// <a href="https://example.com/path">https://example.com/path</a>
```

Use `postProcess` to decorate the generated anchors — e.g. mark external links,
add classes or `target` / `rel` attributes. It receives every anchor this
library creates and never touches links that were already in your HTML.

### Working with a `Dom\HTMLDocument`

If you already have a document, pass it directly. It is modified by reference and
returned as-is (rather than serialized back to a string):

```php
use Dom\HTMLDocument;
use Hirasso\Autolinker\Autolinker;

$doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
Autolinker::link($doc); // $doc is now mutated in place
```

## License

MIT © [Rasso Hilber](https://rassohilber.com)
