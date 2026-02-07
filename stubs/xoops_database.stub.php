<?php

/**
 * PHPStan stub for XOOPS database classes
 */

class XoopsDatabase
{
    /**
     * @param string $sql
     * @return \mysqli_result|bool
     */
    public function query($sql) {}

    /**
     * @param string $sql
     * @return \mysqli_result|bool
     */
    public function queryF($sql) {}

    /**
     * @param string $table
     * @return string
     */
    public function prefix($table = '') {}

    /**
     * @param \mysqli_result $result
     * @return array|null
     */
    public function fetchArray($result) {}

    /**
     * @param \mysqli_result $result
     * @return array|null
     */
    public function fetchRow($result) {}

    /**
     * @return int
     */
    public function getInsertId() {}

    /**
     * @return int
     */
    public function getAffectedRows() {}

    /**
     * @param \mysqli_result $result
     * @return void
     */
    public function freeRecordSet($result) {}

    /**
     * @param string $str
     * @return string
     */
    public function quoteString($str) {}
}

class XoopsDatabaseFactory
{
    /**
     * @return XoopsDatabase
     */
    public static function getDatabaseConnection() {}
}
