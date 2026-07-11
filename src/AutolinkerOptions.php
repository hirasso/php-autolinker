<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

use Closure;

/**
 * Immutable configuration for {@see Autolinker::link()}.
 *
 * All options carry their canonical defaults here; the Processor reads them
 * straight off this object.
 */
final readonly class AutolinkerOptions
{
    /** @var ?Closure(\Dom\HTMLElement): mixed */
    public ?Closure $postProcess;

    /**
     * @param ?callable(\Dom\HTMLElement): mixed $postProcess post-process each created anchor
     */
    public function __construct(
        public bool $urls = true,
        public bool $emails = true,
        public bool $stripScheme = true,
        public int $truncateText = 50,
        ?callable $postProcess = null,
    ) {
        $this->postProcess = $postProcess !== null
            ? Closure::fromCallable($postProcess)
            : null;
    }
}
