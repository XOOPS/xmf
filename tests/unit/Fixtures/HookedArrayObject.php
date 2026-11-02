<?php

declare(strict_types=1);

namespace Xmf\Test\Fixtures;

/**
 * An ArrayObject with a hooked virtual property, which json_encode() writes under
 * STD_PROP_LIST (PHP 8.4+ syntax; loaded only by tests that require PHP 8.4)
 *
 * @extends \ArrayObject<int|string, mixed>
 */
final class HookedArrayObject extends \ArrayObject
{
    public string $virtual {
        get => str_repeat('v', $this->length);
    }

    public function __construct(private int $length)
    {
        parent::__construct([], \ArrayObject::STD_PROP_LIST);
    }
}
