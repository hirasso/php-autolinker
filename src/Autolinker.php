<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

use Dom\HTMLDocument;
use Dom\HTMLElement;
use InvalidArgumentException;
use Stringable;

/**
 * Autolink urls and email addresses in your HTML
 *
 * Framework-agnostic: pass a string, get a string back. Wire it into your
 * CMS / framework yourself (e.g. a WordPress `acf/format_value` filter).
 */
final class Autolinker implements Stringable
{
    private Options $options;

    private function __construct(private HTMLDocument $document)
    {
        $this->options = new Options();
    }

    /**
     * Create a new Autolinker instance from a HTMLDocument (by reference)
     */
    public static function createFromDocument(HTMLDocument $document): self
    {
        return new self($document);
    }

    /**
     * Create a new Autolinker instance from a HTML string
     */
    public static function createFromString(string $source): self
    {
        $source = self::parseSource($source);

        return new self(HTMLDocument::createFromString($source, LIBXML_NOERROR));
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

    /**
     * Autolink URLs
     */
    public function urls(bool $enable = true): self
    {
        $this->options = $this->options->modify(urls: $enable);
        return $this;
    }

    /**
     * Autolink emails
     */
    public function emails(bool $enable = true): self
    {
        $this->options = $this->options->modify(emails: $enable);
        return $this;
    }

    /**
     * Strip the scheme from link texts
     */
    public function stripScheme(bool $enable = true): self
    {
        $this->options = $this->options->modify(stripScheme: $enable);
        return $this;
    }

    /**
     * Truncate link texts to a maximum length with ellipsis (0 disables)
     */
    public function truncateText(int $length): self
    {
        $this->options = $this->options->modify(truncateText: $length);
        return $this;
    }

    /**
     * Post-process each autolinked element
     * @param callable(HTMLElement): mixed $callback
     */
    public function postProcess(callable $callback): self
    {
        $this->options = $this->options->modify(postProcess: $callback);
        return $this;
    }

    /**
     * Process a document with the selected options
     */
    public function process(): self
    {
        $processor = new Processor($this->options);
        $processor->run($this->document);
        return $this;
    }

    /**
     * Allow to echo directly
     */
    public function __toString(): string
    {
        $this->process();
        return $this->document->body->innerHTML ?? '';
    }

}
