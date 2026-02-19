<?php

/**
 * PHPStan stub for XOOPS criteria classes
 */

class CriteriaElement
{
    /**
     * @return string
     */
    public function render() {}

    /**
     * @return string
     */
    public function renderWhere() {}

    /**
     * @param string $sort
     * @return void
     */
    public function setSort($sort) {}

    /**
     * @param string $order
     * @return void
     */
    public function setOrder($order) {}

    /**
     * @param int $limit
     * @return void
     */
    public function setLimit($limit) {}

    /**
     * @param int $start
     * @return void
     */
    public function setStart($start) {}
}

class CriteriaCompo extends CriteriaElement
{
    /**
     * @param CriteriaElement|null $ele
     */
    public function __construct($ele = null) {}

    /**
     * @param CriteriaElement $criteria
     * @param string $condition
     * @return void
     */
    public function add($criteria, $condition = 'AND') {}
}

class Criteria extends CriteriaElement
{
    /**
     * @param string $column
     * @param mixed $value
     * @param string $operator
     * @param string $prefix
     * @param string $function
     */
    public function __construct($column, $value = '', $operator = '=', $prefix = '', $function = '') {}
}
