<?php

declare(strict_types=1);

use Dom\HTMLElement;
use Hirasso\Autolinker\Autolinker;
use Hirasso\Autolinker\AutolinkerOptions;

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
    expect(Autolinker::link('https://example.com', new AutolinkerOptions(stripScheme: false)))
        ->toBe('<a href="https://example.com">https://example.com</a>');
});

test('links an email as a mailto anchor', function () {
    expect(render('Mail me@example.com please'))
        ->toBe('Mail <a href="mailto:me@example.com">me@example.com</a> please');
});

test('links an email that contains dots', function () {
    expect(render('Mail me.myself.andi@foo.example.com please'))
        ->toBe('Mail <a href="mailto:me.myself.andi@foo.example.com">me.myself.andi@foo.example.com</a> please');
});

test('links an email that contains +', function () {
    expect(render('Mail me+spam@example.com please'))
        ->toBe('Mail <a href="mailto:me+spam@example.com">me+spam@example.com</a> please');
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
    expect(Autolinker::link('https://example.com', new AutolinkerOptions(urls: false)))
        ->toBe('https://example.com');
});

test('does not link emails when disabled', function () {
    expect(Autolinker::link('me@example.com', new AutolinkerOptions(emails: false)))
        ->toBe('me@example.com');
});

test('urls: false disables url linking but keeps emails', function () {
    expect(Autolinker::link('https://example.com foo@bar.com', new AutolinkerOptions(urls: false)))
        ->toBe('https://example.com <a href="mailto:foo@bar.com">foo@bar.com</a>');
});

test('emails: false disables email linking but keeps urls', function () {
    expect(Autolinker::link('https://example.com foo@bar.com', new AutolinkerOptions(emails: false)))
        ->toBe('<a href="https://example.com">example.com</a> foo@bar.com');
});

test('stripScheme: false keeps the scheme in the text', function () {
    expect(Autolinker::link('https://example.com', new AutolinkerOptions(stripScheme: false)))
        ->toBe('<a href="https://example.com">https://example.com</a>');
});

test('truncateText sets the max text length', function () {
    expect(Autolinker::link('https://example.com/abcdefghij', new AutolinkerOptions(truncateText: 15)))
        ->toBe('<a href="https://example.com/abcdefghij">example.com/ab…</a>');
});

test('postProcess runs on created anchors', function () {
    $html = Autolinker::link(
        'https://example.com',
        new AutolinkerOptions(
            postProcess: fn (HTMLElement $a) => $a->setAttribute('rel', 'nofollow'),
        ),
    );

    expect($html)->toBe('<a href="https://example.com" rel="nofollow">example.com</a>');
});

test('does not truncate when truncateText is 0', function () {
    $long = 'https://example.com/' . str_repeat('a', 100);

    expect(Autolinker::link($long, new AutolinkerOptions(truncateText: 0)))
        ->toBe(sprintf('<a href="%s">%s</a>', $long, substr($long, strlen('https://'))));
});

test('truncates long link text with an ellipsis', function () {
    $long = 'https://example.com/' . str_repeat('a', 100);
    $html = render($long);

    // 50 char limit: 49 chars + ellipsis
    expect($html)->toContain('…</a>')
        ->and($html)->toContain('href="' . $long . '"');
});

test('a Dom\HTMLDocument is mutated by reference and returned', function () {
    $doc = Dom\HTMLDocument::createFromString('<p>https://example.com</p>', LIBXML_NOERROR);

    $result = Autolinker::link($doc, new AutolinkerOptions(postProcess: function (HTMLElement $a) {
        $a->setAttribute('class', 'external');
    }));

    expect($result)->toBe($doc)
        ->and($doc->body->innerHTML)
        ->toBe('<p><a href="https://example.com" class="external">example.com</a></p>');
});

test('rejects a full HTML document', function () {
    expect(fn () => Autolinker::link('<!doctype html><p>https://example.com</p>'))
        ->toThrow(InvalidArgumentException::class);
});
