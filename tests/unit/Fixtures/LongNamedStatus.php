<?php

declare(strict_types=1);

namespace Xmf\Test\Fixtures;

/**
 * A backed enum whose case name is far longer than its encoded value
 */
enum LongNamedStatus: int
{
    case PROCESSING_INSTRUCTION_COMPLETE = 1;
}
