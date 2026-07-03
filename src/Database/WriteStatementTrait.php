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
 * Xmf\Database\WriteStatementTrait
 *
 * Single source of truth for executing a write/DDL statement across XOOPS cores.
 *
 * exec() is the current API for writes/DDL (and, where Protector wraps the
 * connection, it is inspected by dblayertrap); older cores predate exec() and
 * only offer queryF(). Prefer exec() when the connection provides it and fall
 * back to queryF() otherwise, so the same code is correct on every core.
 *
 * @category  Xmf\Database\WriteStatementTrait
 * @package   Xmf
 * @author    XOOPS Project <www.xoops.org>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
trait WriteStatementTrait
{
    /**
     * Execute a write/DDL statement using the modern exec() where the connection
     * provides it, falling back to queryF() on cores that predate exec().
     *
     * @param \XoopsDatabase $db  database connection
     * @param string         $sql statement to execute
     *
     * @return \mysqli_result|bool
     */
    protected static function executeWrite($db, $sql)
    {
        return method_exists($db, 'exec') ? $db->exec($sql) : $db->queryF($sql);
    }
}
