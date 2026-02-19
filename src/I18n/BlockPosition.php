<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

declare(strict_types=1);

namespace Xmf\I18n;

/**
 * Block positioning logic for RTL/LTR layouts.
 * Framework-agnostic - works with any CMS that has sidebar concepts.
 *
 * @category  Xmf\I18n\BlockPosition
 * @package   Xmf
 * @author    MAMBA <mambax7@gmail.com>
 * @copyright 2000-2025 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class BlockPosition
{
    /**
     * Map a side integer to logical class based on convention.
     *
     * @param int $side       Physical side (convention: 1=left, 0=right in LTR context)
     * @param int $leftValue  What integer represents "left" in your CMS (default: 1)
     * @param int $rightValue What integer represents "right" in your CMS (default: 0)
     *
     * @return string 'start', 'end', or 'center'
     */
    public static function toLogical(
        int $side,
        int $leftValue = 1,
        int $rightValue = 0
    ): string {
        if ($side === $leftValue) {
            return 'start';
        }
        if ($side === $rightValue) {
            return 'end';
        }
        return 'center';
    }

    /**
     * Get CSS class for a block position.
     *
     * @param int $side       Physical side value from database
     * @param int $leftValue  What represents "left" (default: 1)
     * @param int $rightValue What represents "right" (default: 0)
     *
     * @return string CSS class: 'block-start', 'block-end', or 'block-center'
     */
    public static function toCssClass(
        int $side,
        int $leftValue = 1,
        int $rightValue = 0
    ): string {
        $logical = self::toLogical($side, $leftValue, $rightValue);
        return "block-{$logical}";
    }

    /**
     * Get appropriate side value for "start" position in given direction.
     *
     * @param string|null $direction 'ltr' or 'rtl'
     * @param int         $leftValue  What represents "left" (default: 1)
     * @param int         $rightValue What represents "right" (default: 0)
     *
     * @return int Side value for start position
     */
    public static function getStartValue(
        ?string $direction = null,
        int $leftValue = 1,
        int $rightValue = 0
    ): int {
        $dir = $direction ?? Direction::dir();
        return ($dir === Direction::RTL) ? $rightValue : $leftValue;
    }

    /**
     * Get appropriate side value for "end" position in given direction.
     *
     * @param string|null $direction 'ltr' or 'rtl'
     * @param int         $leftValue  What represents "left" (default: 1)
     * @param int         $rightValue What represents "right" (default: 0)
     *
     * @return int Side value for end position
     */
    public static function getEndValue(
        ?string $direction = null,
        int $leftValue = 1,
        int $rightValue = 0
    ): int {
        $dir = $direction ?? Direction::dir();
        return ($dir === Direction::RTL) ? $leftValue : $rightValue;
    }
}
