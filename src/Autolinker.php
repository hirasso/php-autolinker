<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

use Dom\HTMLDocument;

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
            ? HTMLDocument::createFromString($source, LIBXML_NOERROR)
            : $source;

        $processor = new Processor($options ?? new AutolinkerOptions());

        $processor->run($document);

        if ($source instanceof HTMLDocument) {
            return $document;
        }

        [$before, $after] = self::getBeforeAndAfter($source);

        return $before . ($document->body->innerHTML ?? '') . $after;
    }

    /**
     * Get the string before and after the body, if any
     *
     * @return array{0: string, 1: string}
     */
    private static function getBeforeAndAfter(string $source): array
    {
        preg_match('/(?<before><body[\s>])(?<content>.*)(?<after><\/body>.*)/', $source, $matches);

        return [
            $matches['before'] ?? '',
            $matches['after'] ?? '',
        ];
    }
}
