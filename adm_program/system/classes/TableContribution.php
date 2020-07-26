<?php
/**
 ***********************************************************************************************
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

global $gDb;

class TableContribution extends TableAccess
{
    /**
     * Constructor that will create an object of a recordset of the table adm_dates.
     * If the id is set than the specific date will be loaded.
     * @param Database $database Object of the class Database. This should be the default global object **$gDb**.
     * @param int      $datId    The recordset of the date with this id will be loaded. If id isn't set than an empty object of the table is created.
     */
    public function __construct(Database $database, $feeId = 0)
    {
        // read also data of assigned category
        //$this->connectAdditionalTable(TBL_CATEGORIES, 'cat_id', 'dat_cat_id');

        parent::__construct($database, 'mws__contribution_fees', 'fee', $feeId);
    }

    /**
     * Deletes the selected record of the table and all references in other tables.
     * After that the class will be initialize.
     * @return bool **true** if no error occurred
     */
    public function delete()
    {
        $feeId     = (int) $this->getValue('fee_id');

        $this->db->startTransaction();

        // TODO: if an event is deleted, delete all song registrations
        // delete all song registratons for this song
        /**
        $eventParticipationRoles = new RolesRights($this->db, 'event_participation', $datId);
        $eventParticipationRoles->delete();
       
        // if date has participants then the role with their memberships must be deleted
        if ($datRoleId > 0)
        {
            $sql = 'UPDATE '.TBL_DATES.'
                       SET dat_rol_id = NULL
                     WHERE dat_id = ? -- $datId';
            $this->db->queryPrepared($sql, array($datId));

            $dateRole = new TableRoles($this->db, $datRoleId);
            $dateRole->delete(); // TODO Exception handling
        }
        */
        
        // now delete song
        parent::delete();

        return $this->db->endTransaction();
    }

    /**
     * @param string $text
     * @return string
     */


    /**
     * Get the value of a column of the database table.
     * If the value was manipulated before with **setValue** than the manipulated value is returned.
     * @param string $columnName The name of the database column whose value should be read
     * @param string $format     For date or timestamp columns the format should be
     *                           the date/time format e.g. **d.m.Y = '02.04.2011'**.
     *                           For text columns the format can be **database** that would return
     *                           the original database value without any transformations
     * @return int|string|bool Returns the value of the database column.
     *                         If the value was manipulated before with **setValue** than the manipulated value is returned.
     */
    public function getValue($columnName, $format = '')
    {
        global $gL10n;

        if ($columnName === 'fee_description')
        {
            if (!isset($this->dbColumns['fee_description']))
            {
                $value = '';
            }
            elseif ($format === 'database')
            {
                $value = html_entity_decode(strStripTags($this->dbColumns['fee_description']), ENT_QUOTES, 'UTF-8');
            }
            else
            {
                $value = $this->dbColumns['fee_description'];
            }
        }
        else
        {
            $value = parent::getValue($columnName, $format);
        }

        return $value;
    }

    /**
     * This function reads the deadline for participation. If no deadline is set as default the the startdate of the event will be set.
     * return string $dateDeadline Returns a string with formated date and time
     */

    public function isEditable()
    {
        global $gCurrentOrganization, $gCurrentUser;

        if($gCurrentUser->editDates()
        || in_array((int) $this->getValue('cat_id'), $gCurrentUser->getAllEditableCategories('DAT'), true))
        {
            // if category belongs to current organization than events are editable
            if($this->getValue('cat_org_id') > 0
            && (int) $this->getValue('cat_org_id') === (int) $gCurrentOrganization->getValue('org_id'))
            {
                return true;
            }

            // if category belongs to all organizations, child organization couldn't edit it
            if((int) $this->getValue('cat_org_id') === 0 && !$gCurrentOrganization->isChildOrganization())
            {
                return true;
            }
        }
    }

    /**
     * This method checks if the current user is allowed to view this event. Therefore
     * the visibility of the category is checked.
     * @return bool Return true if the current user is allowed to view this event
     */
    public function isVisible()
    {
        global $gCurrentUser;

    }

    /**
     * Set a new value for a column of the database table.
     * The value is only saved in the object. You must call the method **save** to store the new value to the database
     * @param string $columnName The name of the database column whose value should get a new value
     * @param mixed  $newValue   The new value that should be stored in the database field
     * @param bool   $checkValue The value will be checked if it's valid. If set to **false** than the value will not be checked.
     * @return bool Returns **true** if the value is stored in the current object and **false** if a check failed
     */
    public function setValue($columnName, $newValue, $checkValue = true)
    {
        if($checkValue)
        {
            if ($columnName === 'fee_description')
            {
                return parent::setValue($columnName, $newValue, false);
            }
        }

        return parent::setValue($columnName, $newValue, $checkValue);
    }
}
