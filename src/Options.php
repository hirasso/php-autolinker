<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

use Closure;
use Dom\HTMLElement;

final readonly class Options
{
    public ?Closure $postProcess;

    /**
     * @param bool $urls autolink urls
     * @param bool $emails autolink emails
     * @param bool $stripScheme strip the theme from link texts
     * @param int $truncateText truncate the link text to a maximum length with ellipsis
     * @param ?callable(HTMLElement): mixed $postProcess post-process autolinked elements
     */
    public function __construct(
        public bool $urls = true,
        public bool $emails = true,
        public bool $stripScheme = true,
        public int $truncateText = 50,
        ?callable $postProcess = null
    ) {
        $this->postProcess = $postProcess !== null
            ? Closure::fromCallable($postProcess)
            : null;
    }

    /**
     * Load the defaults
     */
    public static function defaults(): self
    {
        return new self();
    }

    /**
     * Modify the options in an immutable way
     */
    public function modify(
        ?bool $urls = null,
        ?bool $emails = null,
        ?bool $stripScheme = null,
        ?int $truncateText = null,
        ?callable $postProcess = null,
    ): self {
        return new self(
            urls: $urls ?? $this->urls,
            emails: $emails ?? $this->emails,
            stripScheme: $stripScheme ?? $this->stripScheme,
            truncateText: $truncateText ?? $this->truncateText,
            postProcess: $postProcess ?? $this->postProcess,
        );
    }

}
