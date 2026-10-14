<?php
namespace Xmf\Test\Database;

use Xmf\Database\Migrate;

class MigrateTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCurrentSchemaSkipsMissingTables()
    {
        $handler = new LoadingTableHandler(array('present' => array('columns' => array())), array());
        $migrate = new TestableMigrate();
        $migrate->setModuleTables(array('present', 'missing'));
        $migrate->setTableHandler($handler);

        $this->assertSame(array('present' => array('columns' => array())), $migrate->getCurrentSchema());
        $this->assertSame(array(), $handler->added);
    }

    public function testGetCurrentSchemaThrowsWhenTableCannotBeRead()
    {
        $handler = new LoadingTableHandler(array(), array('broken' => 'Index idx on table broken has a functional key part'));
        $migrate = new TestableMigrate();
        $migrate->setModuleTables(array('broken'));
        $migrate->setTableHandler($handler);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot read table broken: Index idx on table broken has a functional key part');

        $migrate->getCurrentSchema();
    }

    public function testGetSynchronizeDdlThrowsInsteadOfTreatingUnreadableTableAsMissing()
    {
        $handler = new LoadingTableHandler(array(), array('broken' => 'read failed'));
        $migrate = new TestableMigrate();
        $migrate->setModuleTables(array('broken'));
        $migrate->setTableHandler($handler);
        $migrate->setTargetDefinitions(array(
            'broken' => array('options' => 'ENGINE=InnoDB', 'columns' => array()),
        ));

        try {
            $migrate->getSynchronizeDDL();
            $this->fail('Expected a RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Cannot read table broken: read failed', $e->getMessage());
        }
        $this->assertSame(array(), $handler->added);
    }
}

class TestableMigrate extends Migrate
{
    public function __construct()
    {
    }

    public function setTargetDefinitions(array $targetDefinitions): void
    {
        $this->targetDefinitions = $targetDefinitions;
    }

    public function setModuleTables(array $moduleTables): void
    {
        $this->moduleTables = $moduleTables;
    }

    public function setTableHandler(object $tableHandler): void
    {
        $this->tableHandler = $tableHandler;
    }
}

/**
 * Table handler double: useTable() succeeds for $existing tables and reports
 * $errors[table] through getLastError() for the others.
 */
class LoadingTableHandler
{
    /** @var string[] */
    public $added = array();
    /** @var array[] */
    private $loaded = array();
    /** @var string */
    private $lastError = '';
    /** @var array[] */
    private $existing;
    /** @var string[] */
    private $errors;

    public function __construct(array $existing, array $errors)
    {
        $this->existing = $existing;
        $this->errors = $errors;
    }

    public function useTable(string $tableName): bool
    {
        $this->lastError = $this->errors[$tableName] ?? '';
        if (isset($this->existing[$tableName])) {
            $this->loaded[$tableName] = $this->existing[$tableName];
            return true;
        }
        return false;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function addTable(string $tableName): bool
    {
        $this->added[] = $tableName;
        return true;
    }

    public function dumpTables(): array
    {
        return $this->loaded;
    }

    public function dumpQueue(): array
    {
        return array();
    }
}
