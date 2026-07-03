<?php

namespace {
    // Minimal global XoopsDatabaseFactory test double. TableLoad resolves its
    // connection through XoopsDatabaseFactory::getDatabaseConnection(); the XMF
    // test suite runs without a XOOPS core, so provide a settable stand-in.
    if (!class_exists('XoopsDatabaseFactory', false)) {
        class XoopsDatabaseFactory
        {
            /** @var object|null */
            public static $connection;

            /**
             * @return object|null
             */
            public static function getDatabaseConnection()
            {
                return self::$connection;
            }
        }
    }
}

namespace Xmf\Test\Database {

    use Xmf\Database\TableLoad;

    class TableLoadTest extends \PHPUnit\Framework\TestCase
    {
        protected function tearDown(): void
        {
            \XoopsDatabaseFactory::$connection = null;
        }

        public function testLoadTableFromArrayUsesExecWhenAvailable()
        {
            $db = new FakeLoadDatabaseWithExec();
            \XoopsDatabaseFactory::$connection = $db;

            $count = TableLoad::loadTableFromArray('demo', array(array('id' => 1), array('id' => 2)));

            $this->assertSame(2, $count);
            $this->assertSame(array('exec', 'exec'), $db->calls);
        }

        public function testLoadTableFromArrayFallsBackToQueryFWhenExecMissing()
        {
            $db = new FakeLoadDatabaseLegacy();
            \XoopsDatabaseFactory::$connection = $db;

            $count = TableLoad::loadTableFromArray('demo', array(array('id' => 1)));

            $this->assertSame(1, $count);
            $this->assertSame(array('queryF'), $db->calls);
        }

        public function testTruncateTableUsesExecAndReturnsAffectedRows()
        {
            $db = new FakeLoadDatabaseWithExec();
            $db->affected = 5;
            \XoopsDatabaseFactory::$connection = $db;

            $result = TableLoad::truncateTable('demo');

            $this->assertSame(array('exec'), $db->calls);
            $this->assertSame(5, $result);
        }

        public function testTruncateTableFallsBackToQueryFWhenExecMissing()
        {
            $db = new FakeLoadDatabaseLegacy();
            $db->affected = 3;
            \XoopsDatabaseFactory::$connection = $db;

            $result = TableLoad::truncateTable('demo');

            $this->assertSame(array('queryF'), $db->calls);
            $this->assertSame(3, $result);
        }
    }

    /**
     * Fake connection that provides exec() (modern core).
     */
    class FakeLoadDatabaseWithExec
    {
        /** @var string[] */
        public array $calls = array();
        public int $affected = 0;

        public function prefix(string $table): string
        {
            return 'xoops_' . $table;
        }

        public function quote(mixed $value): string
        {
            return "'" . addslashes((string) $value) . "'";
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

        public function getAffectedRows(): int
        {
            return $this->affected;
        }
    }

    /**
     * Fake connection modelling an older core that predates exec() (queryF() only).
     */
    class FakeLoadDatabaseLegacy
    {
        /** @var string[] */
        public array $calls = array();
        public int $affected = 0;

        public function prefix(string $table): string
        {
            return 'xoops_' . $table;
        }

        public function quote(mixed $value): string
        {
            return "'" . addslashes((string) $value) . "'";
        }

        public function queryF(string $sql): bool
        {
            $this->calls[] = 'queryF';
            return true;
        }

        public function getAffectedRows(): int
        {
            return $this->affected;
        }
    }
}
