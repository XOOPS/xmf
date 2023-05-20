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
 * Generate ULID
 *
 * @category  Xmf\Ulid
 * @package   Xmf
 * @author    Michael Beck <mambax7@gmail.com>
 * @copyright 2023 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class Ulid
{
    const ENCODING_CHARS = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    const ENCODING_LENGTH = 32;
    /**
     * Last generated timestamp.
     * @var int
     */
    private static $lastTime = 0;

    /**
     * Generate a new ULID.
     *
     * @return string The generated ULID.
     * @throws \Exception
     */

    public static function generate(bool $upperCase = true): string
    {
        $time      = (int)(microtime(true) * 1000);

        // If the current timestamp is equal or less than the last generated one,
        // increase it by one millisecond to ensure ULIDs are always sorted correctly.
        if ($time <= self::$lastTime) {
            $time = self::$lastTime + 1;
        }

        self::$lastTime = $time;

        $timeChars = self::encodeTime($time);
        $randChars = self::encodeRandomness();
        $ulid      = $timeChars . $randChars;

        return $upperCase ? strtoupper($ulid) : strtolower($ulid);
    }

    private static function encodeTime(int $time): string
    {
        $timeChars = '';
        for ($i = 0; $i < 10; $i++) {
            $mod       = $time % self::ENCODING_LENGTH;
            $timeChars = self::ENCODING_CHARS[$mod] . $timeChars;
            $time      = ($time - $mod) / self::ENCODING_LENGTH;
        }
        return $timeChars;
    }

    /**
     * @throws \Exception
     */
    private static function encodeRandomness(): string
    {
        $randomBytes = random_bytes(10); // 80 bits
        $randChars   = '';
        for ($i = 0; $i < 16; $i++) {
            $randValue = ord($randomBytes[$i % 10]);
            if ($i % 2 === 0) {
                $randValue >>= 3; // take the upper 5 bits
            } else {
                $randValue &= 31; // take the lower 5 bits
            }
            $randChars .= self::ENCODING_CHARS[$randValue];
        }
        return $randChars;
    }
}
