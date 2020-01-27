<?php
/**
 ***********************************************************************************************
 * Class manages access to database table adm_users
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * @deprecated 3.2.0:4.0.0 This class is deprecated and should not be used anymore. The class Users should be used instead.
 */
class TableUsers extends User
{
    /**
     * Constructor that will create an object of a recordset of the table adm_users.
     * If the id is set than the specific user will be loaded.
     * @deprecated 3.2.0:4.0.0 This class is deprecated and should not be used anymore. The class Users should be used instead.
     * @param Database $database Object of the class Database. This should be the default global object **$gDb**.
     * @param int      $userId   The recordset of the user with this id will be loaded. If id isn't set than an empty object of the table is created.
     */
    public function __construct(Database $database, $userId = 0)
    {
        global $gLogger, $gProfileFields;

        $gLogger->warning('DEPRECATED: "new TableUsers()" is deprecated, use "new User()" instead!');

        parent::__construct($database, $gProfileFields, $userId);
    }
}
