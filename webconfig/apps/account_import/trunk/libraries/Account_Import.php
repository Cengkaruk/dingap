<?php

/**
 * Account import/export class.
 *
 * @category   Apps
 * @package    Account_Import
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\account_import;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('account_import');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////


// Factories
//----------

use \clearos\apps\users\User_Factory as User;
use \clearos\apps\users\User_Manager_Factory as User_Manager;

clearos_load_library('users/User_Factory');
clearos_load_library('users/User_Manager_Factory');

// Classes
//--------

use \clearos\apps\File_CSV_DataSource as File_CSV_DataSource;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\base\File as File;
use \clearos\apps\groups\Group as Group;
use \clearos\apps\groups\Group_Manager as Group_Manager;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\base\Engine as Engine;

clearos_load_library('/File_CSV_DataSource');
clearos_load_library('base/Shell;');
clearos_load_library('base/File');
clearos_load_library('groups/Group');
clearos_load_library('groups/Group_Manager');
clearos_load_library('network/Hostname');
clearos_load_library('base/Engine');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\groups\Group_Not_Found_Exception as Group_Not_Found_Exception;
use \clearos\apps\users\User_Already_Exists_Exception as User_Already_Exists_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('groups/Group_Not_Found_Exception');
clearos_load_library('users/User_Already_Exists_Exception');


///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Account import/export class.
 *
 * @category   Apps
 * @package    Account_Import
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

class Account_Import extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CSV = 'import.csv';
    const FILE_CSV_TEMPLATE = 'import_template.csv';
    const COMMAND_IMPORT = '/usr/sbin/account_import';
    const COMMAND_PS = '/bin/ps';
    const FOLDER_ACCOUNT_IMPORT = '/var/clearos/account_import';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Account Import/Export constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns boolean indicating whether import is currently running.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function is_import_in_progress()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new Shell();
            $exitcode = $shell->execute(self::COMMAND_PS, " afx | grep " . addslashes(self::COMMAND_IMPORT) . " & echo $!", FALSE);
            if ($exitcode != 0)
                throw new Engine_Exception(lang('account_import_unable_to_determine_running_state'), CLEAROS_WARNING);
            $rows = $shell->get_output();
            $pid = -1;
            foreach ($rows as $row) {
                if ($pid < 0) {
                    $pid = $row;
                    continue;
                }
                if (preg_match('/^([0-9]+)\s+.*/', $row, $match)) {
                    // Bit of a hack...looking at PIDs
                    if ((intval($match[1]) + 3) < $pid || $match[1] > $pid)
                        return TRUE;
                }
            }
            return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(lang('account_import_unable_to_determine_running_state'), CLEAROS_WARNING);
        }
    }

    /**
     * Perform an account export.
     *
     * @throws Engine_Exception
     */

    function export()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $hostname = new Hostname();
            $filename = $hostname->get() . '.csv';
            $file = new File(COMMON_TEMP_DIR . "/" . $filename, FALSE);

            if ($file->exists())
                $file->delete();
            $file->create("webconfig", "webconfig", 640);
            $file->add_lines("username,firstName,lastName,password,street,roomNumber,city,region," .
                "country,postalCode,organization,unit,telephone,fax,mailFlag,mailquota," .
                "proxyFlag,openvpnFlag,pptpFlag,sambaFlag,ftpFlag,webFlag,pbxState,pbxPresenceState,pbxExtension,groups" .
                "\n"
            );
            $usermanager = new User_Manager();
            $groupmanager = new Group_Manager();
            $userlist = $usermanager->get_all_users();
            $groups = $groupmanager->get_group_list();
            foreach ($userlist as $username) {
                // Reset group list
                $grouplist = '';
                $user = new User($username);
                $userinfo = $user->get_info();
                foreach ($groups as $group) {
                    if (in_array($username, $group['members']))
                        $grouplist .= $group['group'] . ',';
                }

                $grouplist = preg_replace('/(.*),$/', '${1}', $grouplist);
                
                $file->add_lines(
                    "\"$username\"," .
                    "\"" . $userinfo['firstName'] . "\"," .
                    "\"" . $userinfo['lastName'] . "\"," .
                    "\"\"," . // Password blank for now
                    "\"" . $userinfo['street'] . "\"," .
                    "\"" . $userinfo['roomNumber'] . "\"," .
                    "\"" . $userinfo['city'] . "\"," .
                    "\"" . $userinfo['region'] . "\"," .
                    "\"" . $userinfo['country'] . "\"," .
                    "\"" . $userinfo['postalCode'] . "\"," .
                    "\"" . $userinfo['organization'] . "\"," .
                    "\"" . $userinfo['unit'] . "\"," .
                    "\"" . $userinfo['telephone'] . "\"," .
                    "\"" . $userinfo['fax'] . "\"," .
                    ((isset($userinfo['mailFlag']) && $userinfo['mailFlag']) ? "TRUE" : "FALSE") . "," .
                    "\"" . $userinfo['mailquota'] . "\"," .
                    ((isset($userinfo['proxyFlag']) && $userinfo['proxyFlag']) ? "TRUE" : "FALSE") . "," .
                    ((isset($userinfo['openvpnFlag']) && $userinfo['openvpnFlag']) ? "TRUE" : "FALSE") . "," .
                    ((isset($userinfo['pptpFlag']) && $userinfo['pptpFlag']) ? "TRUE" : "FALSE") . "," .
                    ((isset($userinfo['sambaFlag']) && $userinfo['sambaFlag']) ? "TRUE" : "FALSE") . "," .
                    ((isset($userinfo['ftpFlag']) && $userinfo['ftpFlag']) ? "TRUE" : "FALSE") . "," .
                    ((isset($userinfo['webFlag']) && $userinfo['webFlag']) ? "TRUE" : "FALSE") . "," .
                    ((isset($userinfo['pbxState']) && $userinfo['pbxState']) ? "TRUE" : "FALSE") . "," .
                    ((isset($userinfo['pbxPresenceState']) && $userinfo['pbxPresenceState']) ? "TRUE" : "FALSE") . "," .
                    "\"" . $userinfo['pbxExtension'] . "\"," .
                    "\"" . $grouplist . "\"" .
                    "\n"
                );
            }
            return $filename;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Perform an account import.
     *
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function import()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_import_in_progress())
            throw new Engine_Exception(lang('account_import_in_progress'), CLEAROS_ERROR);
            
        $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
        if (!$file->exists())
            throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);

        try {
            $options = array();
            $options['background'] = true;
            $shell = new Shell;
            $shell->execute(self::COMMAND_IMPORT, NULL, FALSE, $options);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Put the CSV file in the cache directory, ready for import begin.
     *
     * @filename string CSV filename
     * @return void
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function set_csv_file($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(CLEAROS_TEMP_DIR . '/' . $filename, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);

            // Move uploaded file to cache
            $file->move_to(self::FOLDER_ACCOUNT_IMPORT. '/' . self::FILE_CSV);
            $file->chown('root','root'); 
            $file->chmod(600);
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Is CSV file uploaded.
     *
     * @return boolean true/FALSE
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function is_csv_file_uploaded()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                return FALSE;
            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Resets (deletes) the CSV file.
     *
     * @return void
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function delete_csv_file()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);
            $file->delete();
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Resets (deletes) the CSV file.
     *
     * @return integer size 
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function get_csv_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);
            return $file->get_size();
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Get the number of records.
     *
     * @return integer the number of records
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function get_number_of_records()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);
            $csv = new File_CSV_DataSource();
            $csv->load(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV);
            return $csv->countRows();
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Add a user.
     *
     * @param array $userinfo array of user info
     * @throws User_Already_Exists_Exception Validation_Exception
     */

    function add_user($userinfo)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $this->_convert_flags($userinfo);

            // Need to pop a few variables into place
            $username = strtolower($userinfo['username']);
            unset($userinfo['username']);
            // Add password verification
            $userinfo['verify'] = $userinfo['password'];
            $userinfo['webconfigFlag'] = true;
            $user = new User($username);

            try {
                $user->Add($userinfo);
            } catch (User_Already_Exists_Exception $e) {
                throw new User_Already_Exists_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            } catch (Exception $e) {
                $errors = $user->get_validation_errors();
                throw new Validation_Exception($errors[0]);
            }

        } catch (User_Already_Exists_Exception $e) {
            throw new User_Already_Exists_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Validation_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Add a user to a group.
     *
     * @param string $username username
     * @param string $group    group
     * @throws Engine_Exception, Group_Not_Found_Exception
     */

    function add_user_to_group($username, $group)
    {
        clearos_profile(__METHOD__, __LINE__);

        // We do this on a group by group basis so we can log individual errors
        try {
            $group = new Group($group);
            $group->add_member($username);
        } catch (Group_Not_Found_Exception $e) {
            throw new Group_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
    * Loads configuration files.
    *
    * @param array $userinfo array of user info
    * @return void
    * @throws Engine_Exception
    */

    protected function _convert_flags(&$userinfo)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Convert empty strings to null
        foreach ($userinfo as $key => $value) {
            if (empty($value))
                $userinfo[$key] = NULL;
        }

        // Convert to booleans
        $attribute_list = array(
            'ftpFlag',
            'mailFlag',
            'openvpnFlag',
            'pptpFlag',
            'sambaFlag',
            'webFlag',
            'webconfigFlag',
            'proxyFlag',
            'pbxState',
            'pbxPresenceState'
        );

        foreach ($attribute_list as $attribute) {
            if (isset($userinfo[$attribute]) && $userinfo[$attribute] && !eregi('FALSE|no', $userinfo[$attribute]))
                $userinfo[$attribute] = true;
            else
                $userinfo[$attribute] = FALSE;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

}
