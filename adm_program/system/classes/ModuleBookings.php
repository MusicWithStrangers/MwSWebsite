<?php
/**
 ***********************************************************************************************
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * This class reads date recordsets from database
 *
 * This class reads all available recordsets from table dates.
 * and returns an Array with results, recordsets and validated parameters from $_GET Array.
 *
 * **Returned array:**
 * ```
 * array(
 *         [numResults] => 1
 *         [limit] => 10
 *         [totalCount] => 1
 *         [recordset] => Array
 *         (
 *             [0] => Array
 *                 (
 *                     [0] => 10
 *                     [cat_id] => 10
 *                     [1] => 1
 *                     [cat_org_id] => 1
 *                     [2] => DAT
 *                     [cat_type] => DAT
 *                     [3] => COMMON
 *                     [cat_name_intern] => COMMON
 *                     [4] => Allgemein
 *                     [cat_name] => Allgemein
 *                     [6] => 0
 *                     [cat_system] => 0
 *                     [7] => 0
 *                     [cat_default] => 0
 *                     [8] => 1
 *                     [cat_sequence] => 1
 *                     [9] => 1
 *                     [cat_usr_id_create] => 1
 *                     [10] => 2012-01-08 11:12:05
 *                     [cat_timestamp_create] => 2012-01-08 11:12:05
 *                     [11] =>
 *                     [cat_usr_id_change] =>
 *                     [12] =>
 *                     [cat_timestamp_change] =>
 *                     [13] => 9
 *                     [dat_id] => 9
 *                     [14] => 10
 *                     [dat_cat_id] => 10
 *                     [15] =>
 *                     [dat_rol_id] =>
 *                     [16] =>
 *                     [dat_room_id] =>
 *                     [18] => 2013-09-21 21:00:00
 *                     [dat_begin] => 2013-09-21 21:00:00
 *                     [19] => 2013-09-21 22:00:00
 *                     [dat_end] => 2013-09-21 22:00:00
 *                     [20] => 0
 *                     [dat_all_day] => 0
 *                     [21] => 0
 *                     [dat_highlight] => 0
 *                     [22] =>
 *                     [dat_description] =>
 *                     [23] =>
 *                     [dat_location] =>
 *                     [24] =>
 *                     [dat_country] =>
 *                     [25] => eet
 *                     [dat_headline] => eet
 *                     [26] => 0
 *                     [dat_max_members] => 0
 *                     [27] => 1
 *                     [dat_usr_id_create] => 1
 *                     [28] => 2013-09-20 21:56:23
 *                     [dat_timestamp_create] => 2013-09-20 21:56:23
 *                     [29] =>
 *                     [dat_usr_id_change] =>
 *                     [30] =>
 *                     [dat_timestamp_change] =>
 *                     [31] =>
 *                     [member_date_role] =>
 *                     [32] =>
 *                     [mem_leader] =>
 *                     [33] => Paul Webmaster
 *                     [create_name] => Paul Webmaster
 *                     [34] =>
 *                     [change_name] =>
 *                 )
 *
 *         )
 *
 *     [parameter] => Array
 *         (
 *             [active_role] => 1
 *             [calendar-selection] => 1
 *             [cat_id] => 0
 *             [category-selection] => 0,
 *             [date] =>
 *             [daterange] => Array
 *                 (
 *                     [english] => Array
 *                         (
 *                             [start_date] => 2013-09-21
 *                             [end_date] => 9999-12-31
 *                         )
 *
 *                     [system] => Array
 *                         (
 *                             [start_date] => 21.09.2013
 *                             [end_date] => 31.12.9999
 *                         )
 *
 *                 )
 *
 *             [headline] => Termine
 *             [id] => 0
 *             [mode] => actual
 *             [order] => ASC
 *             [startelement] => 0
 *             [view_mode] => html
 *         )
 *
 * )
 * ```
 */
class ModuleBookings extends Modules
{
    /**
     * Constructor that will create an object of a parameter set needed in modules to get the recordsets.
     * Initialize parameters
     */
    public function __construct()
    {
        parent::__construct();

        $this->setParameter('mode', 'actual');
    }

    /**
     * SQL query returns an array with available dates.
     * @param int $startElement Defines the offset of the query (default: 0)
     * @param int $limit        Limit of query rows (default: 0)
     * @return array<string,mixed> Array with all results, dates and parameters.
     */
    public function getDataSet($startElement = 0, $limit = null)
    {
        global $gDb, $gSettingsManager, $gCurrentUser;
        $gCurrentUserId=$gCurrentUser->getValue('usr_id');

        if ($limit === null)
        {
            $limit = 10;
        }

        // read dates from database
        if ($gCurrentUser->editBookings())
        {
            $sql = 'SELECT * FROM mws__roombookingday INNER JOIN mws__venues on mws__roombookingday.rbd_venue=mws__venues.ven_id';
        } else {
            $sql = 'SELECT * FROM mws__roombookingday INNER JOIN mws__venues on mws__roombookingday.rbd_venue=mws__venues.ven_id where mws__roombookingday.rbd_enable=1';
        }
        $pdoStatement = $gDb->queryPrepared($sql); // TODO add more params
        $bookCount=$pdoStatement->rowCount();
        $bookResults=array();
        if ($bookCount>0)
        {
            $bookData = $pdoStatement->fetchAll();
            foreach ($bookData as $abook)
            {
                $aBooking=array();
                $canBook=False;
                $showRoom=False;
                $bookTimestamp=strtotime($abook['rbd_startTime']);
                $bookDate=new DateTime();
                $bookDate->setTimestamp($bookTimestamp);
                 #$bookDate->getTimestamp();
                if ($abook['rbd_weekly'] == 1)
                {
                    $dayweek=date('l',$bookTimestamp);
                    $now = new DateTime();
                    $nextDate = $now->modify('next '.$dayweek);
                    $sqlexceptions='SELECT * FROM mws__bookexceptions where bex_rbd_id='.$abook['rbd_id'];
                    $pdoStatementExcept = $gDb->queryPrepared($sqlexceptions); 
                    $exceptCount=$pdoStatementExcept->rowCount();
                    $hasExcept=True;
                    if ($exceptCount>0)                
                    {
                        $exceptData = $pdoStatementExcept->fetchAll();
                        while ($hasExcept)
                        {
                            $hasExcept=False;
                            foreach ($exceptData as $anException)
                            {
                                //$exceptDate=new DateTime($anException['bex_rbd_date']);
                                $exceptDateString=date("m/d/Y h:i:s A T",strtotime($anException['bex_rbd_date']));
                                $exceptDate=DateTime::createFromFormat('m/d/Y h:i:s A T', $exceptDateString);
                                if ($exceptDate->format('Y/m/d') == $nextDate->format('Y/m/d'))
                                {
                                    $hasExcept=True;
                                    $nextDate->modify('+7 days');
                                }

                            }
                        }
                    } 
                    $nextTimestamp=$nextDate->getTimestamp();
                    $startTime=date("h:i:s A T",$bookTimestamp);
                    $startDate=date("m/d/Y",$nextTimestamp);
                    $slotStart=DateTime::createFromFormat('m/d/Y h:i:s A T', $startDate.' '.$startTime);
                    //copy time from bookdate to bookday
                } else
                {
                    // else if date is today
                    $slotStartString=date("m/d/Y h:i:s A T",strtotime($abook['rbd_startTime']));
                    $slotStart=DateTime::createFromFormat('m/d/Y h:i:s A T', $slotStartString);
                }
                $now = new DateTime();
                $bookDate=$slotStart->format("Y-m-d");
                $minutesFromStart = abs($now->getTimestamp() - $slotStart->getTimestamp()) / 60;
                #$slotStart=strtotime($abook['rbd_startTime']);
                $slotEnd= clone $slotStart;
                $minutes=strval((int)$abook['rbd_slotCount']*(int)$abook['rbd_slotLength']);
                $bookWithSong=$abook['rbd_hoursBookingSNR'];
                $bookWithSongStart= clone $slotStart;
                $bookWithSongStart->modify('-'.(int)$bookWithSong.' hours' );
                $bookNonSong=$abook['rbd_hoursBookingNonSNR'];
                $bookNonSongStart= clone $slotStart;
                $bookNonSongStart->modify('-'.(int)$bookNonSong.'hours');
                $slotEnd->modify('+'.$minutes.' minutes' );
                # DATE(mws_bookings.bookdate)=DATE($slotStart)
                # WHERE DATE(timestamp) = '2012-05-05'
                $bookDelayAfterBook=$abook['rbd_hoursBookDelayAfterbooked'];
                $sql = 'SELECT * FROM mws__roombookingday inner join mws__bookings on mws__roombookingday.rbd_id=mws__bookings.boo_rbd_id inner join mws__users on mws__users.usr_id=mws__bookings.boo_usr_id inner join mws__user_data on mws__user_data.usd_usr_id=mws__users.usr_id where mws__user_data.usd_usf_id IN (1,2) and rbd_id='. $abook['rbd_id'].' AND DATE(mws__bookings.boo_bookdate)=DATE('.$bookDate.')';
                $sql='SELECT GROUP_CONCAT(mws__user_data.usd_value order by mws__user_data.usd_usf_id DESC SEPARATOR \' \') as \'name\', mws__roombookingday.rbd_startTime, mws__bookings.boo_specialbooking, mws__bookings.boo_comment, mws__bookings.boo_bookdate, mws__roombookingday.rbd_enable, mws__roombookingday.rbd_slotCount, mws__roombookingday.rbd_slotLength, mws__roombookingday.rbd_id, mws__bookings.boo_id, mws__bookings.boo_usr_id, mws__bookings.boo_slotindex FROM mws__roombookingday inner join mws__bookings on mws__roombookingday.rbd_id=mws__bookings.boo_rbd_id inner join mws__users on mws__users.usr_id=mws__bookings.boo_usr_id inner join mws__user_data on mws__user_data.usd_usr_id=mws__users.usr_id where mws__user_data.usd_usf_id IN (1,2) and mws__roombookingday.rbd_id='.$abook['rbd_id'].' AND DATE(mws__bookings.boo_bookdate)=\''.$bookDate.'\' group by mws__bookings.boo_id';
                $pdoStatementbusy = $gDb->queryPrepared($sql); // TODO add more params
                $bookCountbusy=$pdoStatementbusy->rowCount();
                $slotData = new stdClass();
                if ($bookCountbusy>0)
                {
                    $slotData=$pdoStatementbusy->fetchAll();
                }
                
                #$slotStartStamp = date("m/d/Y h:i:s A T",$slotStart);
                #$slotEndStamp = date("m/d/Y h:i:s A T",$slotEnd);
                if ($slotStart>new DateTime() or $gCurrentUser->editBookings())
                {
                    $showRoom=1;
                }
                // check for exceptions on $bookDay (null it)
                $IBooked=0;
                if ($showRoom)
                {

                    // bex_rbd_from bex_rbd_to
                    // check bookable   
                    $now = new DateTime();
                    if ($now>$bookWithSongStart)
                    {
                        $canBookSong=True;
                    }
                    if ($now>$bookNonSongStart)
                    {
                        $canBookNonSong=True;
                    }
                    $aBooking['slotstart'] = $slotStart;
                    $aBooking['slotend'] = $slotEnd;
                    $aBooking['bookWithSongStart']=$bookWithSongStart;
                    $aBooking['bookNonSongStart']=$bookNonSongStart;
                    $aBooking['venuename']=$abook['ven_name'];
                    $aBooking[] = $abook;
                    $slotCount=$abook['rbd_slotCount'];
                    $slotLength=$abook['rbd_slotLength'];
                    $slotTimes=[];
                    $slotBookings=[];
                    $slotBookingsDescription=[];
                    for ($i=0;$i<$slotCount;$i++)
                    {
                        $slotTime= clone $slotStart;
                        $slotTimes[$i+1]=$slotTime->modify('+'.(int)$slotLength*($i).' minutes' );
                        $slotBookings[$i+1]=0;
                    }
                    foreach ($slotData as $aSlot)
                    {
                        if ($aSlot['boo_usr_id']===$gCurrentUserId and $aSlot['boo_specialbooking']===0)
                        {
                            $IBooked++;
                        }
                        $sindex=(int)$aSlot['boo_slotindex'];
                        $slotBookings[$sindex]=$aSlot['boo_id'];
                        $slotBookingsDescription[$sindex]=$aSlot['name'];
                    }
                }
                $aBooking['slotTimes']=$slotTimes;
                $aBooking['IBooked']=$IBooked;
                $aBooking['slotBookings']=$slotBookings;
                $aBooking['slotBookingsName']=$slotBookingsDescription;
                // create slots from start time
                $bookResults[]=$aBooking;
            }
        }

        
        // array for results
        return array(
            'recordset'  => $bookResults,
            'totalCount' => count($bookResults)
        );
    }

    /**
     * Get number of available dates.
     * @return int
     */
    public function getDataSetCount()
    {
        global $gDb, $gCurrentUser;

        if ($this->id > 0)
        {
            return 1;
        }

        $catIdParams = array_merge(array(0), $gCurrentUser->getAllVisibleCategories('DAT'));
        $sqlConditions = $this->getSqlConditions();

        $sql = 'SELECT COUNT(DISTINCT dat_id) AS count
                  FROM ' . TBL_DATES . '
            INNER JOIN ' . TBL_CATEGORIES . '
                    ON cat_id = dat_cat_id
                 WHERE cat_id IN ('.replaceValuesArrWithQM($catIdParams).')
                       '. $sqlConditions['sql'];

        $statement = $gDb->queryPrepared($sql, array_merge($catIdParams, $sqlConditions['params']));

        return (int) $statement->fetchColumn();
    }

    /**
     * Returns a module specific headline
     * @param string $headline The initial headline of the module.
     * @return string Returns the full headline of the module
     */
    public function getHeadline($headline)
    {
        global $gDb, $gL10n, $gCurrentOrganization;

        return $headline;
    }



    /**
     * Set a date range in which the dates should be searched. The method will fill
     * 4 parameters **dateStartFormatEnglish**, **dateStartFormatEnglish**,
     * **dateEndFormatEnglish** and **dateEndFormatAdmidio** that could be read with
     * getParameter and could be used in the script.
     * @param string $dateRangeStart A date in english or Admidio format that will be the start date of the range.
     * @param string $dateRangeEnd   A date in english or Admidio format that will be the end date of the range.
     * @throws AdmException SYS_DATE_END_BEFORE_BEGIN
     * @return bool Returns false if invalid date format is submitted
     */
    public function setDateRange($dateRangeStart = '', $dateRangeEnd = '')
    {
        global $gSettingsManager;

        if ($dateRangeStart === '')
        {
            $dateStart = '1970-01-01';
            $dateEnd   = (date('Y') + 10) . '-12-31';

            // set date_from and date_to regarding to current mode
            switch ($this->mode)
            {
                case 'actual':
                    $dateRangeStart = DATE_NOW;
                    $dateRangeEnd   = $dateEnd;
                    break;
                case 'old':
                    $dateRangeStart = $dateStart;
                    $dateRangeEnd   = DATE_NOW;
                    break;
                case 'all':
                    $dateRangeStart = $dateStart;
                    $dateRangeEnd   = $dateEnd;
                    break;
            }
        }
        // If mode=old then we want to have the events in reverse order ('DESC')
        if ($this->mode === 'old')
        {
            $this->order = 'DESC';
        }

        // Create date object and format date_from in English format and system format and push to daterange array
        $objDateFrom = \DateTime::createFromFormat('Y-m-d', $dateRangeStart);

        if ($objDateFrom === false)
        {
            // check if date_from has system format
            $objDateFrom = \DateTime::createFromFormat($gSettingsManager->getString('system_date'), $dateRangeStart);
        }

        if ($objDateFrom === false)
        {
            return false;
        }

        $this->setParameter('dateStartFormatEnglish', $objDateFrom->format('Y-m-d'));
        $this->setParameter('dateStartFormatAdmidio', $objDateFrom->format($gSettingsManager->getString('system_date')));

        // Create date object and format date_to in English format and system format and push to daterange array
        $objDateTo = \DateTime::createFromFormat('Y-m-d', $dateRangeEnd);

        if ($objDateTo === false)
        {
            // check if date_from  has system format
            $objDateTo = \DateTime::createFromFormat($gSettingsManager->getString('system_date'), $dateRangeEnd);
        }

        if ($objDateTo === false)
        {
            return false;
        }

        $this->setParameter('dateEndFormatEnglish', $objDateTo->format('Y-m-d'));
        $this->setParameter('dateEndFormatAdmidio', $objDateTo->format($gSettingsManager->getString('system_date')));

        // DateTo should be greater than DateFrom (Timestamp must be less)
        if ($objDateFrom->getTimestamp() > $objDateTo->getTimestamp())
        {
            throw new AdmException('SYS_DATE_END_BEFORE_BEGIN');
        }

        return true;
    }


    /**
     * Method validates all date inputs and formats them to date format 'Y-m-d' needed for database queries
     * @deprecated 3.2.0:4.0.0 Dropped without replacement.
     * @param string $date Date to be validated and formated if needed
     * @return string|false
     */
    private function formatDate($date)
    {
        global $gLogger, $gSettingsManager;

        $gLogger->warning('DEPRECATED: "$moduleDates->formatDate()" is deprecated without replacement!');

        $objDate = \DateTime::createFromFormat('Y-m-d', $date);
        if ($objDate !== false)
        {
            return $date;
        }

        // check if date has system format
        $objDate = \DateTime::createFromFormat($gSettingsManager->getString('system_date'), $date);
        if ($objDate !== false)
        {
            return $objDate->format('Y-m-d');
        }

        return false;
    }

    /**
     * Returns value for form field.
     * This method compares a date value to a reference value and to date '1970-01-01'.
     * Html output will be set regarding the parameters.
     * If value matches the reference or date('1970-01-01'), the output value is cleared to get an empty string.
     * This method can be used to fill a html form
     * @deprecated 3.2.0:4.0.0 Dropped without replacement.
     * @param string $date      Date is to be checked to reference and default date '1970-01-01'.
     * @param string $reference Reference date
     * @return string|false String with date value, or an empty string, if $date is '1970-01-01' or reference date
     */
    public function getFormValue($date, $reference)
    {
        global $gLogger;

        $gLogger->warning('DEPRECATED: "$moduleDates->getFormValue()" is deprecated without replacement!');

        if (isset($date, $reference))
        {
            return $this->setFormValue($date, $reference);
        }

        return false;
    }

    /**
     * Check date value to reference and set html output.
     * If value matches to reference, value is cleared to get an empty string.
     * @deprecated 3.2.0:4.0.0 Dropped without replacement.
     * @param string $date
     * @param string $reference
     * @return string
     */
    private function setFormValue($date, $reference)
    {
        global $gLogger;

        $gLogger->warning('DEPRECATED: "$moduleDates->setFormValue()" is deprecated without replacement!');

        $checkedDate = $this->formatDate($date);
        if ($checkedDate === $reference || $checkedDate === '1970-01-01')
        {
            $date = '';
        }

        return $date;
    }
}
