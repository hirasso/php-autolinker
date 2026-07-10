<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

use Closure;
use Dom\Document;
use Dom\HTMLDocument;
use Dom\HTMLElement;
use Dom\Text;
use Dom\XPath;

final readonly class Processor
{
    /** Characters trimmed from the trailing end of a match (they belong to the prose, not the link) */
    private const TRAILING_PUNCTUATION = '.,;:!?';

    /** @var ?Closure(HTMLElement): mixed */
    private ?Closure $postProcess;

    /**
     * @param ?callable(HTMLElement): mixed $postProcess post-process each created anchor
     */
    public function __construct(
        private bool $urls = true,
        private bool $emails = true,
        private bool $stripScheme = true,
        private int $truncateText = 50,
        ?callable $postProcess = null,
    ) {
        $this->postProcess = $postProcess !== null
            ? Closure::fromCallable($postProcess)
            : null;
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
     * Process a text node: link urls and emails in a single pass, then replace
     * the text node with the resulting fragment (if anything was linked)
     */
    private function processTextNode(Text $node): void
    {
        $html = $this->linkify($node->data);

        if ($html === null) {
            return;
        }

        $this->replaceWithFragment($node, $html);
    }

    /**
     * Walk a plain-text string once, wrapping urls and emails in anchors and
     * escaping everything in between. Returns the assembled HTML, or null if
     * nothing matched (so the text node can be left untouched).
     */
    private function linkify(string $text): ?string
    {
        $alternatives = [];
        if ($this->urls) {
            $alternatives[] = '(?<url>(?:https?://|www\.)[^\s<>]+)';
        }
        if ($this->emails) {
            $alternatives[] = '(?<email>[\w.+-]+@[\w-]+(?:\.[\w-]+)+)';
        }

        if ($alternatives === []) {
            return null;
        }

        $pattern = '~' . implode('|', $alternatives) . '~iu';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $html = '';
        $cursor = 0;

        foreach ($matches as $match) {
            [$full, $offset] = $match[0];

            /** urls are matched before emails, so a match with a url group is always a url */
            $isUrl = ($match['url'][1] ?? -1) !== -1;

            $html .= $this->escape(substr($text, $cursor, $offset - $cursor));
            $html .= $isUrl ? $this->linkUrls($full) : $this->linkEmails($full);

            $cursor = $offset + strlen($full);
        }

        $html .= $this->escape(substr($text, $cursor));

        return $html;
    }

    /**
     * Link a single matched url, returning the anchor followed by any trailing
     * punctuation that was trimmed back out of the match
     */
    private function linkUrls(string $str): string
    {
        if (!$this->urls) {
            return $this->escape($str);
        }

        [$url, $trailing] = $this->trimTrailingPunctuation($str);

        $href = str_starts_with(strtolower($url), 'www.') ? "https://$url" : $url;

        return $this->buildAnchor($href, $this->formatUrlText($url)) . $this->escape($trailing);
    }

    /**
     * Link a single matched email as a mailto: anchor, returning the anchor
     * followed by any trailing punctuation that was trimmed back out
     */
    private function linkEmails(string $str): string
    {
        if (!$this->emails) {
            return $this->escape($str);
        }

        [$email, $trailing] = $this->trimTrailingPunctuation($str);

        return $this->buildAnchor("mailto:$email", $this->truncate($email)) . $this->escape($trailing);
    }

    /**
     * Build the visible text for a url anchor: optionally strip the scheme and a
     * lone trailing slash, then truncate
     */
    private function formatUrlText(string $url): string
    {
        $text = $url;

        if ($this->stripScheme) {
            $text = preg_replace('~^https?://~i', '', $text) ?? $text;
            $text = preg_replace('~/$~', '', $text) ?? $text;
        }

        return $this->truncate($text);
    }

    /**
     * Truncate visible link text to the configured maximum length (ellipsis
     * included in the limit). A limit of 0 disables truncation.
     */
    private function truncate(string $text): string
    {
        $limit = $this->truncateText;

        if ($limit <= 0 || mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit - 1) . '…';
    }

    /**
     * Trim trailing sentence punctuation and unbalanced closing parens off a
     * match. Returns [cleaned match, trimmed trailing string].
     * @return array{string, string}
     */
    private function trimTrailingPunctuation(string $str): array
    {
        $trailing = '';

        while ($str !== '') {
            $last = substr($str, -1);

            $isPunctuation = str_contains(self::TRAILING_PUNCTUATION, $last);
            $isUnbalancedParen = $last === ')'
                && substr_count($str, '(') < substr_count($str, ')');

            if (!$isPunctuation && !$isUnbalancedParen) {
                break;
            }

            $trailing = $last . $trailing;
            $str = substr($str, 0, -1);
        }

        return [$str, $trailing];
    }

    /**
     * Build an anchor element from an href and its visible text
     */
    private function buildAnchor(string $href, string $text): string
    {
        return sprintf('<a href="%s">%s</a>', $this->escape($href), $this->escape($text));
    }

    /**
     * Escape a plain-text string for safe HTML insertion
     */
    private function escape(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Parse an HTML string into nodes and use them to replace a text node,
     * running the postProcess callback on each created anchor
     */
    private function replaceWithFragment(Text $node, string $html): void
    {
        $doc = $node->ownerDocument;
        $parent = $node->parentNode;

        if (!$doc instanceof Document || $parent === null) {
            return;
        }

        $container = $doc->createElement('div');
        $container->innerHTML = $html;

        if ($this->postProcess !== null) {
            foreach ($container->getElementsByTagName('a') as $anchor) {
                if ($anchor instanceof HTMLElement) {
                    ($this->postProcess)($anchor);
                }
            }
        }

        while ($container->firstChild !== null) {
            $parent->insertBefore($container->firstChild, $node);
        }

        $parent->removeChild($node);
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

        $ignoreList = 'head, script, style, svg, noscript, title, textarea, select, iframe, canvas, pre, code, a';

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
