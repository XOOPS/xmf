<?php

namespace Xmf\Database;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         Xmf
 * @since           0.1
 * @author          Richard Griffith
 */

/**
 * Xmf\Database\Migrate
 *
 * inspired by Yii CDbMigration
 *
 * Build a work queue of database changes needed to implement new and
 * changed tables. Define table(s) you are dealing with and any desired
 * change(s). If the changes are already in place (i.e. the new column
 * already exists) no work is added. Then queueExecute() to process the
 * whole set.
 */
class Migrate
{
    /**
     * for add/alter column position
     */
    const POSITION_FIRST = 1;

    /**
     * @var XoopsDatabase
     */
    private $_db;

    /**
     * @var Tables
     */
    private $_tables;

    /**
     * @var Work queue
     */
    private $_queue;

    /**
     * @var string last error
     */
    public $lastError;

    /**
     * @var string last error
     */
    public $lastErrNo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        if (!defined('XMF_EXEC')) { die('Xmf was not detected'); }
        \Xmf\Language::load('database', 'xmf');

        $this->_db = \XoopsDatabaseFactory::getDatabaseConnection();
        $this->queueReset();
    }

    /**
     * Return a table name, prefixed with site table prefix
     *
     * @param string $table table name to contain prefix
     *
     * @return string table name
     *
     */
    public function name($table)
    {
        return $this->_db->prefix($table);
    }

    /**
     * Add new column for table to the work queue
     *
     * @param string $table    table to contain the column
     * @param string $column   name of column to add
     * @param mixed  $position FIRST, string of column name to add new
     *                          column after, or null for natural append
     * @param array $attributes column_definition
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function addColumn($table, $column, $position, $attributes)
    {
        $columnDef=array('name'=>$column, 'position'=>$position, 'attributes'=>$attributes);

        // Find table def.
        if (isset($this->_tables[$table])) {
            $tableDef = &$this->_tables[$table];
            // Is this on a table we are adding?
            if(isset($tableDef['create']) && $tableDef['create']) {
                switch($position) {
                    case Migrate::POSITION_FIRST:
                        array_unshift($tableDef['columns'],$columnDef);
                        break;
                    case '':
                    case null:
                    case false:
                        array_push($tableDef['columns'],$columnDef);
                        break;
                    default:
                        // should be a column name to add after
                        // loop thru and find that column
                        $i=0;
                        foreach($tableDef['columns'] as $col) {
                            ++$i;
                            if(strcasecmp($col['name'],$position)==0) {
                                array_splice($tableDef['columns'],$i,0,array($columnDef));
                                break;
                            }
                        }
                }
                return true;
            }
            else {
                foreach($tableDef['columns'] as $col) {
                    if(strcasecmp($col['name'],$column)==0) {
                        return true;
                    }
                }
                switch($position) {
                    case Migrate::POSITION_FIRST:
                        $pos='FIRST';
                        break;
                    case '':
                    case null:
                    case false:
                        $pos='';
                        break;
                    default:
                        $pos='AFTER `$position`';
                }
                $this->_queue[]="ALTER TABLE `{$tableDef['attributes']['TABLE_NAME']}` ADD COLUMN {$column} {$columnDef['attributes']} {$pos} ";

            }
        }
        else { // no table established
            $this->lastError = 'Table is not defined';
            $this->lastErrNo = -1;

            return false;
        }

        return true; // exists or is added to queue
    }

    /**
     * Add new primary key definition for table to work queue
     *
     * @param string $table  table
     * @param string $column column or comma separated list of columns
     *                       to use a primary key
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function addPrimaryKey($table, $column)
    {
        return false;
    }

    /**
     * Load table schema from database, or starts new empty schema if
     * table doesn't exist
     *
     * @param string $table table
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function addTable($table)
    {
        if(isset($this->_tables[$table])) return true;
        $tableDef=$this->_getTable($table);
        if (is_array($tableDef)) {
            $this->_tables[$table] = $tableDef;

            return true;
        } else {
            if ($tableDef===true) {
                $tableDef=array();
                $tableDef['attributes'] = array(
                      'TABLE_NAME' => $this->_db->prefix($table)
                    , 'ENGINE' => ''
                    , 'CHARACTER_SET_NAME' => '');
                $tableDef['create'] = true;
                $this->_tables[$table] = $tableDef;

                $this->_queue[]=array('createtable'=>$table);

                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Add alter column operation to the work queue
     *
     * @param string $table    table containing the column
     * @param string $column   column to add
     * @param mixed  $position FIRST, string of column name to add new
     *                          column after, or null for no change
     * @param array $attributes column_definition
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function alterColumn($table, $column, $position, $attributes)
    {
        $columnDef=array('position'=>$position, 'attributes'=>$attributes);

        // Find table def. Is this on a table we are adding?
        if (isset($this->_queue['createtables'][$table])) {
            $this->_queue['createtables'][$table]['columns'][$column]=$columnDef;
        } else {
            // is this an existing table?
            if (isset($this->_tables[$table])) {
                $tableDef = &$this->_tables[$table];
                // skip if this column is already on the table
                if (!isset($tableDef['columns'][$column])) {
                    $this->_queue['altercolumns'][$table][$column]=$columnDef;
                }
            } else {
                $this->lastError = 'Table is not defined';
                $this->lastErrNo = -1;

                return false;
            }

        }

        return true; // exists or is added to queue
    }

    /**
     * Loads table schema from database, and adds newTable with that
     * schema to the queue
     *
     * @param string $table    existing table
     * @param string $newTable new table
     * @param bool   $withData true to copy data, false for schema only
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function copyTable($table, $newTable, $withData=false)
    {
        if(isset($this->_tables[$newTable])) return true;
        $tableDef=$this->_getTable($table);
        $copy=$this->name($newTable);
        $original=$this->name($table);

        if (is_array($tableDef)) {
            $tableDef['attributes']['TABLE_NAME']=$copy;
            if($withData) {
                $this->_queue[] = "CREATE TABLE {$copy} LIKE {$original} ;";
                $this->_queue[] = "INSERT INTO {$copy} SELECT * FROM {$original} ;";
            }
            else {
                $tableDef['create'] = true;
                $this->_queue[]=array('createtable'=>$newTable);
            }
            $this->_tables[$newTable]=$tableDef;

            return true;
        } else {
            return false;
        }

    }

    /**
     * Add new index definition for table to work queue
     *
     * @param string $name   name of index to add
     * @param string $table  table indexed
     * @param string $column column or comma separated list of columns
     *                        to use the key
     * @param bool $unique true if index is to be unique
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function createIndex($name, $table, $column, $unique=false)
    {
        return false;
    }

    /**
     * Add drop column operation to the work queue
     *
     * @param string $table  table containing the column
     * @param string $column column to drop
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropColumn($table, $column)
    {
        return false;
    }

    /**
     * Add drop index operation to the work queue
     *
     * @param string $name  name of index to drop
     * @param string $table table indexed

     * @return bool true if no errors, false if errors encountered
     */
    public function dropIndex(string $name, string $table)
    {
        return false;
    }

    /**
     * Add drop for all (non-PRIMARY) keys for a table to the work
     * queue. This can be used to clean up indexes with automatic names.
     *
     * @param string $table table indexed
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropIndexes(string $table)
    {
        return false;
    }

    /**
     * Add drop of PRIMARY key for a table to the work queue
     *
     * @param string $table table
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropPrimaryKey(string $table)
    {
        return false;
    }

    /**
     * Add drop of table to the work queue
     *
     * @param string $table table
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropTable(string $table)
    {
        return false;
    }

    /**
     * Add rename column operation to the work queue
     *
     * @param string $table   table containing the column
     * @param string $column  column to rename
     * @param string $newName new column name
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function renameColumn($table, $column, $newName)
    {
        return false;
    }

    /**
     * Add rename table operation to the work queue
     *
     * @param string $table   table
     * @param string $newName new table name
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function renameTable($table, $newName)
    {
        return false;
    }

    /**
     * Add alter table table_options (ENGINE, DEFAULT CHARSET, etc.)
     * to work queue
     *
     * @param string $table   table
     * @param array  $options table_options
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function setTableOptions($table, $options)
    {
        // ENGINE=MEMORY DEFAULT CHARSET=utf8;
        return false;
    }


    /**
     * Clear the work queue
     */
    public function queueReset()
    {
        $this->_tables = array();
        $this->_queue  = array();
    }

    /**
     * Execute the work queue
     */
    public function queueExecute()
    {
    }


    /**
     * Create and execute a DELETE SQL statement.
     *
     * @param string $table    table
     * @param mixed  $criteria string where clause or object criteria
     */
    public function delete($table, $criteria)
    {
    }

    /** Creates and executes an INSERT SQL statement.
     *
     * @param string $table   table
     * @param array  $columns array of 'column'=>'value' entries
     */
    public function insert(string $table, array $columns)
    {
    }

    /**
     * Creates and executes an UPDATE SQL statement.
     *
     * @param string $table    table
     * @param array  $columns  array of 'column'=>'value' entries
     * @param mixed  $criteria string where clause or object criteria
     */
    public function update(string $table, array $columns, $criteria)
    {
    }

    /**
     * Empty a table
     *
     * @param string $table table
     */
    public function truncate(string $table)
    {
    }



    /** return SQL to create the table
     *
     * @param string $table table
     */
    public function renderTableCreate(string $table)
    {
    }

    /**
     * return a basic XoopsObject definition for the table
     *
     * @param string $table table
     */
    public function renderTableObject(string $table)
    {
    }


    /**
     * get table definition from INFORMATION_SCHEMA
     *
     * @param string $sql   SQL statement to execute
     * @param bool   $force true to use queryF
     *
     * @return mixed result resouce if no error,
     *               true if no error but no result
     *               false if error encountered.
     *               Any error message is in $this->lastError;
     */
    private function & _execSql($sql,$force=false)
    {
        if ($force) {
            $result = $this->_db->queryF($sql);
        } else {
            $result = $this->_db->query($sql);
        }

        if (!$result) {
            $this->lastError = $this->_db->error();
            $this->lastErrNo = $this->_db->errno();
        }

        return $result;

    }

    /**
     * fetch the next row of a result set
     *
     * @param resource $result as returned by query
     *
     * @return bool true if no errors and table is loaded, false if
     *               error presented. Error message in $this->lastError;
     */
    private function _fetch(&$result)
    {
        return $this->_db->fetchArray($result);
    }

    /**
     * get table definition from INFORMATION_SCHEMA
     *
     * @param string $table table
     *
     * @return bool true if no errors and table is loaded, false if
     *               error presented. Error message in $this->lastError;
     */
    private function _getTable($table)
    {

        $tableDef = array();

        $sql  = 'SELECT TABLE_NAME, ENGINE, CHARACTER_SET_NAME ';
        $sql .= ' FROM `INFORMATION_SCHEMA`.`TABLES` t, ';
        $sql .= ' `INFORMATION_SCHEMA`.`CHARACTER_SETS` c ';
        $sql .= ' WHERE t.TABLE_SCHEMA = \'' . XOOPS_DB_NAME . '\' ';
        $sql .= ' AND t.TABLE_NAME = \'' . $this->name($table) . '\' ';
        $sql .= ' AND t.TABLE_COLLATION  = c.DEFAULT_COLLATE_NAME ';
/*
SELECT TABLE_NAME, ENGINE, CHARACTER_SET_NAME
FROM `INFORMATION_SCHEMA`.`TABLES` t,
`INFORMATION_SCHEMA`.`CHARACTER_SETS` c
WHERE
t.TABLE_COLLATION  = c.DEFAULT_COLLATE_NAME
AND t.TABLE_SCHEMA = 'xoopstest'
AND t.TABLE_NAME = 'xtrc_config'
*/
        $result = $this->_execSql($sql);
        if(!$result) return false;

        $tableDef['attributes'] = $this->_fetch($result);
        if(empty($tableDef['attributes'])) return true;

        $sql  = 'SELECT * ';
        $sql .= ' FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
        $sql .= ' WHERE TABLE_SCHEMA = \'' . XOOPS_DB_NAME . '\' ';
        $sql .= ' AND TABLE_NAME = \'' . $this->name($table) . '\' ';
        $sql .= ' ORDER BY `ORDINAL_POSITION` ';

        $result = $this->_execSql($sql);

        while ($column=$this->_fetch($result)) {
            $attributes = ' ' . $column['COLUMN_TYPE'] . ' '
                . (($column['IS_NULLABLE'] == 'NO') ? ' NOT NULL ' : '' )
                . (($column['COLUMN_DEFAULT'] === null) ? '' :
                        " DEFAULT '". $column['COLUMN_DEFAULT'] . "' ")
                . $column['EXTRA'];

            $columnDef=array('name'=>$column['COLUMN_NAME'], 'position'=>$column['ORDINAL_POSITION'], 'attributes'=>$attributes);

//            $tableDef['columnorder'][$column['ORDINAL_POSITION']] = $column['COLUMN_NAME'];
//            $tableDef['columns'][$column['COLUMN_NAME']] = $columnDef;
            $tableDef['columns'][] = $columnDef;
        };

        $sql  = 'SELECT `INDEX_NAME`, `SEQ_IN_INDEX`, `NON_UNIQUE`, ';
        $sql .= ' `COLUMN_NAME`, `SUB_PART` ';
        $sql .= ' FROM `INFORMATION_SCHEMA`.`STATISTICS` ';
        $sql .= ' WHERE TABLE_SCHEMA = \'' . XOOPS_DB_NAME . '\' ';
        $sql .= ' AND TABLE_NAME = \'' . $this->name($table) . '\' ';
        $sql .= ' ORDER BY `INDEX_NAME`, `SEQ_IN_INDEX` ';
/*
SELECT `INDEX_NAME`, `SEQ_IN_INDEX`, `NON_UNIQUE`, `COLUMN_NAME`, `COLLATION`, `SUB_PART`

FROM `STATISTICS`

WHERE `TABLE_NAME` = 'xtrc_gwiki_pages'

order by `INDEX_NAME`, `SEQ_IN_INDEX`
*/
        $result = $this->_execSql($sql);

        while ($key=$this->_fetch($result)) {
            $tableDef['keys'][$key['INDEX_NAME']][$key['SEQ_IN_INDEX']] = $key;
        };

        return $tableDef;

    }

    // for development debugging only
    public function dumpTables() { return $this->_tables; }
    public function dumpQueue()  { return $this->_queue; }

}
