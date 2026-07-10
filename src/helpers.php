<?php

declare(strict_types=1);

namespace Hirasso\Autolink;

/**
 * Autolink a HTML string or \Dom\HTMLDocument
 */
function autolink(string|\Dom\HTMLDocument $source): Autolink
{
    return match(true) {
        is_string($source) => Autolink::createFromString($source),
        default => Autolink::createFromDocument($source)
    };
}
