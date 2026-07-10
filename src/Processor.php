<?php

declare(strict_types=1);

namespace Hirasso\Autolink;

use Dom\HTMLDocument;
use Dom\HTMLElement;
use Dom\Text;
use Dom\XPath;

final readonly class Processor
{
    public function __construct(private Options $options)
    {

    }

    /**
     * Run the processor against a HTMLDocument
     */
    public function run(HTMLDocument $doc): void
    {
        foreach ($this->getNonEmptyTextNodes($doc) as $node) {
            $this->processTextNode($node);
        };
    }

    /**
     * Process a text node
     */
    private function processTextNode(Text $node): void
    {
        $node->data = $this->linkUrls($node->data);
        $node->data = $this->linkEmails($node->data);
    }

    /**
     * Link urls in a string
     */
    private function linkUrls(string $str): string
    {
        if (!$this->options->urls) {
            return $str;
        }
        // TODO link urls, return linked string
        return $str;
    }

    /**
     * Link emails in a string
     */
    private function linkEmails(string $str): string
    {
        if (!$this->options->emails) {
            return $str;
        }
        // TODO link emails, return linked string
        return $str;
    }

    /**
     * Get all (non-empty) text nodes
     * @return list<\Dom\Text>
     */
    private function getNonEmptyTextNodes(HTMLDocument $doc, ?HTMLElement $context = null): array
    {
        $query = $context
            ? './/text()[normalize-space() != ""]'
            : '//text()[normalize-space() != ""]';

        $ignoreList = 'head, script, style, svg, noscript, title, textarea, select, iframe, canvas, pre, code';

        /** @var list<\Dom\Text> */
        return array_values(array_filter(
            [...new XPath($doc)->query($query, $context)],
            fn ($node) =>
                $node->textContent
                && !$this->isWhitespaceOnly($node->textContent)
                && !$node->parentElement?->closest($ignoreList)
        ));
    }

    /**
     * Is the text only whitespace, including zero-width and non-breaking chars?
     */
    private function isWhitespaceOnly(string $text): bool
    {
        $stripped = preg_replace(
            '/[\s\p{Z}\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}\x{00AD}]+/u',
            '',
            $text
        );

        return $stripped === '';
    }
}
