<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Database;

/**
 * Specific exception for missing or invalid schema definitions used during migration.
 *
 * @category  Xmf\Database
 * @package   Xmf
 * @author    XOOPS Development Team <contact@xoops.org>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class SchemaDefinitionException extends \RuntimeException
{
    public static function forFile(string $file): self
    {
        return new self("No schema definition {$file}");
    }

    public static function forTable(string $tableName): self
    {
        return new self("No schema definition for table {$tableName}");
    }
}
