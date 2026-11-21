<?php
namespace Xmf\Test;

use Xmf\Yaml;

class YamlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testDumpAndLoad()
    {
        $inputArray = array('one' => 1, 'two' => array(1, 2), 'three' => '');

        $string = Yaml::dump($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::load((string)$string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testSaveAndRead()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'TEST');
        $inputArray = array('one' => 1, 'two' => array(1, 2), 'three' => '');

        $byteCount = Yaml::save($inputArray, $tmpfname);
        $this->assertFalse($byteCount === false);
        $this->assertGreaterThan(0, $byteCount);

        $outputArray = Yaml::read($tmpfname);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);

        unlink($tmpfname);
    }

    public function testDumpAndLoadWrapped()
    {
        $inputArray = array('one' => 1, 'two' => array(1, 2), 'three' => '');

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::loadWrapped((string)$string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testDumpAndLoadWrappedStress()
    {
        $inputArray = array('start' => '---', 'end' => '...', 'misc' => 'stuff');

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::loadWrapped((string)$string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testDumpAndLoadWrappedStress2()
    {
        $inputArray = array('start' => '---', 'end' => '...', 'misc' => 'stuff');

        $string = Yaml::dump($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::loadWrapped((string)$string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testSaveAndReadWrapped()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'TEST');
        $inputArray = array('one' => 1, 'two' => array(1, 2), 'three' => '');

        $byteCount = Yaml::saveWrapped($inputArray, $tmpfname);
        $this->assertFalse($byteCount === false);
        $this->assertGreaterThan(0, $byteCount);

        $outputArray = Yaml::readWrapped($tmpfname);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);

        unlink($tmpfname);
    }

    public function testDumpAndLoadWrappedWithClosingCommentSequence()
    {
        $inputArray = array('value' => 'contains */ closing comment', 'nested' => array('a' => '*/'));

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertNotFalse($string);
        $this->assertIsString($string);

        $outputArray = Yaml::loadWrapped((string) $string);
        $this->assertIsArray($outputArray);
        $this->assertSame($inputArray, $outputArray);
    }

    public function testDumpWrappedUsesHaltCompilerGuard()
    {
        $inputArray = array('key' => 'value');
        $string = Yaml::dumpWrapped($inputArray);
        $this->assertIsString($string);
        $this->assertStringStartsWith('<?php __halt_compiler(); ?>', (string) $string);
    }

    public function testDumpAndLoadWrappedWithPhpTag()
    {
        $inputArray = array('code' => '<?php echo "hello"; ?>');

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertNotFalse($string);
        $this->assertIsString($string);

        $outputArray = Yaml::loadWrapped((string) $string);
        $this->assertIsArray($outputArray);
        $this->assertSame($inputArray, $outputArray);
    }

    public function testLoadWrappedReadsOldCommentFormat()
    {
        $inputArray = array('one' => 1, 'two' => 'hello');
        $oldFormat = "<?php\n/*\n---\none: 1\ntwo: hello\n...\n*/\n";

        $outputArray = Yaml::loadWrapped($oldFormat);
        $this->assertIsArray($outputArray);
        $this->assertSame($inputArray, $outputArray);
    }

    public function testReadNoFile()
    {
        $this->assertFalse(Yaml::read('./no-such-file'));
    }

    public function testReadWrappedNoFile()
    {
        $this->assertFalse(Yaml::readWrapped('./no-such-file'));
    }

    public function testReadOversizedFile()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'YAMLTEST');
        $this->assertNotFalse($tmpfname, 'Failed to create temp file');
        try {
            // Create a file just over 2MB
            $fh = fopen($tmpfname, 'w');
            $this->assertNotFalse($fh, 'Failed to open temp file for writing');
            $line = str_repeat('x', 1024) . "\n";
            for ($i = 0; $i < 2049; $i++) {
                fwrite($fh, $line);
            }
            fclose($fh);
            // Suppress the trigger_error warning
            $result = @Yaml::read($tmpfname);
            $this->assertFalse($result);
        } finally {
            if (file_exists($tmpfname)) {
                unlink($tmpfname);
            }
        }
    }

    public function testReadWrappedOversizedFile()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'YAMLTEST');
        $this->assertNotFalse($tmpfname, 'Failed to create temp file');
        try {
            $fh = fopen($tmpfname, 'w');
            $this->assertNotFalse($fh, 'Failed to open temp file for writing');
            $line = str_repeat('x', 1024) . "\n";
            for ($i = 0; $i < 2049; $i++) {
                fwrite($fh, $line);
            }
            fclose($fh);
            $result = @Yaml::readWrapped($tmpfname);
            $this->assertFalse($result);
        } finally {
            if (file_exists($tmpfname)) {
                unlink($tmpfname);
            }
        }
    }

    public function testLoadInvalidYamlThrows()
    {
        $this->expectException(\RuntimeException::class);
        Yaml::load('key: [unclosed');
    }

    public function testReadWrappedInvalidYamlThrowsTheParserExceptionOnce()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'YAMLTEST');
        $this->assertNotFalse($tmpfname, 'Failed to create temp file');
        file_put_contents($tmpfname, "<?php __halt_compiler(); ?>\n---\nkey: [unclosed\n...\n");
        try {
            Yaml::readWrapped($tmpfname);
            $this->fail('Expected a RuntimeException');
        } catch (\RuntimeException $e) {
            // loadWrapped() threw it; readWrapped() passes it on instead of wrapping it again.
            $this->assertInstanceOf(\Symfony\Component\Yaml\Exception\ParseException::class, $e);
        } finally {
            unlink($tmpfname);
        }
    }

    public function testFailureThatIsNotARuntimeExceptionIsWrapped()
    {
        try {
            Yaml::load([]); // the parser takes a string
            $this->fail('Expected a RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf(\TypeError::class, $e->getPrevious());
        }
    }

    public function testSaveWrappedLeavesTheFileAloneWhenThereIsNothingToDump()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'YAMLTEST');
        $this->assertNotFalse($tmpfname, 'Failed to create temp file');
        file_put_contents($tmpfname, 'kept');
        try {
            // dumpWrapped() gives false for a value whose YAML text is "0". No array
            // dumps to that, so the integer stands in for "nothing to write" here.
            $this->assertFalse(Yaml::dumpWrapped(0));
            $this->assertFalse(Yaml::saveWrapped(0, $tmpfname));
            $this->assertSame('kept', file_get_contents($tmpfname));
        } finally {
            unlink($tmpfname);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function saveMethods(): array
    {
        return ['save' => ['save'], 'saveWrapped' => ['saveWrapped']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('saveMethods')]
    public function testFileNameWithNulByteWarnsAndReturnsFalse(string $method)
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        }, E_USER_WARNING);

        try {
            $result = Yaml::$method(['one' => 1], sys_get_temp_dir() . "/yaml\0test.yml");
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result);
        $this->assertSame(['Failed to write YAML file (ValueError)'], $warnings);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('saveMethods')]
    public function testFileNameThatIsNoStringWarnsAndReturnsFalse(string $method)
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        }, E_USER_WARNING);

        try {
            $result = Yaml::$method(['one' => 1], ['not', 'a', 'name']);
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result);
        $this->assertSame(['Failed to write YAML file (TypeError)'], $warnings);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('saveMethods')]
    public function testFailedWriteWarnsWithoutThePath(string $method)
    {
        $missing = sys_get_temp_dir() . '/xmf-yaml-no-such-dir-' . bin2hex(random_bytes(8));
        $warnings = [];
        // Every level. PHP still calls a handler for an error silenced with @, with
        // error_reporting() lowered for the moment: that is how a handler knows to
        // leave it. PHP's own warning for the failed write, which carries the full
        // path, must arrive silenced; the one that counts is the sanitized one.
        $level = error_reporting();
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings, $level): bool {
            if (error_reporting() === $level) {
                $warnings[] = $errstr;
            }

            return true;
        });

        try {
            $result = Yaml::$method(['one' => 1], $missing . '/schema.yml');
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result);
        $this->assertSame(['Failed to write YAML file: schema.yml'], $warnings);
    }
}
