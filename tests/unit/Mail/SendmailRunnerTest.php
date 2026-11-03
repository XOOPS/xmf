<?php

namespace Xmf\Test\Mail;

use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\Mail\SendmailException;
use Xmf\Mail\SendmailRunner;

class SendmailRunnerTest extends \PHPUnit\Framework\TestCase
{
    private array $cleanupPaths = array();

    protected function tearDown(): void
    {
        foreach ($this->cleanupPaths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $this->cleanupPaths = array();
    }

    public function testDeliverRejectsInvalidPath()
    {
        $runner = new SendmailRunner();

        $this->expectException(SendmailException::class);
        $this->expectExceptionMessage('Invalid sendmail path.');

        $runner->deliver('/definitely/not/a/sendmail-binary', 'Subject: test');
    }

    public function testDeliverWritesMessageToSendmailProcess()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-output-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > " . escapeshellarg($outputFile) . "\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));
        $message = "Subject: Test\n\nHello world\n";

        $runner->deliver($script, $message);

        $this->assertSame("Subject: Test\r\n\r\nHello world\r\n", file_get_contents($outputFile));
    }

    public function testDeliverThrowsOnNonZeroExitCode()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > /dev/null\nprintf 'boom\\n' >&2\nexit 1\n"
        );
        $runner = new SendmailRunner(array($script));

        set_error_handler(static function (): bool {
            return true;
        });

        try {
            $this->expectException(SendmailException::class);
            $this->expectExceptionMessage('Sendmail exited with code 1: boom');

            $runner->deliver($script, "Subject: Test\n\nHello world\n");
        } finally {
            restore_error_handler();
        }
    }

    public function testDeliverWarnsWhenSendmailWritesToStderrOnSuccess()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > /dev/null\nprintf 'minor warning\\n' >&2\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));
        $warning = null;

        set_error_handler(static function (int $errno, string $errstr) use (&$warning): bool {
            $warning = $errstr;
            return true;
        });

        try {
            $runner->deliver($script, "Subject: Test\n\nHello world\n");
        } finally {
            restore_error_handler();
        }

        $this->assertSame('sendmail warning (success): minor warning\n', $warning);
    }

    public function testDeliverEmptyPayloadDoesNotHang()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-output-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > " . escapeshellarg($outputFile) . "\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));

        $runner->deliver($script, '');

        $this->assertSame('', file_get_contents($outputFile));
    }

    public function testConstructorRejectsTimeoutBelowOneSecond()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sendmail timeout must be at least 1 second.');

        // the constructor must throw before this assertion runs
        $this->assertInstanceOf(SendmailRunner::class, new SendmailRunner(null, null, null, null, true, 0));
    }

    public function testDeliverNormalizesLoneCarriageReturns()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-output-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > " . escapeshellarg($outputFile) . "\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));

        $runner->deliver($script, "Subject: Test\rX-Test: 1\n\r\nBody\r");

        $this->assertSame("Subject: Test\r\nX-Test: 1\r\n\r\nBody\r\n", file_get_contents($outputFile));
    }

    public static function hangingScriptProvider(): array
    {
        return [
            'keeps pipes open' => ["cat > /dev/null\nexec sleep 30\n"],
            'closes pipes, keeps running' => ["cat > /dev/null\nexec 1>&- 2>&-\nexec sleep 30\n"],
            'ignores SIGTERM' => ["trap '' TERM\ncat > /dev/null\nexec sleep 30\n"],
        ];
    }

    #[DataProvider('hangingScriptProvider')]
    public function testDeliverTimesOutOnHangingProcess(string $body)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $script = $this->createExecutableScript("#!/usr/bin/env bash\n" . $body);
        $runner = new SendmailRunner(array($script), null, null, null, true, 1);
        $start = microtime(true);

        try {
            $runner->deliver($script, "Subject: Test\n\nHello world\n");
            $this->fail('Expected a timeout.');
        } catch (SendmailException $e) {
            $this->assertSame('Sendmail did not finish within 1 seconds.', $e->getMessage());
        }

        // 1 s limit + 2 s SIGTERM grace, with slack for slow CI
        $this->assertLessThan(6, microtime(true) - $start);
    }

    public function testDeliverBoundsBufferedOutput()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > /dev/null\nhead -c 20000000 /dev/zero | tr '\\0' x\n"
            . "head -c 20000000 /dev/zero | tr '\\0' y >&2\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));
        $warning = null;
        $before = memory_get_usage();
        memory_reset_peak_usage();

        set_error_handler(static function (int $errno, string $errstr) use (&$warning): bool {
            $warning = $errstr;
            return true;
        });

        try {
            $runner->deliver($script, "Subject: Test\n\nHello world\n");
        } finally {
            restore_error_handler();
        }

        $this->assertStringStartsWith('sendmail warning (success): yyyy', (string) $warning);
        $this->assertLessThan(2000000, memory_get_peak_usage() - $before);
    }

    public function testDeliverRunsCanonicalTargetWhenLinkDirectoryIsWritableByOthers()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-argv0-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\nprintf '%s' \"\$0\" > " . escapeshellarg($outputFile) . "\ncat > /dev/null\nexit 0\n"
        );
        // not under the temp dir: a world-writable /tmp would make the walk fail before the group bit
        $linkDir = __DIR__ . '/sendmail-linkdir-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($linkDir, 0700));
        $link = $linkDir . '/sendmail';

        try {
            chmod($linkDir, 0700);
            if (!$this->parentsTrusted($linkDir)) {
                $this->markTestSkipped('A directory above the test directory is group/other-writable or owned by another user.');
            }
            $this->assertTrue(symlink($script, $link));
            $runner = new SendmailRunner(array($link));

            $runner->deliver($link, "Subject: Test\n\nHello\n");
            $this->assertSame($link, file_get_contents($outputFile), 'A private link directory keeps the literal path');

            chmod($linkDir, 0770); // group members could swap the link after validation
            $runner->deliver($link, "Subject: Test\n\nHello\n");
        } finally {
            @unlink($link);
            @rmdir($linkDir);
        }

        $this->assertSame(realpath($script), file_get_contents($outputFile));
    }

    public function testDeliverRunsCanonicalTargetWhenALaterLinkHopIsWritableByOthers()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-argv0-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\nprintf '%s' \"\$0\" > " . escapeshellarg($outputFile) . "\ncat > /dev/null\nexit 0\n"
        );
        $suffix = bin2hex(random_bytes(8));
        $linkDir = __DIR__ . '/sendmail-linkdir-' . $suffix;
        $hopDir = __DIR__ . '/sendmail-hopdir-' . $suffix;
        $this->assertTrue(mkdir($linkDir, 0700));
        $this->assertTrue(mkdir($hopDir, 0700));
        $link = $linkDir . '/sendmail';
        $hop = $hopDir . '/mta';

        try {
            chmod($linkDir, 0700);
            chmod($hopDir, 0700);
            if (!$this->parentsTrusted($linkDir)) {
                $this->markTestSkipped('A directory above the test directory is group/other-writable or owned by another user.');
            }
            $this->assertTrue(symlink($script, $hop));
            $this->assertTrue(symlink('../' . basename($hopDir) . '/mta', $link)); // relative hop
            $runner = new SendmailRunner(array($link));

            $runner->deliver($link, "Subject: Test\n\nHello\n");
            $this->assertSame($link, file_get_contents($outputFile), 'A private link chain keeps the literal path');

            chmod($hopDir, 0777); // another account could swap the middle link after validation
            $runner->deliver($link, "Subject: Test\n\nHello\n");
        } finally {
            @unlink($link);
            @unlink($hop);
            @rmdir($linkDir);
            @rmdir($hopDir);
        }

        $this->assertSame(realpath($script), file_get_contents($outputFile));
    }

    public function testDeliverRunsCanonicalTargetWhenALinkedDirectoryCanBeSwapped()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-argv0-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\nprintf '%s' \"\$0\" > " . escapeshellarg($outputFile) . "\ncat > /dev/null\nexit 0\n"
        );
        $suffix = bin2hex(random_bytes(8));
        $linkDir = __DIR__ . '/sendmail-linkdir-' . $suffix;
        $openDir = __DIR__ . '/sendmail-opendir-' . $suffix;
        $subDir = $openDir . '/sub';
        $this->assertTrue(mkdir($linkDir, 0700));
        $this->assertTrue(mkdir($subDir, 0700, true));
        $dirLink = $linkDir . '/bin';
        $hop = $subDir . '/sendmail';
        $path = $dirLink . '/sendmail'; // linkDir/bin -> openDir/sub, sub/sendmail -> script

        try {
            chmod($linkDir, 0700);
            chmod($openDir, 0700);
            chmod($subDir, 0700);
            if (!$this->parentsTrusted($linkDir)) {
                $this->markTestSkipped('A directory above the test directory is group/other-writable or owned by another user.');
            }
            $this->assertTrue(symlink($subDir, $dirLink));
            $this->assertTrue(symlink($script, $hop));
            $runner = new SendmailRunner(array($path));

            $runner->deliver($path, "Subject: Test\n\nHello\n");
            $this->assertSame($path, file_get_contents($outputFile), 'A private linked directory keeps the literal path');

            chmod($openDir, 0777); // another account could swap sub, which the directory link points into
            $runner->deliver($path, "Subject: Test\n\nHello\n");
        } finally {
            @unlink($hop);
            @unlink($dirLink);
            @rmdir($subDir);
            @rmdir($openDir);
            @rmdir($linkDir);
        }

        $this->assertSame(realpath($script), file_get_contents($outputFile));
    }

    /**
     * True when $dir and every directory above it are owned by root or this user and closed to
     * group and other writes, the condition SendmailRunner needs to keep the literal path
     */
    private function parentsTrusted(string $dir): bool
    {
        if (!function_exists('posix_geteuid')) {
            return false;
        }
        do {
            $mode = fileperms($dir);
            $owner = fileowner($dir);
            if ($mode === false || ($mode & 0022) !== 0 || ($owner !== 0 && $owner !== posix_geteuid())) {
                return false;
            }
            $parent = $dir;
            $dir = dirname($dir);
        } while ($dir !== $parent);

        return true;
    }

    private function createTempFile(string $prefix, string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        $this->assertNotFalse($path, 'Failed to create temporary file.');
        file_put_contents($path, $contents);
        $this->cleanupPaths[] = $path;

        return $path;
    }

    private function createExecutableScript(string $contents): string
    {
        $path = $this->createTempFile('sendmail-script-', $contents);
        chmod($path, 0700);

        return $path;
    }
}
