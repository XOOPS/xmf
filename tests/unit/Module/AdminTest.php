<?php

declare(strict_types=1);

namespace Xmf\Test\Module;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Xmf\Module\Admin;

/**
 * Regression coverage for Admin::menuIconPath().
 *
 * The method must return a module-RELATIVE icon path so that both 2.5 ModuleAdmin
 * renderers resolve it: renderMenuIndex() detects URLs but addNavigation() blindly
 * prefixes the module URL, so only a relative '../../' value works in both.
 *
 * isXng() keys off the global \Xoops class and the icon-set probing reads
 * XOOPS_ROOT_PATH, so each case runs in its own process.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AdminTest extends TestCase
{
    /** @var list<string> */
    private array $tmp = [];

    protected function tearDown(): void
    {
        foreach ($this->tmp as $dir) {
            self::rrmdir($dir);
        }
        $this->tmp = [];
    }

    public function testNonXngReturnsRelativeFrameworksPath(): void
    {
        // \Xoops is not defined here, so isXng() is false.
        self::assertSame(
            '../../Frameworks/moduleclasses/icons/32/home.png',
            Admin::menuIconPath('home.png')
        );
    }

    public function testNonXngEmptyImageReturnsRelativeBase(): void
    {
        self::assertSame(
            '../../Frameworks/moduleclasses/icons/32/',
            Admin::menuIconPath('')
        );
    }

    public function testLeadingSlashIsStripped(): void
    {
        self::assertSame(
            '../../Frameworks/moduleclasses/icons/32/home.png',
            Admin::menuIconPath('/home.png')
        );
    }

    public function testXngWithLegacyIconReturnsRelativeFrameworksPath(): void
    {
        $root = $this->makeRoot(files: ['Frameworks/moduleclasses/icons/32/home.png']);
        $this->bootXng($root);

        self::assertSame(
            '../../Frameworks/moduleclasses/icons/32/home.png',
            Admin::menuIconPath('home.png')
        );
    }

    public function testXngEmptyImageWithLegacyDirReturnsRelativeFrameworksBase(): void
    {
        $root = $this->makeRoot(dirs: ['Frameworks/moduleclasses/icons/32']);
        $this->bootXng($root);

        self::assertSame(
            '../../Frameworks/moduleclasses/icons/32/',
            Admin::menuIconPath('')
        );
    }

    public function testXngWithMediaOnlyIconReturnsRelativeMediaPath(): void
    {
        $root = $this->makeRoot(files: ['media/xoops/images/icons/32/home.png']);
        $this->bootXng($root);

        self::assertSame(
            '../../media/xoops/images/icons/32/home.png',
            Admin::menuIconPath('home.png')
        );
    }

    public function testXngWithNoIconsFallsBackToRelativeMediaPath(): void
    {
        $root = $this->makeRoot(); // neither icon set present
        $this->bootXng($root);

        self::assertSame(
            '../../media/xoops/images/icons/32/home.png',
            Admin::menuIconPath('home.png')
        );
    }

    /** Make isXng() true (define the global \Xoops class) and set XOOPS_ROOT_PATH. */
    private function bootXng(string $root): void
    {
        if (!class_exists('Xoops', false)) {
            require_once __DIR__ . '/fixtures/xoops_stub.php';
        }
        if (!defined('XOOPS_ROOT_PATH')) {
            define('XOOPS_ROOT_PATH', $root);
        }
    }

    /**
     * @param list<string> $dirs  directories to create under the fake webroot
     * @param list<string> $files files (with parent dirs) to create under it
     */
    private function makeRoot(array $dirs = [], array $files = []): string
    {
        $root = sys_get_temp_dir() . '/xmf_admin_' . uniqid('', true);
        mkdir($root, 0777, true);
        $this->tmp[] = $root;
        foreach ($dirs as $d) {
            mkdir($root . '/' . $d, 0777, true);
        }
        foreach ($files as $f) {
            $full = $root . '/' . $f;
            mkdir(dirname($full), 0777, true);
            file_put_contents($full, '');
        }

        return $root;
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? self::rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
