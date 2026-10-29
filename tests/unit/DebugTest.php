<?php

namespace Xmf\Test;

use Kint\Kint;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Xmf\Debug;

class DebugTest extends \PHPUnit\Framework\TestCase
{
    #[RunInSeparateProcess]
    public function testFirstDumpKeepsDisabledKintDisabled()
    {
        Kint::$enabled_mode = false;

        ob_start();
        Debug::dump('secret');
        $output = ob_get_clean();

        $this->assertFalse(Kint::$enabled_mode);
        $this->assertSame('', $output);
    }
}
