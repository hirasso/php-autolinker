<?php

declare(strict_types=1);

use Dom\HTMLElement;
use Hirasso\Autolink\Options;
use Hirasso\Autolink\Processor;

use function Hirasso\Autolink\autolink;

/**
 * Render a fragment through Autolink with the default options
 */
function render(string $html): string
{
    return (string) autolink($html);
}

/**
 * Render a fragment through the Processor with custom options
 */
function renderWith(string $html, Options $options): string
{
    $doc = Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR);
    (new Processor($options))->run($doc);

    return $doc->body->innerHTML;
}

test('links a plain https url', function () {
    expect(render('Visit https://example.com now'))
        ->toBe('Visit <a href="https://example.com">example.com</a> now');
});

test('links a plain http url', function () {
    expect(render('See http://example.com'))
        ->toBe('See <a href="http://example.com">example.com</a>');
});

test('links a www url and prepends https to the href', function () {
    expect(render('Go to www.example.com'))
        ->toBe('Go to <a href="https://www.example.com">www.example.com</a>');
});

test('keeps the path in the visible text', function () {
    expect(render('https://example.com/foo/bar'))
        ->toBe('<a href="https://example.com/foo/bar">example.com/foo/bar</a>');
});

test('strips a lone trailing slash from the visible text', function () {
    expect(render('https://example.com/'))
        ->toBe('<a href="https://example.com/">example.com</a>');
});

test('does not strip the scheme when stripScheme is disabled', function () {
    expect(renderWith('https://example.com', new Options(stripScheme: false)))
        ->toBe('<a href="https://example.com">https://example.com</a>');
});

test('links an email as a mailto anchor', function () {
    expect(render('Mail me@example.com please'))
        ->toBe('Mail <a href="mailto:me@example.com">me@example.com</a> please');
});

test('links multiple items in one string', function () {
    expect(render('a http://a.com b foo@b.com c'))
        ->toBe('a <a href="http://a.com">a.com</a> b <a href="mailto:foo@b.com">foo@b.com</a> c');
});

test('trims trailing punctuation off a url', function () {
    expect(render('See https://example.com.'))
        ->toBe('See <a href="https://example.com">example.com</a>.');
});

test('trims trailing punctuation off an email', function () {
    expect(render('Write me@example.com.'))
        ->toBe('Write <a href="mailto:me@example.com">me@example.com</a>.');
});

test('keeps balanced parens but trims an unbalanced closing paren', function () {
    expect(render('(see https://en.wikipedia.org/wiki/Foo_(bar))'))
        ->toBe('(see <a href="https://en.wikipedia.org/wiki/Foo_(bar)">en.wikipedia.org/wiki/Foo_(bar)</a>)');
});

test('does not autolink inside an existing anchor', function () {
    $html = '<a href="/x">visit https://example.com</a>';

    expect(render($html))->toBe($html);
});

test('does not autolink inside ignored elements', function () {
    $html = '<code>https://example.com</code>';

    expect(render($html))->toBe($html);
});

test('escapes surrounding text', function () {
    expect(render('a & b https://example.com'))
        ->toBe('a &amp; b <a href="https://example.com">example.com</a>');
});

test('leaves text without urls or emails untouched', function () {
    expect(render('just some prose'))->toBe('just some prose');
});

test('does not link urls when disabled', function () {
    expect(renderWith('https://example.com', new Options(urls: false)))
        ->toBe('https://example.com');
});

test('does not link emails when disabled', function () {
    expect(renderWith('me@example.com', new Options(emails: false)))
        ->toBe('me@example.com');
});

test('the fluent urls(false) toggle disables url linking', function () {
    expect((string) autolink('https://example.com foo@bar.com')->urls(false))
        ->toBe('https://example.com <a href="mailto:foo@bar.com">foo@bar.com</a>');
});

test('the fluent emails(false) toggle disables email linking', function () {
    expect((string) autolink('https://example.com foo@bar.com')->emails(false))
        ->toBe('<a href="https://example.com">example.com</a> foo@bar.com');
});

test('the fluent stripScheme(false) toggle keeps the scheme in the text', function () {
    expect((string) autolink('https://example.com')->stripScheme(false))
        ->toBe('<a href="https://example.com">https://example.com</a>');
});

test('the fluent truncateText toggle sets the max text length', function () {
    expect((string) autolink('https://example.com/abcdefghij')->truncateText(15))
        ->toBe('<a href="https://example.com/abcdefghij">example.com/ab…</a>');
});

test('the fluent postProcess toggle runs on created anchors', function () {
    $html = (string) autolink('https://example.com')
        ->postProcess(fn (HTMLElement $a) => $a->setAttribute('rel', 'nofollow'));

    expect($html)->toBe('<a href="https://example.com" rel="nofollow">example.com</a>');
});

test('does not truncate when truncateText is 0', function () {
    $long = 'https://example.com/' . str_repeat('a', 100);

    expect(renderWith($long, new Options(truncateText: 0)))
        ->toBe(sprintf('<a href="%s">%s</a>', $long, substr($long, strlen('https://'))));
});

test('truncates long link text with an ellipsis', function () {
    $long = 'https://example.com/' . str_repeat('a', 100);
    $html = render($long);

    // 50 char limit: 49 chars + ellipsis
    expect($html)->toContain('…</a>')
        ->and($html)->toContain('href="' . $long . '"');
});

test('applies the postProcess callback to created anchors', function () {
    $doc = Dom\HTMLDocument::createFromString('<p>https://example.com</p>', LIBXML_NOERROR);

    $options = new Options(postProcess: function (HTMLElement $a) {
        $a->setAttribute('class', 'external');
    });

    (new Hirasso\Autolink\Processor($options))->run($doc);

    expect($doc->body->innerHTML)
        ->toBe('<p><a href="https://example.com" class="external">example.com</a></p>');
});
