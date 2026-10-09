<?php
namespace Xmf\Test\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\Database\Tables;

class TablesTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderTableCreateReturnsFalseForMalformedColumnDefinition()
    {
        $tables = new TestableTables();
        $tables->setTables(
            array(
                'demo' => array(
                    'options' => 'ENGINE=InnoDB',
                    'columns' => array(
                        array('attributes' => 'int NOT NULL'),
                    ),
                ),
            )
        );

        $this->assertFalse($tables->callRenderTableCreate('demo'));
    }

    public function testRenderTableCreateIncludesPrimaryAndUniqueKeys()
    {
        $tables = new TestableTables();
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'options' => 'ENGINE=InnoDB',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                        array('name' => 'title', 'attributes' => 'varchar(255) NOT NULL'),
                    ),
                    'keys' => array(
                        'PRIMARY' => array('columns' => '`id`'),
                        'idx_title' => array('columns' => '`title`', 'unique' => true),
                    ),
                ),
            )
        );

        $sql = $tables->callRenderTableCreate('demo', true);

        $this->assertStringContainsString('CREATE TABLE `xoops_demo`', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql);
        $this->assertStringContainsString('UNIQUE KEY idx_title (`title`)', $sql);
    }

    public function testInsertSkipsMalformedColumnDefinitionWithWarning()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                        array('attributes' => 'varchar(255) NOT NULL'),
                    ),
                ),
            )
        );

        $warning = $this->captureWarning(static function () use ($tables): void {
            $tables->insert('demo', array('id' => 7));
        });

        $this->assertStringContainsString('Skipping malformed column definition in Xmf\Database\Tables::insert', $warning);
        $this->assertSame("INSERT INTO `xoops_demo` (`id`) VALUES('7')", $tables->dumpQueue()[0]);
    }

    public function testUpdateSkipsMalformedColumnDefinitionWithWarning()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'title', 'attributes' => 'varchar(255) NOT NULL'),
                        array('attributes' => 'varchar(255) NOT NULL'),
                    ),
                ),
            )
        );

        $warning = $this->captureWarning(static function () use ($tables): void {
            $tables->update('demo', array('title' => 'Updated'), 'WHERE id = 1');
        });

        $this->assertStringContainsString('Skipping malformed column definition in Xmf\Database\Tables::update', $warning);
        $this->assertSame("UPDATE `xoops_demo` SET `title` = 'Updated' WHERE id = 1", $tables->dumpQueue()[0]);
    }

    public function testInsertReturnsFalseWhenNoValidColumnsMatch()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                    ),
                ),
            )
        );

        $this->assertFalse($tables->insert('demo', array('title' => 'Ignored')));
        $this->assertSame('No valid columns supplied for insert', $tables->getLastError());
        $this->assertSame(-1, $tables->getLastErrNo());
        $this->assertSame(array(), $tables->dumpQueue());
    }

    public function testUpdateReturnsFalseWhenNoValidColumnsMatch()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                    ),
                ),
            )
        );

        $this->assertFalse($tables->update('demo', array('title' => 'Ignored'), 'WHERE id = 1'));
        $this->assertSame('No valid columns supplied for update', $tables->getLastError());
        $this->assertSame(-1, $tables->getLastErrNo());
        $this->assertSame(array(), $tables->dumpQueue());
    }

    public function testQuoteDefaultClauseEscapesSingleQuotes()
    {
        $tables = new TestableTables();
        $result = $tables->callQuoteDefaultClause("O'Reilly");
        $this->assertSame(" DEFAULT 'O''Reilly' ", $result);
    }

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

    public function testExecSqlRoutesSelectToQuery()
    {
        $db = new RoutingFakeDatabase();
        $tables = new TestableTables();
        $tables->setDb($db);

        $tables->callExecSql('SELECT * FROM `demo`');

        $this->assertSame(array('query'), $db->calls);
    }

    public function testExecSqlRoutesWriteToExecWhenAvailable()
    {
        $db = new RoutingFakeDatabase();
        $tables = new TestableTables();
        $tables->setDb($db);

        $tables->callExecSql('CREATE TABLE `demo` (`id` INT)');

        $this->assertSame(array('exec'), $db->calls);
    }

    public function testExecSqlForcedSelectTakesWritePath()
    {
        $db = new RoutingFakeDatabase();
        $tables = new TestableTables();
        $tables->setDb($db);

        $tables->callExecSql('SELECT 1', true);

        $this->assertSame(array('exec'), $db->calls);
    }

    public function testExecSqlFallsBackToQueryFWhenExecMissing()
    {
        $db = new LegacyFakeDatabase();
        $tables = new TestableTables();
        $tables->setDb($db);

        $tables->callExecSql('TRUNCATE TABLE `demo`');

        $this->assertSame(array('queryF'), $db->calls);
    }

    private function captureWarning(callable $callback): string
    {
        $warning = '';

        set_error_handler(static function (int $errno, string $errstr) use (&$warning): bool {
            $warning = $errstr;
            return true;
        });

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $warning;
    }

    public static function functionDefaultProvider(): array
    {
        return array(
            'mysql' => array('CURRENT_TIMESTAMP', ' DEFAULT CURRENT_TIMESTAMP '),
            'mariadb' => array('current_timestamp()', ' DEFAULT CURRENT_TIMESTAMP() '),
            'precision' => array('CURRENT_TIMESTAMP(6)', ' DEFAULT CURRENT_TIMESTAMP(6) '),
            'mariadb precision' => array('current_timestamp(3)', ' DEFAULT CURRENT_TIMESTAMP(3) '),
            'literal text' => array('current_timestamp_x', " DEFAULT 'current_timestamp_x' "),
            'trailing newline' => array("CURRENT_TIMESTAMP\n", " DEFAULT 'CURRENT_TIMESTAMP\n' "),
        );
    }

    #[DataProvider('functionDefaultProvider')]
    public function testQuoteDefaultClauseLeavesTimestampFunctionUnquoted(string $default, string $expected)
    {
        $tables = new TestableTables();
        $this->assertSame($expected, $tables->callQuoteDefaultClause($default));
    }

    public static function columnTypeDefaultProvider(): array
    {
        return array(
            'timestamp' => array('timestamp', 'current_timestamp()', ' DEFAULT CURRENT_TIMESTAMP() '),
            'datetime precision' => array('datetime(6)', 'CURRENT_TIMESTAMP(6)', ' DEFAULT CURRENT_TIMESTAMP(6) '),
            'varchar keeps literal' => array('varchar(50)', 'current_timestamp()', " DEFAULT 'current_timestamp()' "),
            'varchar keeps literal upper' => array('varchar(50)', 'CURRENT_TIMESTAMP', " DEFAULT 'CURRENT_TIMESTAMP' "),
        );
    }

    #[DataProvider('columnTypeDefaultProvider')]
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

    public function setDb(object $db): void
    {
        $this->db = $db;
    }

    public function setTables(array $tables): void
    {
        $this->tables = $tables;
        $this->queue = array();
    }

    public function callRenderTableCreate(string $table, bool $prefixed = false): string|false
    {
        return $this->renderTableCreate($table, $prefixed);
    }

    public function callQuoteDefaultClause(?string $default, ?string $columnType = null): string
    {
        return $this->quoteDefaultClause($default, $columnType);
    }

    /**
     * @return \mysqli_result|bool
     */
    public function callExecSql(string $sql, bool $force = false)
    {
        return $this->execSql($sql, $force);
    }
}

class FakeTablesDatabase
{
    public function quote(mixed $value): string
    {
        return "'" . addslashes((string) $value) . "'";
    }
}

/**
 * Fake connection that records which execution method was used and, like a
 * modern core, provides exec(). Used to assert execSql()'s read/write routing.
 */
class RoutingFakeDatabase
{
    /** @var string[] */
    public array $calls = array();

    public function query(string $sql): bool
    {
        $this->calls[] = 'query';
        return true;
    }

    public function exec(string $sql): bool
    {
        $this->calls[] = 'exec';
        return true;
    }

    public function queryF(string $sql): bool
    {
        $this->calls[] = 'queryF';
        return true;
    }

    public function error(): string
    {
        return '';
    }

    public function errno(): int
    {
        return 0;
    }
}

/**
 * Fake connection modelling an older core that predates exec() (only queryF()).
 */
class LegacyFakeDatabase
{
    /** @var string[] */
    public array $calls = array();

    public function query(string $sql): bool
    {
        $this->calls[] = 'query';
        return true;
    }

    public function queryF(string $sql): bool
    {
        $this->calls[] = 'queryF';
        return true;
    }

    public function error(): string
    {
        return '';
    }

    public function errno(): int
    {
        return 0;
    }
}

/**
 * Tables with canned INFORMATION_SCHEMA rows instead of a database.
 */
class CannedTables extends Tables
{
    private array $results = array();

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
