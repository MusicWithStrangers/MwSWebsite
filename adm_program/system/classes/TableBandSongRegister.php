<?php
/**
 ***********************************************************************************************
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * Creates an event object from the database table adm_dates
 *
 * With the given id an event object is created from the data in the database table **adm_dates**.
 * The class will handle the communication with the database and give easy access to the data. New
 * event could be created or existing event could be edited. Special properties of
 * data like save urls, checks for evil code or timestamps of last changes will be handled within this class.
 *
 * **Code examples:**
 * ```
 * // get data from an existing event
 * $event       = new TableDate($gDb, $dateId);
 * $headline    = $event->getValue('dat_headline');
 * $description = $event->getValue('dat_description');
 *
 * // change existing event
 * $event = new TableDate($gDb, $dateId);
 * $event->setValue('dat_headline', 'My new headling');
 * $event->setValue('dat_description', 'This is the new description.');
 * $event->save();
 *
 * // create new event
 * $event = new TableDate($gDb);
 * $event->setValue('dat_headline', 'My new headling');
 * $event->setValue('dat_description', 'This is the new description.');
 * $event->save();
 * ```
 */
class TableBandSongRegister extends TableAccess
{
    /**
     * Constructor that will create an object of a recordset of the table adm_dates.
     * If the id is set than the specific date will be loaded.
     * @param Database $database Object of the class Database. This should be the default global object **$gDb**.
     * @param int      $datId    The recordset of the date with this id will be loaded. If id isn't set than an empty object of the table is created.
     */
    public function __construct(Database $database, $snrId = 0)
    {
        // read also data of assigned category
        //$this->connectAdditionalTable(TBL_CATEGORIES, 'cat_id', 'dat_cat_id');

        parent::__construct($database, 'mws__song_registration', 'snr', $snrId);
    }

    /**
     * Check if the current user is allowed to participate to this event.
     * Therefore we check if the user is member of a role that is assigned to
     * the right event_participation.
     * @return bool Return true if the current user is allowed to participate to the event.
     */
    public function allowedToRegister()
    {
        global $gCurrentUser;

        return true;
    }

    /**
     * Deletes the selected record of the table and all references in other tables.
     * After that the class will be initialize.
     * @return bool **true** if no error occurred
     */
    public function delete()
    {
        $snrId     = (int) $this->getValue('snr_id');

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

        $value = parent::getValue($columnName, $format);
        return $value;
    }

    /**
     * This method checks if the current user is allowed to edit this event. Therefore
     * the event must be visible to the user and must be of the current organization.
     * The user must be a member of at least one role that have the right to manage events.
     * Global events could be only edited by the parent organization.
     * @return bool Return true if the current user is allowed to edit this event
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
        if ($gCurrentUser->getValue('usr_id' === $this->getValue('snr_usr_id')))
        {
            // current user is the song owner and as such can edit
            return true;
        }

        return false;
    }

    /**
     * This method checks if the current user is allowed to view this event. Therefore
     * the visibility of the category is checked.
     * @return bool Return true if the current user is allowed to view this event
     */
    public function isVisible()
    {
        global $gCurrentUser;

        // TODO: who can watch song registration?
        // check if the current user could view the category of the event
        // return in_array((int) $this->getValue('cat_id'), $gCurrentUser->getAllVisibleCategories('SON'), true);
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
        return parent::setValue($columnName, $newValue, $checkValue);
    }
    public function participants_all()
    {
        $song_id = $this->getValue('snr_son_id');
        $song = new TableSong($gDb, $song_id);
        $userlist = $song->users_in_song();
        $participants=array();
        foreach ($userlist as $a_user)
        {
            $user=new User($gDb,$gProfileFields,$a_user);
            $participants[$user->getValue('usr_id')]=$user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME');
        }
        return participants;
    }
    public function participants_not_payed_now()
    {
        global $gDb, $gL10n, $gProfileFields;
        $song_id = $this->getValue('snr_son_id');
        $song = new TableSong($gDb, $song_id);
        $userlist = $song->users_in_song();
        $nonpayers=array();
        foreach ($userlist as $a_user)
        {
            $user=new User($gDb,$gProfileFields,$a_user);
            $blocked_message='';
            if (!$user->userPayedNow())
            {
                $nonpayers[$user->getValue('usr_id')]=$user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME');
            }
        }
        return $nonpayers;
    }
    public function participants_not_payed_during_event()
    {
        global $gDb, $gL10n, $gProfileFields;
        $song_id = $this->getValue('snr_son_id');
        $song = new TableSong($gDb, $song_id);
        $userlist = $song->users_in_song();
        $nonpayers=array();
        foreach ($userlist as $a_user)
        {
            $user=new User($gDb,$gProfileFields,$a_user);
            $blocked_message='';
            if (!$user->userPayedNow())
            {
                $nonpayers[$user->getValue('usr_id')]=$user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME');
            }
        }
        return $nonpayers;
    }
}
