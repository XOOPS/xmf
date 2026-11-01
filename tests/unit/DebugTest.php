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

    #[RunInSeparateProcess]
    public function testStartTraceRefusesParameterLevelXdebugCannotHonour()
    {
        $this->requireXdebug('3.0');

        [$warnings, $started] = $this->runStartTrace('1');

        $this->assertStringContainsString('cannot limit parameters to level 1', $warnings);
        $this->assertFalse($started);
    }

    #[RunInSeparateProcess]
    public function testStartTraceLevelZeroSwitchesParameterCollectionOff()
    {
        $this->requireXdebug('3.3');

        [$warnings, $started] = $this->runStartTrace('0');

        $this->assertSame('0', ini_get('xdebug.collect_params'));
        $this->assertStringNotContainsString('trace not started', $warnings);
        // xdebug.mode is startup-only, so a trace can only begin where it includes trace
        $traceMode = in_array('trace', xdebug_info('mode'), true);
        $this->assertSame($traceMode, $started);
    }

    private function requireXdebug(string $minVersion): void
    {
        $version = phpversion('xdebug');
        if ($version === false || version_compare($version, $minVersion, '<')) {
            $this->markTestSkipped("Requires Xdebug {$minVersion}+");
        }
    }

    /**
     * Run startTrace() into a temp file
     *
     * @return array{string, bool} the warnings it raised, and whether a trace was running
     */
    private function runStartTrace(string $collectParams): array
    {
        if (xdebug_get_tracefile_name() !== false) {
            $this->markTestSkipped('A trace is already running');
        }
        $started = false;
        $warnings = [];
        set_error_handler(function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;
            return true;
        }, E_WARNING | E_USER_WARNING);
        $traceFile = sys_get_temp_dir() . '/xmf_debug_trace_' . getmypid();
        try {
            Debug::startTrace($traceFile, $collectParams);
            $started = xdebug_get_tracefile_name() !== false;
            if ($started) {
                $written = xdebug_stop_trace();
                if (is_string($written) && is_file($written)) {
                    unlink($written);
                }
            }
        } finally {
            restore_error_handler();
        }
        return [implode("\n", $warnings), $started];
    }
}
