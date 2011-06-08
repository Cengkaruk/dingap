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
use \clearos\apps\Shell_Exec as Shell_Exec;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\groups\Group as Group;
use \clearos\apps\groups\Group_Manager as Group_Manager;
use \clearos\apps\network\Hostname as Hostname;

clearos_load_library('/File_CSV_DataSource');
clearos_load_library('/Shell_Exec;');
clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('groups/Group');
clearos_load_library('groups/Group_Manager');
clearos_load_library('network/Hostname');

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

class Account_Import extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

	const PATH_TEMPLATE = '/usr/share/system/modules/user-import/';
	const FILE_ODS_TEMPLATE = 'import_template.ods';
	const FILE_XLS_TEMPLATE = 'import_template.xls';
	const FILE_CSV_TEMPLATE = 'import_template.csv';
	const FILE_IMPORT_SCRIPT = '/var/webconfig/scripts/userimport.php';
	const FILE_IMPORT = '/usr/webconfig/tmp/import.csv';

	protected $csv_file = null;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Account Import/Export constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('account_import');
    }

    /**
     * Returns boolean indicating whether import is currently running.
     *
     * @return boolean
     */

    function is_import_in_progress()
    {
        clearos_profile(__METHOD__, __LINE__);

		try {
            // TODO
			//return is_import_running();
            return TRUE;
		} catch (Exception $e) {
			return false;
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
			$file = new File(COMMON_TEMP_DIR . "/" . $filename, false);

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
	 * @throws Engine_Exception
	 */

	function import()
	{
        clearos_profile(__METHOD__, __LINE__);

		if (is_import_running())
			throw new Engine_Exception(lang('account_import_running'), CLEAROS_ERROR);

		try {
			if ($this->csv_file == null)
				throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);
			$file = new File($this->csv_file, true);

			if (!$file->exists())
				throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);

			$file->move_to(self::FILE_IMPORT);
			try {
				$options = array();
				$options['background'] = true;
				$shell = new Shell_Exec;
				$shell->Execute(self::FILE_IMPORT_SCRIPT, '', true, $options);
			} catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
			}
		} catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}
	}

	/**
	 * Set the internal CSV filename.
     * @param string $filename import filename
	 *
	 * @throws Engine_Exception, File_Not_Found_Exception
	 */

	function set_csv_file($filename)
	{
        clearos_profile(__METHOD__, __LINE__);

		try {
			$file = new File($filename, true);
			if (!$file->exists())
				throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);
			$this->csv_file = $filename;
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
			if ($this->csv_file == null)
				throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);
			$csv = new File_CSV_DataSource();
			$csv->load($this->csv_file);
			return $csv->countRows();
		} catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		} catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
		}
	}

	/**
	 * Get the size of the CSV file.
	 *
	 * @return string the size of the file
	 * @throws EngineException
	 */

	function get_size()
	{
        clearos_profile(__METHOD__, __LINE__);

		try {
            $this->load->helper('number');
			if ($this->csv_file == null)
				throw new File_Not_Found_Exception(lang('account_import_csv_not_uploaded'), CLEAROS_ERROR);
			$file = new File($this->csv_file, true);
			return byte_format($file->get_size(), 1);
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

	/**
	 * Returns template type options.
	 *
	 * @return array
	 */

	public function get_template_types()
	{
        clearos_profile(__METHOD__, __LINE__);

		$options = array(
			self::FILE_ODS_TEMPLATE => lang('account_import_type_ods'),
			self::FILE_XLS_TEMPLATE => lang('account_import_type_xls'),
			self::FILE_CSV_TEMPLATE => lang('account_import_type_csv')
		);

		return $options;
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
			if (isset($userinfo[$attribute]) && $userinfo[$attribute] && !eregi('false|no', $userinfo[$attribute]))
				$userinfo[$attribute] = true;
			else
				$userinfo[$attribute] = false;
		}
	}

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

}
