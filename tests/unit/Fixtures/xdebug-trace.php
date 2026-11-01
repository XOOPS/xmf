<?php

declare(strict_types=1);

/*
 * Run by DebugTest in a child process with Xdebug in trace mode.
 * Arguments: autoload file, trace file, collect_params level.
 * Traces one call with a known argument and prints the trace file name.
 */

require $argv[1];

\Xmf\Debug::startTrace($argv[2], $argv[3]);
strrev('xmf-trace-argument');
echo (string) xdebug_stop_trace();
