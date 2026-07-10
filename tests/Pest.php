<?php

declare(strict_types=1);

use Hirasso\Autolinker\Autolinker;

/**
 * Render a fragment through Autolinker with the default options
 */
function render(string $html): string
{
    return Autolinker::link($html);
}
