<?php
namespace Xmf\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\ProxyCheck;

class localProxyCheck extends ProxyCheck
{
    public function __construct($name, $header, $trusted = ['10.0.0.0/8'], $remoteAddr = '10.1.1.1')
    {
        $this->proxyHeaderName = $name;
        $this->proxyHeader = $header;
        $this->trustedProxies = $trusted;
        $this->remoteAddr = $remoteAddr;
    }

    public function isTrusted($ip)
    {
        return $this->isTrustedProxy($ip);
    }
}

class ProxyCheckTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProxyCheck
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ProxyCheck();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGet()
    {
        $ip = $this->object->get();
        $this->assertFalse($ip);
    }

    public static function getProxyCheckTestData()
    {
        return array(
//          ['name', 'header', 'expected'],
            ['HTTP_FORWARDED', 'for=192.168.2.60;proto=http;by=203.0.113.43', false],
            ['HTTP_FORWARDED', 'for=203.0.113.195;proto=http;by=203.0.113.43', '203.0.113.195'],
            ['HTTP_FORWARDED', 'for="[2020:db8:85a3:8d3:1319:8a2e:370:7348]";proto=http;by=203.0.113.43', '2020:db8:85a3:8d3:1319:8a2e:370:7348'],
            ['HTTP_NOT_FORWARDED', 'for="[2020:db8:85a3:8d3:1319:8a2e:370:7348]";proto=http;by=203.0.113.43', false],
            ['HTTP_CLIENT_IP', '203.0.113.195', '203.0.113.195'],
            ['STUFF', '2020:db8:85a3:8d3:1319:8a2e:370:7348', '2020:db8:85a3:8d3:1319:8a2e:370:7348'],
            // right-most untrusted entry is the client; left entries are client supplied
            ['HTTP_X_FORWARDED_FOR', '8.8.8.8, 203.0.113.195', '203.0.113.195'],
            ['HTTP_X_FORWARDED_FOR', '8.8.8.8, 203.0.113.195, 10.2.2.2', '203.0.113.195'],
            ['HTTP_X_FORWARDED_FOR', '10.2.2.2, 10.3.3.3', false],
            ['HTTP_X_FORWARDED_FOR', '203.0.113.195, garbage, 10.2.2.2', false],
            ['HTTP_FORWARDED', 'for=8.8.8.8, for=203.0.113.195', '203.0.113.195'],
            ['HTTP_FORWARDED', 'for=203.0.113.195, for=10.2.2.2;proto=https', '203.0.113.195'],
            // RFC 7239 parsing: case-insensitive names, node ports, quoted delimiters
            ['HTTP_FORWARDED', 'For=203.0.113.195', '203.0.113.195'],
            ['HTTP_FORWARDED', 'FOR="203.0.113.195:4711"', '203.0.113.195'],
            ['HTTP_FORWARDED', 'for="[2606:4700::1111]:443"', '2606:4700::1111'],
            ['HTTP_FORWARDED', 'proto=https;xfor=1.2.3.4;for=203.0.113.195', '203.0.113.195'],
            ['HTTP_FORWARDED', 'for=203.0.113.195;host="a,b;c"', '203.0.113.195'],
            ['HTTP_FORWARDED', 'for=203.0.113.195;for=8.8.8.8', false],
            ['HTTP_FORWARDED', 'for=unknown', false],
            ['HTTP_FORWARDED', 'for=203.0.113.195, for=_hidden', false],
            ['HTTP_FORWARDED', 'for=203.0.113.195, proto=https', false],
            ['HTTP_FORWARDED', 'for="203.0.113.195', false],
            // anything after the address other than a numeric port stops the walk
            ['HTTP_X_FORWARDED_FOR', '8.8.8.8, 10.2.2.2:not-a-port', false],
            ['HTTP_X_FORWARDED_FOR', '8.8.8.8, 203.0.113.195:443', '203.0.113.195'],
            ['HTTP_FORWARDED', 'for="[2606:4700::1111]x"', false],
            ['HTTP_FORWARDED', 'for="[2606:4700::1111]:44x"', false],
            ['HTTP_FORWARDED', 'for=8.8.8.8, for="[10::1]garbage"', false],
        );
    }

    /**
     * @dataProvider getProxyCheckTestData
     */
    public function testProxyCheck($name, $header, $expected)
    {
        $obj = new localProxyCheck($name, $header);
        $this->assertSame($expected, $obj->get());
    }

    public function testHeaderIgnoredWhenPeerIsNotTrusted()
    {
        $obj = new localProxyCheck('HTTP_X_FORWARDED_FOR', '203.0.113.195', ['10.0.0.0/8'], '198.51.100.7');
        $this->assertFalse($obj->get());
    }

    public function testHeaderIgnoredWhenNoTrustedProxiesConfigured()
    {
        $obj = new localProxyCheck('HTTP_X_FORWARDED_FOR', '203.0.113.195', [], '10.1.1.1');
        $this->assertFalse($obj->get());
    }

    public function testHeaderIgnoredWithoutRemoteAddr()
    {
        $obj = new localProxyCheck('HTTP_X_FORWARDED_FOR', '203.0.113.195', ['10.0.0.0/8'], false);
        $this->assertFalse($obj->get());
    }

    public static function trustedProxyData()
    {
        return [
            [['10.1.1.1'], '10.1.1.1', true],
            [['10.1.1.1'], '10.1.1.2', false],
            [['10.0.0.0/8'], '10.255.0.1', true],
            [['10.0.0.0/8'], '11.0.0.1', false],
            [['192.168.4.0/23'], '192.168.5.200', true],
            [['192.168.4.0/23'], '192.168.6.1', false],
            [['0.0.0.0/0'], '8.8.8.8', true],
            [['2001:db8::/32'], '2001:db8:ffff::1', true],
            [['2001:db8::/32'], '2001:db9::1', false],
            [['::1'], '::1', true],
            [['10.0.0.0/8'], '::ffff:10.0.0.1', false],
            [['10.0.0.0/33'], '10.0.0.1', false],
            [['10.0.0.0/x'], '10.0.0.1', false],
            [['not-an-ip'], '10.0.0.1', false],
            [['10.0.0.0/8'], 'not-an-ip', false],
        ];
    }

    #[DataProvider('trustedProxyData')]
    public function testIsTrustedProxy($trusted, $ip, $expected)
    {
        $obj = new localProxyCheck('HTTP_X_FORWARDED_FOR', '', $trusted);
        $this->assertSame($expected, $obj->isTrusted($ip));
    }

    public function testTrustedProxiesFromConfig()
    {
        global $xoopsConfig;
        $saved = $xoopsConfig;
        $savedServer = $_SERVER;
        try {
            $xoopsConfig = [
                'proxy_env' => 'HTTP_X_FORWARDED_FOR',
                'proxy_trusted' => ' 10.0.0.0/8 , 192.0.2.1',
            ];
            $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.195, 192.0.2.1';
            $_SERVER['REMOTE_ADDR'] = '10.9.9.9';
            $this->assertSame('203.0.113.195', (new ProxyCheck())->get());

            $xoopsConfig['proxy_trusted'] = ['192.0.2.1'];
            $this->assertFalse((new ProxyCheck())->get());

            unset($xoopsConfig['proxy_trusted']);
            $this->assertFalse((new ProxyCheck())->get());
        } finally {
            $xoopsConfig = $saved;
            $_SERVER = $savedServer;
        }
    }
}
