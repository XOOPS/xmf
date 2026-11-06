<?php

namespace Xmf\Test\Module\Helper;

use Xmf\Module\Helper\Session;

/**
 * Test double that sets the prefix directly, bypassing the module-loading constructor.
 */
class PrefixedSessionTestHelper extends Session
{
    public function __construct()
    {
        $this->prefix = 'xmftest_';
    }
}

/**
 * @covers \Xmf\Module\Helper\Session
 */
class SessionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session */
    private $session;

    /** @var array|null $_SESSION before the test, null when it was not set */
    private $savedSession;

    protected function setUp(): void
    {
        $this->savedSession = isset($_SESSION) ? $_SESSION : null;
        $_SESSION = [];
        $this->session = new PrefixedSessionTestHelper();
    }

    protected function tearDown(): void
    {
        if (null === $this->savedSession) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->savedSession;
        }
    }

    public function testGetRoundTripsScalarsArraysAndBinaryStrings()
    {
        $value = ['token' => "bin\0ary", 'list' => [1, 'two', ["\0"], 1.5, null, false]];
        $this->session->set('roundtrip', $value);

        $this->assertSame($value, $this->session->get('roundtrip'));
        $this->session->set('no', false);
        $this->assertFalse($this->session->get('no', 'dflt'));
    }

    public function testGetReturnsDefaultWhenNotSet()
    {
        $this->assertSame('dflt', $this->session->get('never-set', 'dflt'));
    }

    public function testSetRefusesObjectGetCouldNotRead()
    {
        $this->expectException(\RuntimeException::class);
        $this->session->set('obj', ['nested' => [new \stdClass()]]);
    }

    public function testGetRefusesStoredObject()
    {
        // written by older code; allowed_classes=false alone would return __PHP_Incomplete_Class
        $_SESSION['xmftest_obj'] = serialize(['nested' => [new \ArrayObject([1])]]);

        $this->assertFalse($this->session->get('obj', 'dflt'));
    }

    public function testGetRefusesNonStringValue()
    {
        // set() always stores a string; an array written around it is not read
        $_SESSION['xmftest_raw'] = ['not', 'serialized'];

        $this->assertFalse($this->session->get('raw', 'dflt'));
    }

    public function testGetReturnsFalseForMalformedDataWithoutNotice()
    {
        $_SESSION['xmftest_bad'] = 'a:1:{i:0;';

        $this->assertFalse($this->session->get('bad', 'dflt'));
    }

    public function testGetRefusesCustomSerializedClassWithoutWarning()
    {
        // allowed_classes=false warns that __PHP_Incomplete_Class has no unserializer
        $_SESSION['xmftest_c'] = 'C:11:"ArrayObject":21:{x:i:0;a:0:{};m:a:0:{}}';

        $this->assertFalse($this->session->get('c', 'dflt'));
    }

    public function testGetRefusesSelfReferencingArray()
    {
        // R:1; makes the array contain itself; the object walk must not recurse forever
        $_SESSION['xmftest_loop'] = 'a:1:{i:0;R:1;}';

        $this->assertFalse($this->session->get('loop', 'dflt'));
    }

    public function testGetBoundsTheWalkOverSharedReferences()
    {
        // each level's second element references its first: 2^30 paths through 568 bytes
        $payload = 'a:0:{}';
        for ($d = 30; $d >= 1; $d--) {
            $payload = 'a:2:{i:0;' . $payload . 'i:1;R:' . ($d + 1) . ';}';
        }
        $_SESSION['xmftest_shared'] = $payload;

        $start = microtime(true);
        $this->assertFalse($this->session->get('shared', 'dflt'));
        $this->assertLessThan(1.0, microtime(true) - $start);
    }

    public function testGetIgnoresTrailingBytesLikeUnserialize()
    {
        $_SESSION['xmftest_trail'] = "i:7;\n";

        $this->assertSame(7, $this->session->get('trail'));
    }
}
