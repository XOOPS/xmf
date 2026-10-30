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
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->storageDir = sys_get_temp_dir() . '/xmf-filestorage-' . bin2hex(random_bytes(8));
        if (!mkdir($this->storageDir, 0700)) {
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
}
