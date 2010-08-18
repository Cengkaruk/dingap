<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2008 Point Clark Networks.
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
 * Backup/restore utility.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('Hostname.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * BackupRestore.
 *
 * Basic backup/restore utility for server configuration only.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2008, Point Clark Networks
 */

class BackupRestore extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/backup.conf';
	const PATH_ARCHIVE = '/var/lib/backuprestore';
	const PATH_UPLOAD = '/var/lib/backuprestore/upload';
	const CMD_TAR = '/bin/tar';
	const CMD_LS = '/bin/ls';
	const FILE_LIMIT = 10; // Maximum number of archives to keep
	const SIZE_LIMIT = 51200; // Maximum size of all archives

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * BackupRestore constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Performs a backup of the system configuration files.
	 *
	 * @return string path/filename of the backup
	 * @throws EngineException
	 */

	function Backup()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Load the file manifest
		//-----------------------

		try {
			$files = $this->_ReadConfig();

			if (! $files)
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$manifest = '';

		foreach ($files as $file)
			$manifest .= $file  . ' ';

		$manifest = rtrim($manifest);

		// Determine the filename (using the hostname)
		//--------------------------------------------

		try {
			$hostname = new Hostname();
			$prefix = $hostname->GetActual();
			$prefix .= "-";
		} catch (Exception $ignore) {
			// No prefix...no fatal
			$prefix = "";
		}

		$filename = "backup-" . $prefix . strftime("%m-%d-%Y-%H-%M-%S", time()) . ".tar.gz";

		// Create the temporary folder for the archive
		//--------------------------------------------

		try {
			$folder = new Folder(self::PATH_ARCHIVE);

			if (!$folder->Exists())
				$folder->Create("root", "root", 700);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Dump the current LDAP database
		//-------------------------------

		if (file_exists(COMMON_CORE_DIR . "/api/ClearDirectory.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/ClearDirectory.class.php");

			try {
				$directory = new ClearDirectory();
				$directory->Export();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
		}

		// Create the backup
		//------------------

		// TODO: move hard-coded excludes to /etc/backup.conf

		try {
			$shell = new ShellExec();
			$attr = '--exclude /etc/system/database --exclude /etc/postfix/filters --ignore-failed-read -cpzf ';

			if ($shell->Execute(self::CMD_TAR, $attr . self::PATH_ARCHIVE . '/' . $filename . ' ' . $manifest, true) != 0)
				throw new EngineException($shell->GetFirstOutputLine());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$archive = new File(self::PATH_ARCHIVE . '/' . $filename);
			$archive->Chmod(600);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return self::PATH_ARCHIVE . '/' . $filename;
	}

	/**
	 * Returns an array of archived backups on the server.
	 *
	 * @return array a list of archives
	 * @throws EngineException
	 */

	function GetArchiveList()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->_GetList(self::PATH_ARCHIVE);
	}

	/**
	 * Returns an array of uploaded backups on the server.
	 *
	 * @return array a list of uploads
	 * @throws EngineException
	 */

	function GetUploadList()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->_GetList(self::PATH_UPLOAD);
	}

	/**
	 * Purges archives based on date of creation and/or size restrictions.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function Purge()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$files = 0;
		$tally = 0;

		$list = array();
		$uploads = array();
		$archives = array();

		try {
			$archives = $this->GetArchiveList();
			$uploads = $this->GetUploadList();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Clean out any upload files
		//---------------------------

		foreach ($uploads as $archive) {
			try {
				$file = new File(self::PATH_UPLOAD . "/" . $archive);
				$file->Delete();
			} catch (Exception $e) {
				// Nnt fatal
			}
		}

		// Clean out old archives
		//-----------------------

		$shell = new ShellExec();
		$parts = array();

		foreach ($archives as $archive) {
			$date_regex = '([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{2})-([0-9]{2})';

			if (!preg_match("/^backup-(.*)-$date_regex.tar.gz$/", $archive, $parts))
				continue;

			$stamp = mktime($parts[5], $parts[6], $parts[7], $parts[2], $parts[3], $parts[4]);

			$list[$stamp]['archive'] = $archive;

			try {
				$shell = new ShellExec();

				if ($shell->Execute(self::CMD_LS, '-sC1 ' . self::PATH_ARCHIVE . '/' . $archive, true) != 0)
					throw new EngineException($shell->GetFirstOutputLine());

				unset($list[$stamp]);

			} catch (Exception $e) {
				// Not fatal
				continue;
			}

			list($size, $name) = split(" ", $shell->GetFirstOutputLine(), 2);
			$list[$stamp]["size"] = $size;
			$list[$stamp]["archive"] = $name;

			$files++;
			$tally += $size;
		}

		ksort($list, SORT_NUMERIC);

		while ($files > self::FILE_LIMIT || $tally > self::SIZE_LIMIT) {
			$archive = array_shift($list);
			$files--;
			$tally -= $archive["size"];

			try {
				$file = new File($archive["archive"]);
				$file->Delete();
			} catch (Exception $e) {
				// Not fatal
			}
		}
	}

	/**
	 * Performs a restore of the system configuration files from an archive backup.
	 *
	 * @param string $archive filename of the archive to restore
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function RestoreByArchive($archive)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_Restore(self::PATH_ARCHIVE, $archive);
	}

	/**
	 * Performs a restore of the system configuration files by uploading backup.
	 *
	 * @param string $archive filename of the upload to restore
	 * @throws EngineException, ValidationException
	 */

	function RestoreByUpload($archive)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_Restore(self::PATH_UPLOAD, $archive);
		$this->Purge();
	}

	/**
	 * Verifies version information.
	 *
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function VerifyArchive($fullpath)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		try {
			$file = new File($fullpath);
			if (! $file->Exists())
				throw new FileNotFoundException($fullpath);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Check for /etc/release file (not stored in old versions)
		//---------------------------------------------------------

		$shell = new ShellExec();

		try {
			$retval = $shell->Execute(self::CMD_TAR, "-tzvf $fullpath", true);
			if ($retval != 0)
				throw new EngineException($shell->GetFirstOutputLine());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$files = $shell->GetOutput();
		$release_found = false;

		foreach ($files as $file) {
			if (preg_match("/ etc\/release$/", $file))
				$release_found = true;
		}

		if (! $release_found)
			throw new EngineException(BACKUPRESTORE_LANG_ERRMSG_RELEASE_MISSING, COMMON_ERROR);

		// Check to see if release file matches
		//-------------------------------------

		try {
			$retval = $shell->Execute(self::CMD_TAR, "-O -C /var/tmp -xzf $fullpath etc/release", true);

			if ($retval != 0)
				throw new EngineException($shell->GetFirstOutputLine());

			$archive_version = trim($shell->GetFirstOutputLine());

			$file = new File("/etc/release");
			$current_version = trim($file->GetContents());

			if ($current_version != $archive_version)
				throw new EngineException(BACKUPRESTORE_LANG_ERRMSG_RELEASE_MISMATCH . "(" . $archive_version . ")", COMMON_ERROR);

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Returns an array of archived backups on the server.
	 *
	 * @access private
	 * @return array a list of archives
	 * @throws EngineException
	 */

	private function _GetList($path)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$archives = array();

		try {
			$folder = new Folder($path);

			if (! $folder->Exists())
				throw new FolderNotFoundException($path, COMMON_ERROR);

			$contents = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (! $contents)
			return $archives;

		foreach ($contents as $value) {
			if (! preg_match("/tar.gz$/", $value))
				continue;

			$archives[] = $value;
		}

		return array_reverse($archives);
	}

	/**
	 * Reads configuration file.
	 * 
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	private function _ReadConfig()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$files = array();

		$config = new File(self::FILE_CONFIG);

		try {
			if (! $config->Exists())
				throw new FileNotFoundException(self::FILE_CONFIG, COMMON_ERROR);

			$contents = $config->GetContentsAsArray();

			foreach ($contents as $line) {
				if (preg_match("/^\s*#/", $line))
					continue;

				$files[] = $line;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $files;
	}

	/**
	 * Performs a restore for the given backup.
	 *
	 * @access private
	 * @param string $path path of the archive
	 * @param string $archive filename of the archive to restore
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	private function _Restore($path, $archive)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$fullpath = $path . '/' . $archive;

		$this->VerifyArchive($fullpath);

		$file = new File($fullpath);

		try {
			if (! $file->Exists())
				throw new FileNotFoundException($fullpath, COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$shell = new ShellExec();
			if ($shell->Execute(self::CMD_TAR, "-C / -xpzf $fullpath", true) != 0)
				throw new EngineException($shell->GetFirstOutputLine());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Reload the LDAP database and reset LDAP-related daemons
		//--------------------------------------------------------

		if (file_exists(COMMON_CORE_DIR . "/api/ClearDirectory.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/ClearDirectory.class.php");

			try {
				$directory = new ClearDirectory();
				$directory->Import(false);
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}

			if (file_exists(COMMON_CORE_DIR . "/api/UserManager.class.php")) {
				require_once(COMMON_CORE_DIR . "/api/UserManager.class.php");

				try {
					$usermanager = new UserManager();
					$usermanager->Synchronize();
				} catch (Exception $e) {
					throw new EngineException($e->GetMessage(), COMMON_ERROR);
				}
			}
		}
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
