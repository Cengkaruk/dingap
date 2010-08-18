<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2009 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * User import utility.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('Group.class.php');
require_once('GroupManager.class.php');
require_once('Hostname.class.php');
require_once('ShellExec.class.php');
require_once('Ssl.class.php');
require_once('User.class.php');
require_once(COMMON_CORE_DIR . '/scripts/userimport.inc.php');
require_once "File/CSV/DataSource.php";

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * UserImport.
 *
 * Basic user import utility using CSV.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2009, Point Clark Networks
 */

class UserImport extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const PATH_TEMPLATE = '/usr/share/system/modules/user-import/';
	const FILE_ODS_TEMPLATE = 'import_template.ods';
	const FILE_XLS_TEMPLATE = 'import_template.xls';
	const FILE_CSV_TEMPLATE = 'import_template.csv';
	const FILE_IMPORT_SCRIPT = '/var/webconfig/scripts/userimport.php';
	const FILE_IMPORT = '/usr/webconfig/tmp/import.csv';

	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $csv_file = null;
	
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * UserImport constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Is import running.
	 *
     * @returns  boolean  true if import is currently running
	 */

	function ImportInProgress()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		try {
			return IsImportRunning();
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Performs an export.
	 *
	 * @throws EngineException
	 */

	function Export()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$hostname = new Hostname();
			$filename = $hostname->Get() . ".csv";
			$file = new File(COMMON_TEMP_DIR . "/" . $filename, false);

			if ($file->Exists())
				$file->Delete();
			$file->Create("webconfig", "webconfig", 640);
			$file->AddLines("username,firstName,lastName,password,street,roomNumber,city,region," .
				"country,postalCode,organization,unit,telephone,fax,mailFlag,mailquota," .
				"proxyFlag,openvpnFlag,pptpFlag,sambaFlag,ftpFlag,webFlag,pbxState,pbxPresenceState,pbxExtension,groups" .
				"\n"
			);
			$usermanager = new UserManager();
			$groupmanager = new GroupManager();
			$userlist = $usermanager->GetAllUsers();
			$groups = $groupmanager->GetGroupList(GroupManager::TYPE_USER_DEFINED);
			foreach ($userlist as $username) {
				// Reset group list
				$grouplist = '';
				$user = new User($username);
				$userinfo = $user->GetInfo();
				foreach ($groups as $group) {
					if (in_array($username, $group['members']))
						$grouplist .= $group['group'] . ',';
				}

				$grouplist = preg_replace('/(.*),$/', '${1}', $grouplist);
				
				$file->AddLines(
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
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Performs an import.
	 *
	 * @throws EngineException
	 */

	function Import()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (IsImportRunning()) {
			throw new EngineException(USERIMPORT_LANG_RUNNING, COMMON_WARNING);
        }

		try {
			if ($this->csv_file == null)
				throw new FileNotFoundException(USERIMPORT_LANG_ERRMSG_CSV_NOT_UPLOADED, COMMON_ERROR);
			$file = new File($this->csv_file, true);

			if (!$file->Exists())
				throw new FileNotFoundException(USERIMPORT_LANG_ERRMSG_CSV_NOT_UPLOADED, COMMON_ERROR);

			$file->MoveTo(self::FILE_IMPORT);
			try {
				$options = array();
				$options['background'] = true;
				$shell = new ShellExec;
				$shell->Execute(self::FILE_IMPORT_SCRIPT, '', true, $options);
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
		} catch (FileNotFoundException $e) {
			throw new FileNotFoundException($e->GetMessage(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Set the internal CSV filename.
	 *
	 * @throws EngineException, FileNotFoundException
	 */

	function SetCsvFile($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		try {
			$file = new File($filename, true);
			if (!$file->Exists())
				throw new EngineException(USERIMPORT_LANG_ERRMSG_CSV_NOT_UPLOADED, COMMON_ERROR);
			$this->csv_file = $filename;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Get the number of records.
	 *
	 * @return integer the number of records
	 * @throws EngineException
	 */

	function GetNumberOfRecords()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		try {
			if ($this->csv_file == null)
				throw new EngineException(USERIMPORT_LANG_ERRMSG_CSV_NOT_UPLOADED, COMMON_ERROR);
			$csv = new File_CSV_DataSource();
			$csv->load($this->csv_file);
			return $csv->countRows();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Get the size of the CSV file.
	 *
	 * @return string the size of the file
	 * @throws EngineException
	 */

	function GetSize()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			if ($this->csv_file == null)
				throw new EngineException(USERIMPORT_LANG_ERRMSG_CSV_NOT_UPLOADED, COMMON_ERROR);
			$file = new File($this->csv_file, true);
			return $this->_Size($file->GetSize(), 1);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Add a user.
	 *
	 * @throws EngineException
	 */

	function AddUser($userinfo)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$this->_ConvertFlags($userinfo);

			// Need to pop a few variables into place
			$username = strtolower($userinfo['username']);
			unset($userinfo['username']);
			// Add password verification
			$userinfo['verify'] = $userinfo['password'];
			$userinfo['webconfigFlag'] = true;
			$user = new User($username);

			try {
				$user->Add($userinfo);
			} catch (UserAlreadyExistsException $e) {
				throw new UserAlreadyExistsException($e->GetMessage(), COMMON_ERROR);
			} catch (Exception $e) {
				$errors = $user->GetValidationErrors();
				throw new ValidationException($errors[0]);
			}

			// Create SSL Certificate (TODO -- move to User class)
			$ssl = new Ssl();

			try {
				if ($ssl->ExistsDefaultClientCertificate($username))
					$ssl->DeleteDefaultClientCertificate($username);

				$ssl->CreateDefaultClientCertificate($username, $userinfo['password'], $userinfo['password']);
			} catch (Exception $e) {
				$errors = $ssl->GetValidationErrors();
				throw new ValidationException($errors[0]);
			}

		} catch (UserAlreadyExistsException $e) {
			throw new UserAlreadyExistsException($e->GetMessage(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new ValidationException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Add a user to a group.
	 *
	 * @throws EngineException, GroupNotFoundException
	 */

	function AddUserToGroup($username, $group)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// We do this on a group by group basis so we can log individual errors
		try {
			$group = new Group($group);
			$group->AddMember($username);
		} catch (GroupNotFoundException $e) {
			throw new GroupNotFoundException($e->GetMessage(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns template type options.
	 *
	 * @return array
	 */

	public function GetTemplateTypes()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$options = array(
			self::FILE_ODS_TEMPLATE => USERIMPORT_LANG_TYPE_ODS,
			self::FILE_XLS_TEMPLATE => USERIMPORT_LANG_TYPE_XLS,
			self::FILE_CSV_TEMPLATE => USERIMPORT_LANG_TYPE_CSV
		);

		return $options;
	}
	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}

	/**
     * Formats a value into a human readable byte size.
     * @param  float  $input  the value
     * @param  int  $dec  number of decimal places
     *
     * @returns  string  the byte size suitable for display to end user
     */

    function _Size($input, $dec)
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        $prefix_arr = array(" B", "KB", "MB", "GB", "TB");
        $value = round($input, $dec);
        $i=0;
        while ($value>1024) {
            $value /= 1024;
            $i++;
        }
        $display = round($value, $dec) . " " . $prefix_arr[$i];
        return $display;
    }

	function _ConvertFlags(&$userinfo)
	{
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

}

// vim: syntax=php ts=4
?>
