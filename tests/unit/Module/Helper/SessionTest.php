<?php

declare(strict_types=1);

namespace Xmf\Test\Module\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xmf\Module\Helper\Session;

/**
 * Test double that sets the prefix directly, bypassing the module-loading constructor.
 */
final class PrefixedSessionTestHelper extends Session
{
    public function __construct()
    {
        $this->prefix = 'xmftest_';
    }
}

enum SessionTestEnum
{
    case On;
}

#[CoversClass(Session::class)]
final class SessionTest extends TestCase
{
    private Session $session;

    /** @var array<string, mixed>|null $_SESSION before the test, null when it was not set */
    private ?array $savedSession = null;

    protected function setUp(): void
    {
        $this->savedSession = $_SESSION ?? null;
        $_SESSION = [];
        $this->session = new PrefixedSessionTestHelper();
    }

    protected function tearDown(): void
    {
        if ($this->savedSession === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->savedSession;
        }
    }

    public function testGetRoundTripsScalarsArraysAndBinaryStrings(): void
    {
        $value = ['token' => "bin\0ary", 'list' => [1, 'two', ["\0"]]];
        $this->session->set('roundtrip', $value);

        $this->assertSame($value, $this->session->get('roundtrip'));
    }

    public function testGetReturnsDefaultWhenNotSet(): void
    {
        $this->assertSame('dflt', $this->session->get('never-set', 'dflt'));
    }

    public function testSetRejectsValueGetCouldNotRead(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->session->set('big', str_repeat('a', 5000000));
    }

    public function testSetRefusesEnumGetCouldNotRead(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->session->set('enum', [SessionTestEnum::On]);
    }

    public function testGetRefusesStoredEnum(): void
    {
        // written by older code; allowed_classes=false alone would return the enum case
        $_SESSION['xmftest_enum'] = serialize([SessionTestEnum::On]);

        $this->assertFalse($this->session->get('enum', 'dflt'));
    }

    public function testGetRefusesNonStringValue(): void
    {
        // set() always stores a string; an array written around it is not read
        $_SESSION['xmftest_raw'] = ['not', 'serialized'];

        $this->assertFalse($this->session->get('raw', 'dflt'));
    }
}
