<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

use Dom\HTMLDocument;
use Dom\HTMLElement;

/**
 * Automatically link URLs and email addresses in a given block of text/HTML.
 *
 * Framework-agnostic: pass a string, get a string back. Wire it into your
 * CMS / framework yourself (e.g. a WordPress `acf/format_value` filter).
 */
final class Autolinker
{
    /**
     * Autolink urls and email addresses in an HTML fragment, document or `Dom\HTMLDocument`.
     *
     * A string returns the linked HTML. A `Dom\HTMLDocument` is modified by
     * reference and returns the same document.
     *
     * @param ?callable(HTMLElement): mixed $postProcess post-process each created anchor
     * @return ($source is string ? string : HTMLDocument)
     */
    public static function link(
        string|HTMLDocument $source,
        bool $urls = true,
        bool $emails = true,
        bool $stripScheme = true,
        int $truncateText = 50,
        ?callable $postProcess = null,
    ): string|HTMLDocument {
        $document = is_string($source)
            ? HTMLDocument::createFromString($source, LIBXML_NOERROR)
            : $source;

        $processor = new Processor(
            urls: $urls,
            emails: $emails,
            stripScheme: $stripScheme,
            truncateText: $truncateText,
            postProcess: $postProcess,
        );

        $processor->run($document);

        if ($source instanceof HTMLDocument) {
            return $document;
        }

        [$before, $after] = self::getBeforeAndAfter($source);

        return $before . ($document->body->innerHTML ?? '') . $after;
    }

    /**
     * Get the strings before and after the body's content, so that only the
     * body gets re-serialized and everything around it is preserved verbatim.
     * Falls back to `</head>`, `<html>` or the doctype if there is no `<body>`.
     *
     * @return array{0: string, 1: string}
     */
    private static function getBeforeAndAfter(string $source): array
    {
        /** blank out comments and raw text elements, so tags inside them aren't matched */
        $masked = preg_replace_callback(
            '/<!--.*?(?:-->|$)|<(script|style|title|textarea)\b.*?(?:<\/\1\s*>|$)/is',
            fn ($match) => str_repeat(' ', strlen($match[0])),
            $source
        ) ?? $source;

        $start = self::getOffsetAfter($masked, '/<body(?=[\s\/>])[^>]*>/i')
            ?? self::getOffsetAfter($masked, '/<\/head\s*>/i')
            ?? self::getOffsetAfter($masked, '/<html(?=[\s\/>])[^>]*>/i')
            ?? self::getOffsetAfter($masked, '/<!doctype[^>]*>/i')
            ?? 0;

        $end = self::getOffset($masked, '/<\/body\s*>/i', $start)
            ?? self::getOffset($masked, '/<\/html\s*>/i', $start)
            ?? strlen($source);

        return [substr($source, 0, $start), substr($source, $end)];
    }

    /**
     * Get the offset of the first match of $pattern, starting at $offset
     */
    private static function getOffset(string $subject, string $pattern, int $offset = 0): ?int
    {
        return preg_match($pattern, $subject, $match, PREG_OFFSET_CAPTURE, $offset) === 1
            ? $match[0][1]
            : null;
    }

    /**
     * Get the offset right after the first match of $pattern
     */
    private static function getOffsetAfter(string $subject, string $pattern): ?int
    {
        return preg_match($pattern, $subject, $match, PREG_OFFSET_CAPTURE) === 1
            ? $match[0][1] + strlen($match[0][0])
            : null;
    }
}
