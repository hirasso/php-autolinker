<?php

declare(strict_types=1);

use Dom\HTMLElement;
use Hirasso\Autolinker\Autolinker;

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
    expect(Autolinker::link('https://example.com', stripScheme: false))
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
    expect(Autolinker::link('https://example.com', urls: false))
        ->toBe('https://example.com');
});

test('does not link emails when disabled', function () {
    expect(Autolinker::link('me@example.com', emails: false))
        ->toBe('me@example.com');
});

test('urls: false disables url linking but keeps emails', function () {
    expect(Autolinker::link('https://example.com foo@bar.com', urls: false))
        ->toBe('https://example.com <a href="mailto:foo@bar.com">foo@bar.com</a>');
});

test('emails: false disables email linking but keeps urls', function () {
    expect(Autolinker::link('https://example.com foo@bar.com', emails: false))
        ->toBe('<a href="https://example.com">example.com</a> foo@bar.com');
});

test('stripScheme: false keeps the scheme in the text', function () {
    expect(Autolinker::link('https://example.com', stripScheme: false))
        ->toBe('<a href="https://example.com">https://example.com</a>');
});

test('truncateText sets the max text length', function () {
    expect(Autolinker::link('https://example.com/abcdefghij', truncateText: 15))
        ->toBe('<a href="https://example.com/abcdefghij">example.com/ab…</a>');
});

test('postProcess runs on created anchors', function () {
    $html = Autolinker::link(
        'https://example.com',
        postProcess: fn (HTMLElement $a) => $a->setAttribute('rel', 'nofollow'),
    );

    expect($html)->toBe('<a href="https://example.com" rel="nofollow">example.com</a>');
});

test('does not truncate when truncateText is 0', function () {
    $long = 'https://example.com/' . str_repeat('a', 100);

    expect(Autolinker::link($long, truncateText: 0))
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

    $result = Autolinker::link($doc, postProcess: function (HTMLElement $a) {
        $a->setAttribute('class', 'external');
    });

    expect($result)->toBe($doc)
        ->and($doc->body->innerHTML)
        ->toBe('<p><a href="https://example.com" class="external">example.com</a></p>');
});

test('round-trips a body-wrapped fragment, preserving the body tag', function () {
    expect(render('<body>Visit https://example.com</body>'))
        ->toBe('<body>Visit <a href="https://example.com">example.com</a></body>');
});

test('preserves body attributes when round-tripping', function () {
    expect(render('<body class="x">Visit https://example.com</body>'))
        ->toBe('<body class="x">Visit <a href="https://example.com">example.com</a></body>');
});

test('preserves the head and wrapper of a full document, linking only the body', function () {
    $doc = '<html><head><title>t</title></head><body>Visit https://example.com</body></html>';

    expect(render($doc))
        ->toBe('<html><head><title>t</title></head><body>Visit <a href="https://example.com">example.com</a></body></html>');
});

test('preserves HTML comments outside the body', function () {
    $doc = '<!-- top --><html><head><!-- meta --></head>'
        . '<body>Visit https://example.com</body></html><!-- bottom -->';

    expect(render($doc))
        ->toBe('<!-- top --><html><head><!-- meta --></head>'
            . '<body>Visit <a href="https://example.com">example.com</a></body></html><!-- bottom -->');
});

test('preserves a multi-line document byte for byte', function () {
    expect(render("<!doctype html>\n<html>\n<head></head>\n<body>\nVisit https://example.com\n</body>\n</html>\n"))
        ->toBe("<!doctype html>\n<html>\n<head></head>\n<body>\nVisit <a href=\"https://example.com\">example.com</a>\n</body>\n</html>\n");
});

test('preserves whitespace around and inside the body verbatim', function (string $html) {
    expect(render($html))->toBe($html);
})->with([
    'whitespace only' => ['   '],
    'fragment' => [' x '],
    'after doctype' => ["<!doctype html>\n<p>a</p>"],
    'after head' => ["<head></head>\n<p>a</p>"],
    'after body' => ["<body>x</body>\n"],
]);

test('preserves the head of a document without a closing body tag', function () {
    expect(render('<html><head><title>t</title></head><body>https://a.com'))
        ->toBe('<html><head><title>t</title></head><body><a href="https://a.com">a.com</a>');
});

test('preserves the head of a document without a body tag', function () {
    expect(render('<html><head><title>t</title></head>https://a.com</html>'))
        ->toBe('<html><head><title>t</title></head><a href="https://a.com">a.com</a></html>');
});

test('preserves a lone doctype', function () {
    expect(render('<!doctype html>https://a.com'))
        ->toBe('<!doctype html><a href="https://a.com">a.com</a>');
});

test('does not mistake elements starting with "body" for the body', function () {
    expect(render('<body><body-text>https://a.com</body-text></body>'))
        ->toBe('<body><body-text><a href="https://a.com">a.com</a></body-text></body>');
});

test('ignores body tags inside comments and raw text elements', function () {
    $doc = '<html><head><title><body></title><script>"<body>"</script></head>'
        . '<body>https://a.com</body><!-- </body> --></html>';

    expect(render($doc))
        ->toBe('<html><head><title><body></title><script>"<body>"</script></head>'
            . '<body><a href="https://a.com">a.com</a></body><!-- </body> --></html>');
});

test('does not autolink inside buttons and inline code elements', function (string $tag) {
    $html = "<$tag>https://example.com</$tag>";

    expect(render($html))->toBe($html);
})->with(['button', 'kbd', 'samp', 'var']);
