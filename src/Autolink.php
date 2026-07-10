<?php

declare(strict_types=1);

namespace Hirasso\Autolink;

use Dom\HTMLDocument;
use InvalidArgumentException;
use Stringable;

/**
 * Autolink plain urls in your HTML
 *
 * Framework-agnostic: pass a string, get a string back. Wire it into your
 * CMS / framework yourself (e.g. a WordPress `acf/format_value` filter).
 */
final readonly class Autolink implements Stringable
{
    private Options $options;

    private function __construct(private HTMLDocument $document)
    {
        $this->options = new Options();
    }

    /**
     * Create a new Obfuscator instance from a HTMLDocument (by reference)
     */
    public static function createFromDocument(HTMLDocument $document): self
    {
        return new self($document);
    }

    /**
     * Create a new Obfuscator instance from a HTML string
     */
    public static function createFromString(string $source): self
    {
        $source = self::parseSource($source);

        return new self(HTMLDocument::createFromString($source, LIBXML_NOERROR));
    }

    /**
     * Guard against full HTML documents: Autolink should only run against HTML fragments
     */
    private static function parseSource(string $source): string
    {
        if (preg_match('/<!doctype[\s>]|<(?:html|head|body)[\s>]/i', $source) === 1) {
            throw new InvalidArgumentException(
                'Autolink expects a html fragment, not a full document '
                . '(<!doctype>, <html>, <head> or <body> found).'
            );
        }
        return $source;
    }

    /**
     * Autolink URLs
     */
    public function urls(bool $enable = true): self
    {
        $this->options->modify(urls: $enable);
        return $this;
    }

    /**
     * Autolink emails
     */
    public function emails(bool $enable = true): self
    {
        $this->options->modify(emails: $enable);
        return $this;
    }

    /**
     * Process a document with the selected options
     */
    public function process(): self {
        $processor = new Processor($this->options);
        $processor->run($this->document);
        return $this;
    }

    /**
     * Allow to echo directly
     */
    public function __toString(): string {
        $this->process();
        return $this->document->body->innerHTML ?? '';
    }

}
