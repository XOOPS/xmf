<?php
namespace Xmf\Test\Key;

use Xmf\Key\FileStorage;

class FileStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FileStorage
     */
    protected $object;

    /**
     * @var string
     */
    protected $testKey = 'x-unit-test-key-file';

    /**
     * @var string private directory for the key files, which fetch() includes as PHP
     */
    protected $storageDir;

    /**
     * @var string private parent of the storage directory
     */
    protected $storageRoot;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // The storage directory sits alone in a private parent, so a test can tell
        // whether anything was written beside it.
        $this->storageRoot = sys_get_temp_dir() . '/xmf-filestorage-' . bin2hex(random_bytes(8));
        $this->storageDir = $this->storageRoot . '/keys';
        if (!mkdir($this->storageRoot, 0700) || !mkdir($this->storageDir, 0700)) {
            $this->fail('cannot create private test directory');
        }
        $this->object = new FileStorage($this->storageDir, 'fubar');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        @$this->object->delete($this->testKey);
        @rmdir($this->storageDir);
        @rmdir($this->storageRoot);
    }

    public function testSave()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->assertTrue($this->object->save($name, $data));
        $this->assertEquals($data, $this->object->fetch($name));
    }

    public function testFetch()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->assertFalse(@$this->object->fetch($name));
        $this->assertTrue($this->object->save($name, $data));
        $this->assertEquals($this->object->fetch($name), $data);
    }

    public function testExists()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->assertFalse($this->object->exists($name));
        $this->assertTrue($this->object->save($name, $data));
        $this->assertTrue($this->object->exists($name));
    }

    public function testDelete()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->assertTrue($this->object->save($name, $data));
        $this->assertTrue($this->object->exists($name));
        $this->assertTrue($this->object->delete($name));
        $this->assertFalse(@$this->object->delete($name));
        $this->assertFalse($this->object->exists($name));
    }

    public function testNameWithDotUnderscoreAndDigitsIsAccepted()
    {
        $name = 'my.module_key-2';
        try {
            $this->assertTrue($this->object->save($name, 'data'));
            $this->assertSame('data', $this->object->fetch($name));
        } finally {
            @$this->object->delete($name);
        }
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unusableNames(): array
    {
        return [
            'parent directory'   => ['../outside'],
            'forward slash'      => ['sub/key'],
            'backslash'          => ['sub\\key'],
            'drive or stream'    => ['c:key'],
            'NUL byte'           => ["key\0"],
            'trailing line feed' => ["key\n"],
            'space'              => ['my key'],
            'empty'              => [''],
            'array'              => [['key']],
        ];
    }

    /**
     * @param mixed $name
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('unusableNames')]
    public function testUnusableNameIsRefusedByEveryMethod($name)
    {
        $calls = [
            'save'   => fn () => $this->object->save($name, 'data'),
            'fetch'  => fn () => $this->object->fetch($name),
            'exists' => fn () => $this->object->exists($name),
            'delete' => fn () => $this->object->delete($name),
        ];
        foreach ($calls as $method => $call) {
            try {
                $call();
                $this->fail($method . '() accepted the name');
            } catch (\InvalidArgumentException $e) {
                $this->assertSame('Invalid key name', $e->getMessage());
            }
        }
        // Nothing was written, inside the storage directory or beside it.
        $this->assertSame([], array_values(array_diff(scandir($this->storageDir), ['.', '..'])));
        $this->assertSame(['keys'], array_values(array_diff(scandir($this->storageRoot), ['.', '..'])));
    }
}
