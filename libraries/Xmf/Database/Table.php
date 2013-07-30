<?php
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
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id: Table.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

Xmf_Language::load('database', 'xmf');

class Xmf_Database_Table
{
    /**
     * @var XoopsDatabase
     */
    private $_db;

    /**
     * @var string $_name name of the table
     */
    private $_name;

    /**
     * @var string $_structure structure of the table
     */
    private $_structure;

    /**
     * @var array $_data containing valued of each records to be added
     */
    private $_data;

    /**
     * @var array $_alteredFields containing fields to be altered
     */
    private $_alteredFields;

    /**
     * @var array $_newFields containing new fields to be added
     */
    private $_newFields;

    /**
     * @var array $_dropedFields containing fields to be droped
     */
    private $_dropedFields;

    /**
     * @var array $_flagForDrop flag table to drop it
     */
    private $_flagForDrop = false;

    /**
     * @var array $_updatedFields containing fields which values will be updated
     */
    private $_updatedFields;

    /**
     * @var array $_updatedFields containing fields which values will be updated
     */ //felix
    private $_updatedWhere;

    var $_existingFieldsArray = false;

    /**
     * Constructor
     *
     * @param string $name name of the table
     */
    public function __construct($name)
    {
        $this->_db = XoopsDatabaseFactory::getDatabaseConnection();
        $this->_name = $name;
        $this->_data = array();
    }

    /**
     * Return the table name, prefixed with site table prefix
     *
     * @return string table name
     *
     */
    public function name()
    {
        return $this->_db->prefix($this->_name);
    }

    /**
     * Checks if the table already exists in the database
     *
     * @return bool TRUE if it exists, FALSE if not
     *
     */
    public function exists()
    {
        $ret = false;
        $result = $this->_db->query("SHOW TABLES FROM " . XOOPS_DB_NAME);
        while (list ($m_table) = $this->_db->fetchRow($result)) {
            if ($m_table == $this->name()) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    /**
     * @return array
     */
    public function getExistingFieldsArray()
    {
        $fields = array();
        $result = $this->_db->query("SHOW COLUMNS FROM " . $this->name());
        while ($existing_field = $this->_db->fetchArray($result)) {
            $fields[$existing_field['Field']] = $existing_field['Type'];
            if ($existing_field['Null'] != "YES") {
                $fields[$existing_field['Field']] .= " NOT NULL";
            }
            if ($existing_field['Extra']) {
                $fields[$existing_field['Field']] .= " " . $existing_field['Extra'];
            }
            if (!($existing_field['Default'] === NULL) && ($existing_field['Default'] || $existing_field['Default'] == '' || $existing_field['Default'] == 0)) {
                $fields[$existing_field['Field']] .= " default '" . $existing_field['Default'] . "'";
            }
        }
        return $fields;
    }

    /**
     * @param string $field
     * @return bool
     */
    public function fieldExists($field)
    {
        $existingFields = $this->getExistingFieldsArray();
        return isset ($existingFields[$field]);
    }

    /**
     * Set the table structure
     *
     * Example :
     *
     *         $table->setStructure("`transactionid` int(11) NOT NULL auto_increment,
     *                   `date` int(11) NOT NULL default '0',
     *                   `status` int(1) NOT NULL default '-1',
     *                   `itemid` int(11) NOT NULL default '0',
     *                   `uid` int(11) NOT NULL default '0',
     *                   `price` float NOT NULL default '0',
     *                   `currency` varchar(100) NOT NULL default '',
     *                   PRIMARY KEY  (`transactionid`)");
     *
     * @param  string $structure table structure
     *
     */
    public function setStructure($structure)
    {
        $this->_structure = $structure;
    }

    /**
     * Return the table structure
     *
     * @return string table structure
     *
     */
    public function getStructure()
    {
        return sprintf($this->_structure, $this->name());
    }

    /**
     * Add values of a record to be added
     *
     * @param string $data values of a record
     *
     */
    public function setData($data)
    {
        $this->_data[] = $data;
    }

    /**
     * Get the data array
     *
     * @return array containing the records values to be added
     *
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Use to insert data in a table
     *
     * @return bool true if success, false if an error occured
     *
     */
    public function addData()
    {
        $ret = true;
        foreach ($this->getData() as $data) {
            $query = sprintf('INSERT INTO %s VALUES (%s)', $this->name(), $data);
            $res = $this->_db->query($query);
            if (!$res) {
                $ret = false;
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_ADD_DATA_ERR, $this->name()) . "<br />";
            } else {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_ADD_DATA, $this->name()) . "<br />";
            }
        }
        return $ret;
    }

    /**
     * Add a field to be added
     *
     * @param string $name name of the field
     * @param string $properties properties of the field
     * @param bool $newname
     * @param bool $showerror
     * @return void
     */
    public function addAlteredField($name, $properties, $newname = false, $showerror = true)
    {
        $field['name'] = $name;
        $field['properties'] = $properties;
        $field['showerror'] = $showerror;
        $field['newname'] = $newname;
        $this->_alteredFields[] = $field;
    }

    /**
     * Invert values 0 to 1 and 1 to 0
     *
     * @param string $name name of the field
     * @param string $oldValue old propertie
     * @param string $newValue new propertie
     *
     */ //felix
    public function addUpdatedWhere($name, $newValue, $oldValue)
    {
        $field['name'] = $name;
        $field['value'] = $newValue;
        $field['where'] = $oldValue;
        $this->_updatedWhere[] = $field;
    }

    /**
     * Add new field of a record to be added
     *
     * @param string $name name of the field
     * @param string $properties properties of the field
     *
     */
    public function addNewField($name, $properties)
    {
        $field['name'] = $name;
        $field['properties'] = $properties;
        $this->_newFields[] = $field;
    }

    /**
     * Get fields that need to be altered
     *
     * @return array fields that need to be altered
     *
     */
    public function getAlteredFields()
    {
        return $this->_alteredFields;
    }

    /**
     * Add field for which the value will be updated
     *
     * @param string $name name of the field
     * @param string $value value to be set
     *
     */
    public function addUpdatedField($name, $value)
    {
        $field['name'] = $name;
        $field['value'] = $value;
        $this->_updatedFields[] = $field;
    }

    /**
     * Get new fields to be added
     *
     * @return array fields to be added
     *
     */
    public function getNewFields()
    {
        return $this->_newFields;
    }

    /**
     * Get fields which values need to be updated
     *
     * @return array fields which values need to be updated
     *
     */
    public function getUpdatedFields()
    {
        return $this->_updatedFields;
    }

    /**
     * Get fields which values need to be updated
     *
     * @return array fields which values need to be updated
     *
     */ //felix
    public function getUpdatedWhere()
    {
        return $this->_updatedWhere;
    }

    /**
     * Add values of a record to be added
     *
     * @param string $name name of the field
     *
     */
    public function addDropedField($name)
    {
        $this->_dropedFields[] = $name;
    }

    /**
     * Get fields that need to be droped
     *
     * @return array fields that need to be droped
     *
     */
    public function getDropedFields()
    {
        return $this->_dropedFields;
    }

    /**
     * Set the flag to drop the table
     *
     */
    public function setFlagForDrop()
    {
        $this->_flagForDrop = true;
    }

    /**
     * Use to create a table
     *
     * @return bool true if success, false if an error occured
     *
     */
    public function createTable()
    {
        $query = $this->getStructure();
        $query = "CREATE TABLE `" . $this->name() . "` (" . $query . ") ENGINE=MyISAM COMMENT='Xmf <www.xmf.com>'";
        //xoops_debug($query);
        $ret = $this->_db->query($query);
        if (!$ret) {
            echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_CREATE_TABLE_ERR, $this->name()) . " (" . $this->_db->error() . ")<br />";

        } else {
            echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_CREATE_TABLE, $this->name()) . "<br />";
        }
        return $ret;
    }

    /**
     * Use to drop a table
     *
     * @return bool true if success, false if an error occured
     *
     */
    public function dropTable()
    {
        $query = sprintf("DROP TABLE %s", $this->name());
        $ret = $this->_db->query($query);

        if (!$ret) {
            echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_DROP_TABLE_ERR, $this->name()) . " (" . $this->_db->error() . ")<br />";
            return false;
        } else {
            echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_DROP_TABLE, $this->name()) . "<br />";
            return true;
        }
    }

    /**
     * Use to alter a table
     *
     * @return bool true if success, false if an error occured
     *
     */
    public function alterTable()
    {
        $ret = true;
        foreach ($this->getAlteredFields() as $alteredField) {
            if (!$alteredField['newname']) {
                $alteredField['newname'] = $alteredField['name'];
            }
            $query = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` %s", $this->name(), $alteredField['name'], $alteredField['newname'], $alteredField['properties']);
            $ret = $ret && $this->_db->query($query);
            if ($alteredField['showerror']) {
                if (!$ret) {
                    echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_CHGFIELD_ERR, $alteredField['name'], $this->name()) . " (" . $this->_db->error() . ")<br />";
                } else {
                    echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_CHGFIELD, $alteredField['name'], $this->name()) . "<br />";
                }
            }
        }

        return $ret;
    }

    /**
     * Use to add new fileds in the table
     *
     * @return bool true if success, false if an error occured
     *
     */
    public function addNewFields()
    {
        $ret = true;
        foreach ($this->getNewFields() as $newField) {
            $query = sprintf("ALTER TABLE `%s` ADD `%s` %s", $this->name(), $newField['name'], $newField['properties']);
            $ret = $ret && $this->_db->query($query);
            if (!$ret) {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_NEWFIELD_ERR, $newField['name'], $this->name()) . "<br />";
            } else {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_NEWFIELD, $newField['name'], $this->name()) . "<br />";
            }
        }
        return $ret;
    }

    /**
     * Use to update fields values
     *
     * @return bool true if success, false if an error occured
     *
     */
    public function updateFieldsValues()
    {
        $ret = true;
        foreach ($this->getUpdatedFields() as $updatedField) {
            $query = sprintf("UPDATE %s SET %s = %s", $this->name(), $updatedField['name'], $updatedField['value']);
            $ret = $ret && $this->_db->query($query);
            if (!$ret) {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_UPDATE_TABLE_ERR, $this->name()) . " (" . $this->_db->error() . ")<br />";
            } else {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_UPDATE_TABLE, $this->name()) . "<br />";
            }
        }
        return $ret;
    }

    /**
     * Use to update fields values
     *
     * @return bool true if success, false if an error occured
     *
     */ //felix
    public function updateWhereValues()
    {
        $ret = true;
        foreach ($this->getUpdatedWhere() as $updatedWhere) {
            $query = sprintf("UPDATE %s SET %s = %s WHERE %s  %s", $this->name(), $updatedWhere['name'], $updatedWhere['value'], $updatedWhere['name'], $updatedWhere['where']);
            $ret = $ret && $this->_db->query($query);
            if (!$ret) {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_UPDATE_TABLE_ERR, $this->name()) . " (" . $this->_db->error() . ")<br />";
            } else {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_UPDATE_TABLE, $this->name()) . "<br />";
            }
        }
        return $ret;
    }

    /**
     * Use to drop fields
     *
     * @return bool true if success, false if an error occured
     *
     */
    function dropFields()
    {
        $ret = true;
        foreach ($this->getdropedFields() as $dropedField) {
            $query = sprintf("ALTER TABLE %s DROP %s", $this->name(), $dropedField);
            $ret = $ret && $this->_db->query($query);
            if (!$ret) {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_DROPFIELD_ERR, $dropedField, $this->name()) . " (" . $this->_db->error() . ")<br />";
            } else {
                echo "&nbsp;&nbsp;" . sprintf(_DB_XMF_MSG_DROPFIELD, $dropedField, $this->name()) . "<br />";
            }
        }
        return $ret;
    }

}