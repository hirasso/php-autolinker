<?php

declare(strict_types=1);

namespace Hirasso\Autolinker;

/**
 * Autolink a HTML string or \Dom\HTMLDocument
 */
function autolink(string|\Dom\HTMLDocument $source): Autolinker
{
    return match(true) {
        is_string($source) => Autolinker::createFromString($source),
        default => Autolinker::createFromDocument($source)
    };
}
