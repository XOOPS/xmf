<?php
namespace Xmf\Test\Database;

use Xmf\Database\Tables;

class TablesTest extends \PHPUnit\Framework\TestCase
{
    public function testQuoteDefaultClauseNullReturnsEmpty()
    {
        $tables = new TestableTables();
        $result = $tables->callQuoteDefaultClause(null);
        $this->assertSame('', $result);
    }

    public function testQuoteDefaultClauseCurrentTimestamp()
    {
        $tables = new TestableTables();
        $result = $tables->callQuoteDefaultClause('CURRENT_TIMESTAMP');
        $this->assertSame(' DEFAULT CURRENT_TIMESTAMP ', $result);
    }

    public static function functionDefaultProvider(): array
    {
        return array(
            'mysql' => array('CURRENT_TIMESTAMP', ' DEFAULT CURRENT_TIMESTAMP '),
            'mariadb' => array('current_timestamp()', ' DEFAULT CURRENT_TIMESTAMP '),
            'empty parentheses' => array('CURRENT_TIMESTAMP()', ' DEFAULT CURRENT_TIMESTAMP '),
            'precision' => array('CURRENT_TIMESTAMP(6)', ' DEFAULT CURRENT_TIMESTAMP(6) '),
            'mariadb precision' => array('current_timestamp(3)', ' DEFAULT CURRENT_TIMESTAMP(3) '),
            'literal text' => array('current_timestamp_x', " DEFAULT 'current_timestamp_x' "),
            'trailing newline' => array("CURRENT_TIMESTAMP\n", " DEFAULT 'CURRENT_TIMESTAMP\n' "),
        );
    }

    /**
     * @dataProvider functionDefaultProvider
     */
    public function testQuoteDefaultClauseLeavesTimestampFunctionUnquoted(string $default, string $expected)
    {
        $tables = new TestableTables();
        $this->assertSame($expected, $tables->callQuoteDefaultClause($default));
    }

    public static function columnTypeDefaultProvider(): array
    {
        return array(
            'timestamp' => array('timestamp', 'current_timestamp()', ' DEFAULT CURRENT_TIMESTAMP '),
            'datetime precision' => array('datetime(6)', 'CURRENT_TIMESTAMP(6)', ' DEFAULT CURRENT_TIMESTAMP(6) '),
            'varchar keeps literal' => array('varchar(50)', 'current_timestamp()', " DEFAULT 'current_timestamp()' "),
            'varchar keeps literal upper' => array('varchar(50)', 'CURRENT_TIMESTAMP', " DEFAULT 'CURRENT_TIMESTAMP' "),
        );
    }

    /**
     * @dataProvider columnTypeDefaultProvider
     */
    public function testQuoteDefaultClauseUsesColumnType(string $type, string $default, string $expected)
    {
        $tables = new TestableTables();
        $this->assertSame($expected, $tables->callQuoteDefaultClause($default, $type));
    }

    public function testGetTableLoadsColumnsAndIndexes()
    {
        $tables = new CannedTables(array(
            array('INDEX_NAME' => 'PRIMARY', 'SEQ_IN_INDEX' => 1, 'NON_UNIQUE' => 0, 'COLUMN_NAME' => 'id', 'SUB_PART' => null),
            array('INDEX_NAME' => 'name', 'SEQ_IN_INDEX' => 1, 'NON_UNIQUE' => 1, 'COLUMN_NAME' => 'name', 'SUB_PART' => 10),
        ));

        $this->assertTrue($tables->useTable('demo'));
        $this->assertSame('', $tables->getLastError());
        $this->assertSame(
            array(
                'PRIMARY' => array('columns' => 'id', 'unique' => true),
                'name' => array('columns' => 'name (10)', 'unique' => false),
            ),
            $tables->dumpTables()['demo']['keys']
        );
    }

    public function testGetTableRefusesFunctionalIndex()
    {
        $tables = new CannedTables(array(
            array('INDEX_NAME' => 'PRIMARY', 'SEQ_IN_INDEX' => 1, 'NON_UNIQUE' => 0, 'COLUMN_NAME' => 'id', 'SUB_PART' => null),
            array('INDEX_NAME' => 'mixed', 'SEQ_IN_INDEX' => 1, 'NON_UNIQUE' => 1, 'COLUMN_NAME' => 'name', 'SUB_PART' => null),
            array('INDEX_NAME' => 'mixed', 'SEQ_IN_INDEX' => 2, 'NON_UNIQUE' => 1, 'COLUMN_NAME' => null, 'SUB_PART' => null),
        ));

        $this->assertFalse($tables->useTable('demo'));
        $this->assertSame(
            'Index mixed on table demo has a functional key part, which is not supported',
            $tables->getLastError()
        );
        $this->assertSame(array(), $tables->dumpTables());
    }

    public function testGetTableClearsPreviousErrorForMissingTable()
    {
        $tables = new CannedTables(array(), false);
        $tables->setLastError('stale error');

        $this->assertFalse($tables->useTable('demo'));
        $this->assertSame('', $tables->getLastError());
    }
}

class TestableTables extends Tables
{
    public function __construct()
    {
    }

    public function callQuoteDefaultClause($default, $columnType = null): string
    {
        return $this->quoteDefaultClause($default, $columnType);
    }
}

/**
 * Tables with canned INFORMATION_SCHEMA rows instead of a database.
 */
class CannedTables extends Tables
{
    /** @var array[] */
    private $results = array();

    public function __construct(array $indexRows, bool $exists = true)
    {
        $this->databaseName = 'test';
        $this->tables = array();
        $this->queue = array();
        $this->results = array(
            'TABLES' => $exists
                ? array(array('TABLE_NAME' => 'demo', 'ENGINE' => 'InnoDB', 'CHARACTER_SET_NAME' => 'utf8mb4'))
                : array(),
            'COLUMNS' => array(
                array('COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'int', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => 'auto_increment'),
                array('COLUMN_NAME' => 'name', 'COLUMN_TYPE' => 'varchar(50)', 'IS_NULLABLE' => 'YES', 'COLUMN_DEFAULT' => null, 'EXTRA' => ''),
            ),
            'STATISTICS' => $indexRows,
        );
    }

    public function setLastError(string $error): void
    {
        $this->lastError = $error;
        $this->lastErrNo = -1;
    }

    protected function name($table)
    {
        return $table;
    }

    protected function execSql($sql, $force = false)
    {
        foreach (array_keys($this->results) as $view) {
            if (strpos($sql, '`INFORMATION_SCHEMA`.`' . $view . '`') !== false) {
                return new \ArrayIterator($this->results[$view]);
            }
        }
        return false;
    }

    protected function fetch($result)
    {
        if (!$result->valid()) {
            return null;
        }
        $row = $result->current();
        $result->next();
        return $row;
    }
}
