# hirasso/php-autolinker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/autolinker?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/hirasso/autolinker)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/php-autolinker/ci.yml?style=flat-square&logo=github&label=tests)](https://github.com/hirasso/php-autolinker/actions/workflows/ci.yml)
[![Code Coverage](https://img.shields.io/codecov/c/github/hirasso/php-autolinker?style=flat-square&logo=codecov&logoColor=white&label=coverage%20%28whatever%20that%20entails%29)](https://app.codecov.io/gh/hirasso/php-autolinker)

**Autolink urls and email addresses in your HTML 🐘**

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

Pass a string, cast the result back to a string:

```php
use function Hirasso\Autolinker\autolink;

echo autolink('Visit https://example.com or mail me@example.com');
// Visit <a href="https://example.com">example.com</a>
// or mail <a href="mailto:me@example.com">me@example.com</a>
```

`autolink()` accepts an HTML fragment (not a full document) or an existing
`Dom\HTMLDocument`. Only text is linked — content inside existing links and
inside `head, script, style, svg, noscript, title, textarea, select, iframe,
canvas, pre, code` is left untouched, and no nested anchors are created.

### What gets linked

- `http://` and `https://` urls
- Schemeless `www.` urls (the href is prefixed with `https://`)
- Email addresses (turned into `mailto:` links)

Trailing sentence punctuation stays out of the link:

```php
echo autolink('See https://example.com.');
// See <a href="https://example.com">example.com</a>.
```

### Options

Configure the output with a fluent API. All methods return `$this`, so they
chain in any order:

```php
use Dom\HTMLElement;
use function Hirasso\Autolinker\autolink;

echo autolink($html)
    ->urls(true)                 // link urls (default: true)
    ->emails(true)               // link emails (default: true)
    ->stripScheme(true)          // strip the scheme from link texts (default: true)
    ->truncateText(50)           // truncate link texts, ellipsis included; 0 disables (default: 50)
    ->postProcess(function (HTMLElement $a) {
        // called for each anchor this library creates
        $a->setAttribute('rel', 'noopener');
    });
```

`stripScheme` only affects the visible text, never the `href`:

```php
echo autolink('https://example.com/path');
// <a href="https://example.com/path">example.com/path</a>

echo autolink('https://example.com/path')->stripScheme(false);
// <a href="https://example.com/path">https://example.com/path</a>
```

Use `postProcess` to decorate the generated anchors — e.g. mark external links,
add classes or `target` / `rel` attributes. It receives every anchor this
library creates and never touches links that were already in your HTML.

### Working with a `Dom\HTMLDocument`

If you already have a document, pass it directly. It is modified by reference:

```php
use Dom\HTMLDocument;
use function Hirasso\Autolinker\autolink;

$doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
autolink($doc)->process();
```

## License

MIT © [Rasso Hilber](https://rassohilber.com)
