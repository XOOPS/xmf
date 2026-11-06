<?php

namespace Xmf\Test\Module\Helper;

use Xmf\Module\Helper\Session;

/**
 * Test double that sets the prefix directly, bypassing the module-loading constructor.
 */
class PrefixedSessionTestHelper extends Session
{
    /**
     * Set the prefix without loading a module
     */
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

    /**
     * Start every test with an empty session
     */
    protected function setUp(): void
    {
        $this->savedSession = isset($_SESSION) ? $_SESSION : null;
        $_SESSION = [];
        $this->session = new PrefixedSessionTestHelper();
    }

    /**
     * Put back whatever $_SESSION held before the test
     */
    protected function tearDown(): void
    {
        if (null === $this->savedSession) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->savedSession;
        }
    }

    /**
     * Values set() accepts come back unchanged, a stored false included
     */
    public function testGetRoundTripsScalarsArraysAndBinaryStrings()
    {
        $value = ['token' => "bin\0ary", 'list' => [1, 'two', ["\0"], 1.5, null, false]];
        $this->session->set('roundtrip', $value);

        $this->assertSame($value, $this->session->get('roundtrip'));
        $this->session->set('no', false);
        $this->assertFalse($this->session->get('no', 'dflt'));
    }

    /**
     * An absent entry yields the caller's default
     */
    public function testGetReturnsDefaultWhenNotSet()
    {
        $this->assertSame('dflt', $this->session->get('never-set', 'dflt'));
    }

    /**
     * set() refuses an object anywhere in the value and names the limits
     */
    public function testSetRefusesObjectGetCouldNotRead()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('nested at most 32 levels and holding at most 100000 values');
        $this->session->set('obj', ['nested' => [new \stdClass()]]);
    }

    /**
     * A stored object written by older code reads back as false
     */
    public function testGetRefusesStoredObject()
    {
        // allowed_classes=false alone would return __PHP_Incomplete_Class
        $_SESSION['xmftest_obj'] = serialize(['nested' => [new \ArrayObject([1])]]);

        $this->assertFalse($this->session->get('obj', 'dflt'));
    }

    /**
     * An enum token at a value position is refused before unserialize() can autoload it
     */
    public function testGetRefusesEnumTokenWithoutAutoload()
    {
        $loaded = [];
        $autoloader = static function ($class) use (&$loaded) {
            $loaded[] = $class;
        };
        spl_autoload_register($autoloader);
        try {
            $_SESSION['xmftest_enum'] = 'a:1:{i:0;E:23:"Xmf\Test\NoSuchEnum:One";}';
            $this->assertFalse($this->session->get('enum', 'dflt'));
            $_SESSION['xmftest_enum2'] = 'E:23:"Xmf\Test\NoSuchEnum:One";';
            $this->assertFalse($this->session->get('enum2', 'dflt'));
        } finally {
            spl_autoload_unregister($autoloader);
        }
        $this->assertSame([], $loaded);
    }

    /**
     * Enum-like text as a string value is read; mid-string text at a token position is refused
     */
    public function testGetKeepsEnumLikeTextInsideStringsUnlessAtTokenPosition()
    {
        $this->session->set('text', 'E:23:"Xmf\Test\NoSuchEnum:One";');
        $this->assertSame('E:23:"Xmf\Test\NoSuchEnum:One";', $this->session->get('text'));

        // the only false positive of the token check: a string holding "; E:n:" text
        $this->session->set('fp', 'x;E:1:"y');
        $this->assertFalse($this->session->get('fp', 'dflt'));
    }

    /**
     * A value that is not the string set() stores is not read
     */
    public function testGetRefusesNonStringValue()
    {
        $_SESSION['xmftest_raw'] = ['not', 'serialized'];

        $this->assertFalse($this->session->get('raw', 'dflt'));
    }

    /**
     * Malformed data yields false and no notice reaches PHPUnit
     */
    public function testGetReturnsFalseForMalformedDataWithoutNotice()
    {
        $_SESSION['xmftest_bad'] = 'a:1:{i:0;';

        $this->assertFalse($this->session->get('bad', 'dflt'));
    }

    /**
     * A C: payload yields false and its "no unserializer" warning is silenced
     */
    public function testGetRefusesCustomSerializedClassWithoutWarning()
    {
        $_SESSION['xmftest_c'] = 'C:11:"ArrayObject":21:{x:i:0;a:0:{};m:a:0:{}}';

        $this->assertFalse($this->session->get('c', 'dflt'));
    }

    /**
     * An array containing itself is refused instead of walked forever
     */
    public function testGetRefusesSelfReferencingArray()
    {
        $_SESSION['xmftest_loop'] = 'a:1:{i:0;R:1;}';

        $this->assertFalse($this->session->get('loop', 'dflt'));
    }

    /**
     * Shared references that multiply the walk hit the visit budget quickly
     */
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

    /**
     * Bytes after the value are ignored as unserialize() does
     */
    public function testGetIgnoresTrailingBytesLikeUnserialize()
    {
        $_SESSION['xmftest_trail'] = "i:7;\n";

        $this->assertSame(7, $this->session->get('trail'));
    }
}
