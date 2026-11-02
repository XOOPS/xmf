<?php

declare(strict_types=1);

namespace Xmf\Test\Fixtures;

/**
 * Public properties whose encoded values come from get hooks (PHP 8.4+ syntax;
 * loaded only by tests that require PHP 8.4)
 */
final class HookedProperties
{
    public string $virtual {
        get => str_repeat('v', $this->length);
    }

    public string $backed = 'raw' {
        get => strtoupper($this->backed);
    }

    public function __construct(private int $length)
    {
    }
}
