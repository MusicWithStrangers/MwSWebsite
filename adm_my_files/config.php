<?php
/**
 ***********************************************************************************************
 * Configuration file of Admidio
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

// Select your database system for example 'mysql' or 'pgsql'
$gDbType = 'mysql'; 

// Access to the database of the SQL-Server
$g_adm_srv  = 'localhost';     // Host
$g_adm_port = null;     // Port
$g_adm_db   = 'qb402259_admid82';     // Database-Name
$g_adm_usr  = 'qb402259_admid82'; // Username
$g_adm_pw   = '!5c.0Sexp7'; // Password

// Table prefix for Admidio-Tables in database
// Example: 'adm'
$g_tbl_praefix = 'mws_';

// URL to this Admidio installation
// Example: 'https://www.admidio.org/example'
// $g_root_path = 'http://members.musicwithstrangers.com';
$g_root_path = 'http://localhost';

// Short description of the organization that is running Admidio
// This short description must correspond to your input in the installation wizard !!!
// Example: 'ADMIDIO'
// Maximum of 10 characters !!!
$g_organization = 'MWSM';

// The name of the timezone in which your organization is located.
// This must be one of the strings that are defined here https://secure.php.net/manual/en/timezones.php
// Example: 'Europe/Berlin'
$gTimezone = 'Europe/Amsterdam';

// If this flag is set = 1 then you must enter your loginname and password
// for an update of the Admidio database to a new version of Admidio.
// For a more comfortable and easy update you can set this preference = 0.
$gLoginForUpdate = 1;
$Debug=1;

