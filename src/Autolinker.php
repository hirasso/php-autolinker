<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

use Dom\HTMLDocument;
use InvalidArgumentException;

/**
 * Automatically link URLs and email addresses in a given block of text/HTML.
 *
 * Framework-agnostic: pass a string, get a string back. Wire it into your
 * CMS / framework yourself (e.g. a WordPress `acf/format_value` filter).
 */
final class Autolinker
{
    /**
     * Autolink urls and email addresses in an HTML fragment or `Dom\HTMLDocument`.
     *
     * A string returns the linked HTML. A `Dom\HTMLDocument` is modified by
     * reference and returns the same document.
     *
     * @return ($source is string ? string : HTMLDocument)
     */
    public static function link(
        string|HTMLDocument $source,
        ?AutolinkerOptions $options = null,
    ): string|HTMLDocument {
        $document = is_string($source)
            ? HTMLDocument::createFromString(self::parseSource($source), LIBXML_NOERROR)
            : $source;

        $processor = new Processor($options ?? new AutolinkerOptions());

        $processor->run($document);

        return is_string($source) ? $document->body->innerHTML ?? '' : $source;
    }

    /**
     * Guard against full HTML documents: Autolinker should only run against HTML fragments
     */
    private static function parseSource(string $source): string
    {
        if (preg_match('/<!doctype[\s>]|<(?:html|head|body)[\s>]/i', $source) === 1) {
            throw new InvalidArgumentException(
                'Autolinker expects a html fragment, not a full document '
                . '(<!doctype>, <html>, <head> or <body> found).'
            );
        }
        return $source;
    }
}
