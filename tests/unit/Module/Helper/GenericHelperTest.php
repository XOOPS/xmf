<?php

declare(strict_types=1);

namespace Xmf\Test\Module\Helper;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Xmf\Module\Helper as ModuleHelper;
use Xmf\Module\Helper\AbstractHelper;

/**
 * Coverage for GenericHelper::relativeUrl() (issue #39).
 *
 * The helper's URL/path methods only depend on the resolved $dirname, so the
 * concrete Xmf\Module\Helper is created without its (module-loading) constructor
 * and the dirname is injected directly — no XOOPS runtime required.
 */
final class GenericHelperTest extends TestCase
{
    private function helperForDirname(string $dirname): ModuleHelper
    {
        $helper = (new \ReflectionClass(ModuleHelper::class))->newInstanceWithoutConstructor();
        $prop   = new ReflectionProperty(AbstractHelper::class, 'dirname');
        $prop->setAccessible(true);
        $prop->setValue($helper, $dirname);

        return $helper;
    }

    public function testRelativeUrlReturnsRootRelativePath(): void
    {
        $helper = $this->helperForDirname('mymodule');
        self::assertSame('/modules/mymodule/assets/app.js', $helper->relativeUrl('assets/app.js'));
    }

    public function testRelativeUrlWithNoArgumentReturnsModuleRoot(): void
    {
        $helper = $this->helperForDirname('mymodule');
        self::assertSame('/modules/mymodule/', $helper->relativeUrl());
    }

    public function testRelativeUrlHasNoSchemeOrHost(): void
    {
        $helper = $this->helperForDirname('mymodule');
        $result = $helper->relativeUrl('x.css');
        self::assertStringStartsWith('/modules/', $result);
        self::assertStringNotContainsString('://', $result);
    }
}
