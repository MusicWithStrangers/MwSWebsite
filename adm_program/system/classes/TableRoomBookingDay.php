<?php
/**
 ***********************************************************************************************
 * Class manages access to database table adm_guestbook
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * Diese Klasse dient dazu ein Gaestebucheintragsobjekt zu erstellen.
 * Eine Gaestebucheintrag kann ueber diese Klasse in der Datenbank verwaltet werden
 *
 * Beside the methods of the parent class there are the following additional methods:
 *
 * moderate()       - guestbook entry will be published, if moderate mode is set
 */
class TableRoomBookingDay extends TableAccess
{
    /**
     * Constructor that will create an object of a recordset of the table adm_guestbook.
     * If the id is set than the specific guestbook will be loaded.
     * @param Database $database Object of the class Database. This should be the default global object **$gDb**.
     * @param int      $gboId    The recordset of the guestbook with this id will be loaded. If id isn't set than an empty object of the table is created.
     */
    public function __construct(Database $database, $rbdId = 0)
    {
        parent::__construct($database, 'mws__roombookingday', 'rbd', $rbdId);
    }

    /**
     * Deletes the selected guestbook entry and all comments.
     * After that the class will be initialize.
     * @return bool **true** if no error occurred
     */
    public function delete()
    {
        $this->db->startTransaction();

        // Delete all bookings and exceptions
        $sql = 'DELETE FROM mws__bookings
                      WHERE boo_rbd_id = ? -- $this->getValue(\'rbd_id\')';
        $sql = 'DELETE FROM mws__bookexceptions
                      WHERE bex_rbd_id = ? -- $this->getValue(\'rbd_id\')';
        $this->db->queryPrepared($sql, array($this->getValue('rbd_id')));

        $return = parent::delete();

        $this->db->endTransaction();

        return $return;
    }

    /**
     * Get the value of a column of the database table.
     * If the value was manipulated before with **setValue** than the manipulated value is returned.
     * @param string $columnName The name of the database column whose value should be read
     * @param string $format     For date or timestamp columns the format should be the date/time format e.g. **d.m.Y = '02.04.2011'**.
     *                           For text columns the format can be **database** that would return the original database value without any transformations
     * @return mixed Returns the value of the database column.
     *         If the value was manipulated before with **setValue** than the manipulated value is returned.
     */
    public function getValue($columnName, $format = '')
    {
        return parent::getValue($columnName, $format);
    }

    /**
     * guestbook entry will be published, if moderate mode is set
     */

    /**
     * Save all changed columns of the recordset in table of database. Therefore the class remembers if it's
     * a new record or if only an update is necessary. The update statement will only update
     * the changed columns. If the table has columns for creator or editor than these column
     * with their timestamp will be updated.
     * For new records the organization and ip address will be set per default.
     * @param bool $updateFingerPrint Default **true**. Will update the creator or editor of the recordset if table has columns like **usr_id_create** or **usr_id_changed**
     * @return bool If an update or insert into the database was done then return true, otherwise false.
     */

    /**
     * Set a new value for a column of the database table.
     * The value is only saved in the object. You must call the method **save** to store the new value to the database
     * @param string $columnName The name of the database column whose value should get a new value
     * @param mixed  $newValue   The new value that should be stored in the database field
     * @param bool   $checkValue The value will be checked if it's valid. If set to **false** than the value will not be checked.
     * @return bool Returns **true** if the value is stored in the current object and **false** if a check failed
     */
    protected function readData($sqlWhereCondition, array $queryParams = array())
    {
        $sqlAdditionalTables = '';

        // create sql to connect additional tables to the select statement
        if (count($this->additionalTables) > 0)
        {
            foreach ($this->additionalTables as $arrAdditionalTable)
            {
                $sqlAdditionalTables .= ', '.$arrAdditionalTable['table'];
                $sqlWhereCondition   .= ' AND '.$arrAdditionalTable['columnNameAdditionalTable'].' = '.$arrAdditionalTable['columnNameClassTable'].' ';
            }
        }

        // if condition starts with AND then remove this
        if (admStrStartsWith(strtoupper(ltrim($sqlWhereCondition)), 'AND'))
        {
            $sqlWhereCondition = substr($sqlWhereCondition, 4);
        }

        if ($sqlWhereCondition !== '')
        {
            $sql = 'SELECT *
                      FROM '.$this->tableName.'
                           '.$sqlAdditionalTables.'
                     WHERE '.$sqlWhereCondition;
            $readDataStatement = $this->db->queryPrepared($sql, $queryParams); // TODO add more params

            if ($readDataStatement->rowCount() === 1)
            {
                $row = $readDataStatement->fetch();
                $this->newRecord = false;

                // Daten in das Klassenarray schieben
                foreach ($row as $key => $value)
                {
                    if ($value === null)
                    {
                        $this->dbColumns[$key] = ''; // TODO: remove
                    }
                    else
                    {
                        $this->dbColumns[$key] = $value;
                    }
                }

                return true;
            }

            $this->clear();
        }

        return false;
    }
    public function readDataById($id)
    {
        // initialize the object, so that all fields are empty
        $this->clear();

        // add id to sql condition
        if ($id > 0)
        {
            // call method to read data out of database
            return $this->readData(' AND ' . $this->keyColumnName . ' = ? ', array($id));
        }

        return false;
    }
    public function setValue($columnName, $newValue, $checkValue = true)
    {
        return parent::setValue($columnName, $newValue, $checkValue);
    }
}
