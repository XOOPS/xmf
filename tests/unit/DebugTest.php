<?php

namespace Xmf\Test;

use Kint\Kint;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Xmf\Debug;

class DebugTest extends \PHPUnit\Framework\TestCase
{
    /** The argument tests/unit/Fixtures/xdebug-trace.php passes to the traced call */
    private const TRACE_ARGUMENT = 'xmf-trace-argument';

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

        [$warnings] = $this->runStartTrace('0');

        $this->assertSame('0', ini_get('xdebug.collect_params'));
        $this->assertStringNotContainsString('trace not started', $warnings);
    }

    /**
     * xdebug.mode is startup-only, so the real trace runs in a child process in trace mode
     */
    public function testLevelZeroTraceOmitsArgumentValues()
    {
        $this->requireXdebug('3.3');

        $this->assertStringContainsString(self::TRACE_ARGUMENT, $this->childTrace('3'));
        $this->assertStringNotContainsString(self::TRACE_ARGUMENT, $this->childTrace('0'));
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
        } finally {
            if ($started) {
                $this->removeFile(xdebug_stop_trace());
            }
            restore_error_handler();
        }
        return [implode("\n", $warnings), $started];
    }

    /**
     * Trace the fixture's call in a child PHP process and return the trace file contents
     */
    private function childTrace(string $collectParams): string
    {
        $command = [PHP_BINARY];
        $ini = php_ini_loaded_file();
        if ($ini !== false) {
            array_push($command, '-c', $ini);
        }
        array_push(
            $command,
            '-d',
            'xdebug.start_with_request=no',
            '-d',
            'xdebug.use_compression=0',
            __DIR__ . '/Fixtures/xdebug-trace.php',
            dirname(__DIR__, 2) . '/vendor/autoload.php',
            sys_get_temp_dir() . '/xmf_child_trace_' . bin2hex(random_bytes(6)),
            $collectParams
        );
        $env = getenv();
        $env['XDEBUG_MODE'] = 'trace';

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
        $this->assertIsResource($process);
        $written = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        try {
            $this->assertFileExists($written, "Child trace failed: {$errors}");
            return (string) file_get_contents($written);
        } finally {
            $this->removeFile($written);
        }
    }

    /**
     * @param mixed $path trace file name, or false when no trace was written
     */
    private function removeFile($path): void
    {
        if (is_string($path) && $path !== '' && is_file($path)) {
            unlink($path);
        }
    }
}
