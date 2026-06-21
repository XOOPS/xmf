<?php

declare(strict_types=1);

namespace Xmf\Test;

use PHPUnit\Framework\TestCase;
use Xmf\Metagen;

/**
 * Coverage for the multilingual normalization hook added for issue #86.
 */
final class MetagenMultilingualTest extends TestCase
{
    protected function tearDown(): void
    {
        // The normalizer is global static state; reset it between tests.
        Metagen::setMultilingualNormalizer(null);
    }

    public function testRegisteredNormalizerSelectsActiveLanguage(): void
    {
        // Stand in for xlanguage: select the [en] segment from the markup.
        Metagen::setMultilingualNormalizer(static function (string $text): string {
            return preg_match('#\[en\](.*?)\[/en\]#s', $text, $m) ? $m[1] : $text;
        });

        $desc = Metagen::generateDescription('[en]Hello world[/en][fr]Bonjour[/fr]', 10);

        self::assertStringContainsString('Hello', $desc);
        self::assertStringNotContainsString('Bonjour', $desc);
        self::assertStringNotContainsString('[en]', $desc);
    }

    public function testNoNormalizerIsNoOpAndDoesNotCrashOnMarkup(): void
    {
        Metagen::setMultilingualNormalizer(null);

        // With no normalizer registered the markup is left intact, but
        // description generation must not error.
        $desc = Metagen::generateDescription('[en]Hello world[/en]', 10);

        self::assertIsString($desc);
        self::assertStringContainsString('Hello', $desc);
    }
}
