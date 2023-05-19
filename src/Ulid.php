<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

/**
 * Generate UUID
 *
 * @category  Xmf\Ulid
 * @package   Xmf
 * @author    Michael Beck <mambax7@gmail.com>
 * @copyright 2023 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class Ulid
{
    /**
     * Generate a new ULID.
     *
     * @return string The generated ULID.
     * @throws \Exception
     */
    public static function generate()
    {
        $time = microtime(true) * 1000;
        $timestamp = sprintf('%012x', (int)($time));
        $randomness = self::generateRandomness(true);

        return $timestamp . $randomness;
    }

    /**
     * Generate a random 80-bit randomness component for the ULID.
     *
     * @param bool $strongAlgorithm Determines if the algorithm used should be cryptographically strong.
     * @return string The generated randomness component.
     * @throws \Exception
     */
    private static function generateRandomness(bool $strongAlgorithm): string
    {
        if ($strongAlgorithm && function_exists('random_bytes')) {
            $bytes = random_bytes(10);
        } else {
            $bytes = '';
            for ($i = 0; $i < 10; $i++) {
                $bytes .= chr(random_int(0, 255));
            }
        }

        return bin2hex($bytes);
    }
}
