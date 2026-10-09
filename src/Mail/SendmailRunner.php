<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

declare(strict_types=1);

namespace Xmf\Mail;

/**
 * Safe sendmail runner for XOOPS.
 *
 * - No shell: argv-only via proc_open([...], ..., ['bypass_shell' => true])
 * - Strict validation:
 *   • absolute ASCII path format
 *   • allowlist enforcement
 *   • canonical target check via realpath()
 *   • executable file check (optional symlink policy)
 * - Optional, validated envelope sender (-f)
 * - CRLF normalization (str_replace-based)
 * - Diagnostics: clipped stdout/stderr on failure; warns if stderr on success
 *
 * Customize:
 * - Pass a custom $allowlist (array of absolute paths) in the constructor.
 * - Toggle $allowSymlinks (default true) to allow symlinks that resolve
 *   to a canonical allowlisted target.
 * - Inject filesystem check callables (is_executable/is_link/is_file) for testing.
 *
 * @category  Xmf\Mail
 * @package   Xmf
 * @author    XOOPS Development Team <contact@xoops.org>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class SendmailRunner
{
    /** @var string[] absolute paths considered for allowlisting */
    private array $allowlist;

    /** @var string[] canonical realpaths of allowlisted binaries */
    private array $allowlistCanonical;

    /** @var bool allow symlinks that resolve to a canonical allowlist target */
    private bool $allowSymlinks;

    /** @var callable(string):bool */
    private $isExecutable;
    /** @var callable(string):bool */
    private $isLink;
    /** @var callable(string):bool */
    private $isFile;

    /** @var int wall-clock limit in seconds for one delivery, including process exit */
    private int $timeout;

    /** bytes of stdout/stderr kept for diagnostics; the rest is read and discarded */
    private const MAX_OUTPUT = 8192;

    /** seconds to wait for exit after SIGTERM before sending SIGKILL */
    private const TERMINATE_GRACE = 2;

    public function __construct(
        ?array $allowlist = null,
        ?callable $isExecutable = null,
        ?callable $isLink = null,
        ?callable $isFile = null,
        bool $allowSymlinks = true,
        int $timeout = 30
    ) {
        if ($timeout < 1) {
            throw new \InvalidArgumentException('Sendmail timeout must be at least 1 second.');
        }
        $this->timeout = $timeout;
        $this->allowlist = $allowlist ?? [
            '/usr/sbin/sendmail',
            '/usr/lib/sendmail',
            '/usr/bin/sendmail',
            '/usr/bin/msmtp',
            '/usr/sbin/ssmtp',
            '/usr/local/sbin/sendmail',
            '/usr/local/bin/sendmail',
        ];
        $this->isExecutable  = $isExecutable ?? 'is_executable';
        $this->isLink        = $isLink       ?? 'is_link';
        $this->isFile        = $isFile       ?? 'is_file';
        $this->allowSymlinks = $allowSymlinks;

        // Build canonical allowlist by resolving real targets of allowlisted entries.
        $canon = [];
        foreach ($this->allowlist as $p) {
            $rp = realpath($p);               // string|false
            if (is_string($rp)) {
                $canon[$rp] = true;           // set-like de-dupe
            }
        }
        $this->allowlistCanonical = array_keys($canon);
    }

    /**
     * Discover installed, allowlisted binaries (literal allowlist entries that
     * currently exist and meet executable criteria). Symlinks accepted only if
     * they pass isValidBinary() and policy allows them.
     *
     * @return string[] list of literal paths from the allowlist that are valid
     */
    public function discover(): array
    {
        $found = [];
        foreach ($this->allowlist as $path) {
            $real = realpath($path); // string|false
            $ok   = $this->isValidBinary($path, is_string($real) ? $real : null);
            if ($ok) {
                $found[] = $path;
            }
        }
        // Keep literal paths for UI consistency; remove duplicates just in case.
        return array_values(array_unique($found));
    }

    /**
     * Validate an absolute ASCII path against format, allowlist policy,
     * canonical real target, and filesystem permissions.
     *
     * @return string|null the canonical (resolved) path if valid; null otherwise
     */
    public function validatePath(string $path): ?string
    {
        $path = trim($path);
        if (!preg_match('~^/(?:[A-Za-z0-9._-]+/)*[A-Za-z0-9._-]+$~', $path)) {
            return null;
        }

        $resolved = realpath($path); // string|false
        if (!is_string($resolved)) {
            return null;
        }

        if ($resolved === $path) {
            // Not a symlink: the literal path must be allowlisted.
            if (!in_array($path, $this->allowlist, true)) {
                return null;
            }
        } else {
            // Symlink: allow only if policy permits and the resolved target is canonical-allowlisted.
            if (!$this->allowSymlinks || !in_array($resolved, $this->allowlistCanonical, true)) {
                return null;
            }
        }

        return $this->isValidBinary($path, $resolved) ? $resolved : null;
    }

    /**
     * Deliver an RFC 5322 message via sendmail -t -i, optionally with -f.
     *
     * @param string      $sendmailPath validated absolute path (literal form)
     * @param string      $rfc822       headers + CRLF CRLF + body
     * @param string|null $envelopeFrom optional envelope sender (validated)
     *
     * @return void
     *
     * @throws SendmailException on invalid path, process startup or pipe-open failures,
     *                           write failures, premature pipe closure, timeout, or non-zero exit
     */
    public function deliver(string $sendmailPath, string $rfc822, ?string $envelopeFrom = null): void
    {
        $validatedPath = $this->validatePath($sendmailPath);
        if ($validatedPath === null) {
            throw SendmailException::invalidPath();
        }

        // Normalize CRLF, lone CR and LF to CRLF for RFC 5322 compliance (two-step, no double expansion).
        $rfc822 = str_replace(["\r\n", "\r"], "\n", $rfc822);
        $rfc822 = str_replace("\n", "\r\n", $rfc822);

        // Prefer the literal path if it resolves to the same canonical target; else use canonical.
        $literal  = $sendmailPath;
        $resolved = realpath($literal);
        if (!is_string($resolved) || $resolved !== $validatedPath) {
            $literal = $validatedPath;
        }

        $argv = [$literal];

        // Optional, strictly-validated envelope sender (-f).
        $validatedEnvelopeFrom = $this->validateEnvelopeFrom($envelopeFrom);
        if ($validatedEnvelopeFrom !== null) {
            $argv[] = '-f';
            $argv[] = $validatedEnvelopeFrom;
        }

        // Safe flags only.
        $argv[] = '-t';
        $argv[] = '-i';

        $spec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $pipes = [];
        $proc = @proc_open($argv, $spec, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            throw SendmailException::failedToStartProcess();
        }
        /** @var array{0?: resource, 1?: resource, 2?: resource} $pipes */
        $stdin = $pipes[0] ?? null;
        $stdoutPipe = $pipes[1] ?? null;
        $stderrPipe = $pipes[2] ?? null;
        if (!is_resource($stdin) || !is_resource($stdoutPipe) || !is_resource($stderrPipe)) {
            proc_close($proc);
            throw SendmailException::failedToOpenPipes();
        }

        $stdout = '';
        $stderr = '';
        $exitCode = null;
        $deadline = (int) hrtime(true) + $this->timeout * 1000000000;
        stream_set_blocking($stdin, false);
        stream_set_blocking($stdoutPipe, false);
        stream_set_blocking($stderrPipe, false);

        try {
            $len = strlen($rfc822);
            $off = 0;
            $stdinOpen = true;
            $chunkSize = 8192;

            if ($len === 0) {
                fclose($stdin);
                $stdinOpen = false;
            }

            while ($stdinOpen || !feof($stdoutPipe) || !feof($stderrPipe)) {
                $read = [];
                $write = [];
                $except = null;

                if (!feof($stdoutPipe)) {
                    $read[] = $stdoutPipe;
                }
                if (!feof($stderrPipe)) {
                    $read[] = $stderrPipe;
                }
                if ($stdinOpen && $off < $len) {
                    $write[] = $stdin;
                }
                if ($read === [] && $write === []) {
                    break;
                }

                $remaining = $deadline - (int) hrtime(true);
                if ($remaining <= 0) {
                    throw SendmailException::timedOut($this->timeout);
                }
                $wait = min($remaining, 1000000000);
                $ready = @stream_select($read, $write, $except, 0, intdiv($wait, 1000));
                if ($ready === false) {
                    throw SendmailException::failedToOpenPipes();
                }
                if ($ready === 0) {
                    if ($stdinOpen && $off < $len && feof($stdin)) {
                        throw SendmailException::prematurePipeClosure();
                    }
                    continue;
                }

                foreach ($write as $stream) {
                    if ($stream !== $stdin) {
                        continue;
                    }
                    $chunk = substr($rfc822, $off, $chunkSize);
                    $n = @fwrite($stdin, $chunk);
                    if ($n === false) {
                        throw SendmailException::writeFailure();
                    }
                    if ($n === 0) {
                        if (feof($stdin)) {
                            throw SendmailException::prematurePipeClosure();
                        }
                        continue;
                    }
                    $off += $n;
                    if ($off >= $len) {
                        fclose($stdin);
                        $stdinOpen = false;
                    }
                }

                foreach ($read as $stream) {
                    $chunk = fread($stream, $chunkSize);
                    if ($chunk === false || $chunk === '') {
                        continue;
                    }
                    if ($stream === $stdoutPipe) {
                        $stdout .= substr($chunk, 0, self::MAX_OUTPUT - strlen($stdout));
                    } elseif ($stream === $stderrPipe) {
                        $stderr .= substr($chunk, 0, self::MAX_OUTPUT - strlen($stderr));
                    }
                }
            }

            if ($stdinOpen && $off < $len) {
                throw SendmailException::prematurePipeClosure();
            }

            // The pipes can close before the process exits.
            $exitCode = $this->waitForExit($proc, $deadline);
            if ($exitCode === null) {
                throw SendmailException::timedOut($this->timeout);
            }
        } finally {
            if (is_resource($stdin)) {
                fclose($stdin);
            }
            if (is_resource($stdoutPipe)) {
                fclose($stdoutPipe);
            }
            if (is_resource($stderrPipe)) {
                fclose($stderrPipe);
            }
            // On an error path, give the process until the deadline to exit, then
            // terminate it, so proc_close() cannot block the request indefinitely.
            if ($exitCode === null && $this->waitForExit($proc, $deadline) === null) {
                $this->terminate($proc);
            }
            proc_close($proc);
        }
        // Use the code from proc_get_status(): proc_close() returns -1 on PHP < 8.3
        // once proc_get_status() has seen the exit.
        $code = $exitCode;

        // Warn if stderr contains content despite success.
        if ($code === 0 && $stderr !== '') {
            trigger_error('sendmail warning (success): ' . $this->clipForLog($stderr), E_USER_WARNING);
        }

        if ($code !== 0) {
            $sOut  = $this->clipForLog($stdout);
            $sErr  = $this->clipForLog($stderr);
            $first = $this->firstLine($stderr);
            $displayPath = basename($literal);
            trigger_error(
                "sendmail failure: path={$displayPath} code={$code} stderr=\"{$sErr}\" stdout=\"{$sOut}\"",
                E_USER_WARNING
            );
            throw SendmailException::exitedWithCode($code, $first);
        }
    }

    /* ====================== helpers ====================== */

    /**
     * Poll until the process exits or the deadline passes.
     *
     * @param resource $proc     process from proc_open()
     * @param int      $deadline hrtime() value in nanoseconds
     *
     * @return int|null exit code, or null if the process is still running
     */
    private function waitForExit($proc, int $deadline): ?int
    {
        while (true) {
            $status = proc_get_status($proc);
            if (!$status['running']) {
                return $status['exitcode'];
            }
            if ((int) hrtime(true) >= $deadline) {
                return null;
            }
            usleep(10000);
        }
    }

    /**
     * Stop a process: SIGTERM, then SIGKILL if it is still running after the grace period.
     *
     * @param resource $proc process from proc_open()
     */
    private function terminate($proc): void
    {
        proc_terminate($proc);
        $grace = (int) hrtime(true) + self::TERMINATE_GRACE * 1000000000;
        if ($this->waitForExit($proc, $grace) === null) {
            proc_terminate($proc, 9);
        }
    }

    /**
     * Filesystem checks for the target binary.
     * Uses $real (canonical target) when provided; otherwise uses $path.
     */
    private function isValidBinary(string $path, ?string $real = null): bool
    {
        $target = $real ?? $path;

        if (!($this->isFile)($target) || !($this->isExecutable)($target)) {
            return false;
        }
        // If symlinks are globally disallowed, reject when the input is a symlink.
        if (!$this->allowSymlinks && ($this->isLink)($path)) {
            return false;
        }
        return true;
    }

    /**
     * Validate an email address for use in -f (envelope sender).
     * Returns sanitized address or null if unusable.
     */
    private function validateEnvelopeFrom(?string $addr): ?string
    {
        if ($addr === null || $addr === '') {
            return null;
        }
        // Extract <email@host> if a "Name <email>" form was supplied.
        if (preg_match('/<([^>]+)>/', $addr, $m)) {
            $addr = $m[1];
        }
        // Forbid any whitespace/control to prevent header/arg injection.
        if (preg_match('/\s/', $addr) || preg_match('/[\r\n]/', $addr)) {
            return null;
        }
        return filter_var($addr, FILTER_VALIDATE_EMAIL) ? $addr : null;
    }

    /** Clip a string for logs (remove most control chars, escape line breaks, limit length). */
    private function clipForLog(string $s, int $max = 400): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s) ?? '';
        $s = str_replace(["\r", "\n"], ['\\r', '\\n'], $s);
        if (strlen($s) > $max) {
            $s = substr($s, 0, $max) . '…';
        }
        return $s;
    }

    /** Get the first (non-empty) line from a blob, for concise error messages. */
    private function firstLine(string $s): string
    {
        $pos  = strpos($s, "\n");
        $line = $pos === false ? $s : substr($s, 0, $pos);
        return $this->clipForLog($line, 200);
    }
}
