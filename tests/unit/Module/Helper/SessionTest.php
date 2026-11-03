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

    /** @var array<string, mixed> */
    private array $savedSession = [];

    protected function setUp(): void
    {
        $this->savedSession = $_SESSION ?? [];
        $_SESSION = [];
        $this->session = new PrefixedSessionTestHelper();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->savedSession;
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

    public function testGetRefusesStoredEnum(): void
    {
        // allowed_classes=false alone would return the enum case
        $this->session->set('enum', [SessionTestEnum::On]);

        $this->assertFalse($this->session->get('enum', 'dflt'));
    }
}
