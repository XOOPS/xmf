<?php

declare(strict_types=1);

namespace Xmf\Test\Module\Helper;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Xmf\Module\Helper\GenericHelper;

/**
 * Concrete test double that sets the dirname directly, bypassing the
 * module-loading constructor - no reflection required.
 */
final class RelativeUrlTestHelper extends GenericHelper
{
    public function __construct($dirname)
    {
        $this->dirname = $dirname;
    }
}

/**
 * Coverage for GenericHelper::relativeUrl() (issue #39).
 *
 * relativeUrl() reads XOOPS_URL (a constant), so each XOOPS_URL value is
 * exercised in its own process.
 */
final class GenericHelperTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRelativeUrlAtWebRoot(): void
    {
        if (!defined('XOOPS_URL')) {
            define('XOOPS_URL', 'https://example.test');
        }
        $helper = new RelativeUrlTestHelper('mymodule');

        self::assertSame('/modules/mymodule/assets/app.js', $helper->relativeUrl('assets/app.js'));
        self::assertSame('/modules/mymodule/', $helper->relativeUrl());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRelativeUrlInSubdirectoryInstall(): void
    {
        if (!defined('XOOPS_URL')) {
            define('XOOPS_URL', 'https://example.test/xoops');
        }
        $helper = new RelativeUrlTestHelper('mymodule');

        self::assertSame('/xoops/modules/mymodule/assets/app.js', $helper->relativeUrl('assets/app.js'));
        // A leading slash on the input must not double up.
        self::assertSame('/xoops/modules/mymodule/assets/app.js', $helper->relativeUrl('/assets/app.js'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRelativeUrlStripsTrailingSlashOfBaseAndHasNoHost(): void
    {
        if (!defined('XOOPS_URL')) {
            define('XOOPS_URL', 'https://example.test/sub/');
        }
        $helper = new RelativeUrlTestHelper('mymodule');
        $result = $helper->relativeUrl('x.css');

        self::assertSame('/sub/modules/mymodule/x.css', $result);
        self::assertStringNotContainsString('://', $result);
    }
}
