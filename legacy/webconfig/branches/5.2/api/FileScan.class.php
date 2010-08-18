<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2007 Point Clark Networks
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
 * FileScan base class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Cron.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('Daemon.class.php');
require_once(COMMON_CORE_DIR . '/scripts/avscan.inc.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * FileScan base class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2007, Point Clark Networks
 */

class FileScan extends Engine
{
	///////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////

	const FILE_AVSCAN = 'avscan.php';		// PHP antivirus scanner (wrapper)
	const CMD_KILLALL = '/usr/bin/killall';	// killall command

	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	public function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds directory to scan list.
	 *
	 * @param string $dir Directory to scan
	 * @throws EngineException
	 */

	public function AddDirectory($dir)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: Wrong, should be using File class
		if (!file_exists($dir)) {
			throw new EngineException(ANTIVIRUS_LANG_DIR_NOT_FOUND, COMMON_ERROR);
		}

		$dirs = $this->GetDirectories();

		if (count($dirs) && in_array($dir, $dirs)) {
			throw new EngineException(ANTIVIRUS_LANG_DIR_EXISTS, COMMON_ERROR);
		}

		$dirs[] = $dir; sort($dirs);

		$file = new File(AVSCAN_CONFIG);

		if(!$file->Exists()) {
			try {
				$file->Create('root', 'root', '0644');
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
		}

		try {
			$file->DumpContentsFromArray($dirs);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Removes directory from scan list.
	 *
	 * @param string $dir Directory to remove from scan
	 * @throws EngineException
	 */

	public function RemoveDirectory($dir)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$dirs = $this->GetDirectories();

		if (!count($dirs) || !in_array($dir, $dirs))
			throw new EngineException(ANTIVIRUS_LANG_DIR_NOT_FOUND, COMMON_ERROR);

		foreach($dirs as $id => $entry) {
			if ($entry != $dir) continue;
			unset($dirs[$id]);
			sort($dirs);
			break;
		}

		$file = new File(AVSCAN_CONFIG);

		try {
			$file->DumpContentsFromArray($dirs);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Retturns array of directories configured to scan for viruses.
	 *
	 * @return array of directory names
	 * @throws EngineException
	 */

	public function GetDirectories()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$dirs = array();
		$fh = @fopen(AVSCAN_CONFIG, 'r');

		if(!$fh) return $dirs;

		while (!feof($fh)) {
			$dir = chop(fgets($fh, 4096));
			// TODO: Wrong, should be using File class
			if (strlen($dir) && file_exists($dir)) $dirs[] = $dir;
		}

		fclose($fh);
		sort($dirs);

		return $dirs;
	}

	/**
	 * Returns array of preset directories.
	 *
	 * @return array of human directory names keyed by filessytem directory name
	 */

	public function GetDirectoryPresets()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$AVDIRS = array();
		
		require('FileScan.list.php');

		$dirs = $AVDIRS;

		foreach ($dirs as $dir => $label) {
			// TODO: Wrong, should be using File class
			if (!file_exists($dir)) unset($dirs[$dir]);
		}

		return $dirs;
	}

	/**
	 * Deletes a virus.
	 *
	 * @param string $hash MD5 hash of virus filename to delete
	 * @throws EngineException
	 */

	public function DeleteVirus($hash)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!file_exists(AVSCAN_STATE)) {
			throw new EngineException(ANTIVIRUS_LANG_STATE_ERROR, COMMON_ERROR);
		}

		// XXX: Here we use fopen rather than the File class.  This is because the File
		// class provides us with no way to do file locking (flock).  The state file
		// is therefore owned by webconfig so that we can manipulate it's contents.
		if (!($fh = @fopen(AVSCAN_STATE, 'a+'))) {
			throw new EngineException(ANTIVIRUS_LANG_STATE_ERROR, COMMON_ERROR);
		}

		$state = array();
		ResetState($state);

		if ((UnserializeState($fh, $state)) === false) {
			fclose($fh);
			throw new EngineException(ANTIVIRUS_LANG_STATE_ERROR, COMMON_ERROR);
		}

		if (!isset($state['virus'][$hash])) {
			throw new EngineException(ANTIVIRUS_LANG_FILE_NOT_FOUND, COMMON_ERROR);
		}

		try {
			$virus = new File($state['virus'][$hash]['filename'], true);
			$virus->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		// Update state file, delete virus
		unset($state['virus'][$hash]);
		SerializeState($fh, $state);
	}

	/**
	 * Quarantines a virus.
	 *
	 * @param string $hash MD5 hash of virus filename to quarantine
	 * @throws EngineException
	 */

	public function QuarantineVirus($hash)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!file_exists(AVSCAN_STATE)) {
			throw new EngineException(ANTIVIRUS_LANG_STATE_ERROR, COMMON_ERROR);
		}

		// XXX: Here we use fopen rather than the File class.  This is because the File
		// class provides us with no way to do file locking (flock).  The state file
		// is therefore owned by webconfig so that we can manipulate it's contents.
		if (!($fh = @fopen(AVSCAN_STATE, 'a+'))) {
			throw new EngineException(ANTIVIRUS_LANG_STATE_ERROR, COMMON_ERROR);
		}

		$state = array();
		ResetState($state);

		if ((UnserializeState($fh, $state)) === false) {
			fclose($fh);
			throw new EngineException(ANTIVIRUS_LANG_STATE_ERROR, COMMON_ERROR);
		}

		if (!isset($state['virus'][$hash])) {
			throw new EngineException(ANTIVIRUS_LANG_FILE_NOT_FOUND, COMMON_ERROR);
		}

		try {
			$virus = new File($state['virus'][$hash]['filename'], true);
			$virus->MoveTo(AVSCAN_QUARANTINE . "/$hash.dat");
			$virus = new File(AVSCAN_QUARANTINE . "/$hash.nfo");
			$virus->Create('webconfig', 'webconfig', '0640');
			$virus->AddLines(serialize($state['virus'][$hash]));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		// Update state file, delete virus
		unset($state['virus'][$hash]);
		SerializeState($fh, $state);
	}

	/**
	 * Returns array of quarantined viruses.
	 *
	 * @returns array Array of viruses in quarantine
	 * @throws EngineException
	 */

	public function GetQuarantinedViruses()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$dir = new Folder(AVSCAN_QUARANTINE, true);
			$files = $dir->GetListing();
		} catch (FolderNotFoundException $e) {
			return array();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$viruses = array();

		foreach ($files as $file) {
			if (stristr($file, '.nfo') === false) continue;

			try {
				$nfo = new File(AVSCAN_QUARANTINE . "/$file", true);
				$buffer = unserialize($nfo->GetContents());
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}

			$viruses[md5($buffer['filename'])] = $buffer;
		}

		return $viruses;
	}

	/**
	 * Restores a quarantined virus to its orignal location/filename.
	 *
	 * @param string $hash MD5 hash of virus filename to restore
	 * @return void
	 * @throws EngineException
	 */

	public function RestoreQuarantinedVirus($hash)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$nfo = new File(AVSCAN_QUARANTINE . "/$hash.nfo", true);
			$virus = unserialize($nfo->GetContents());
			$dat = new File(AVSCAN_QUARANTINE . "/$hash.dat", true);
			$dat->MoveTo($virus['filename']);
			$nfo->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes a quarantined virus.
	 *
	 * @param string $hash MD5 hash of virus filename to delete
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteQuarantinedVirus($hash)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$nfo = new File(AVSCAN_QUARANTINE . "/$hash.nfo", true);
			$nfo->Delete();
			$dat = new File(AVSCAN_QUARANTINE . "/$hash.dat", true);
			$dat->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Starts virus scanner.
	 *
	 * @throws EngineException
	 * @return void
	 */

	public function StartScan()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (IsScanRunning()) {
			throw new EngineException(ANTIVIRUS_LANG_RUNNING, COMMON_WARNING);
		}

		$this->EnableUpdates();

		try {
			$options = array();
			$options['background'] = true;
			$shell = new ShellExec;
			$shell->Execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_AVSCAN,
				'', true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Stops virus scanner.
	 *
	 * @throws EngineException
	 * @return void
	 */

	public function StopScan()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!IsScanRunning()) {
			throw new EngineException(ANTIVIRUS_LANG_NOT_RUNNING, COMMON_WARNING);
		}

		try {
			$options = array();
			$options['background'] = true;
			$shell = new ShellExec;
			$shell->Execute(self::CMD_KILLALL,
				self::FILE_AVSCAN . ' ' . basename(AVSCAN_SCANNER), true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets an antivirus schedule.
	 *
	 * @param string $minute cron minute value
	 * @param string $hour cron hour value
	 * @param string $dayofmonth cron day-of-month value
	 * @param string $month cron month value
	 * @param string $dayofweek cron day-of-week value
	 * @return void
	 * @throws EngineException
	 */

	public function SetScanSchedule($minute, $hour, $dayofmonth, $month, $dayofweek)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$cron = new Cron();

		try {
			$cron->AddCrondConfigletByParts('app-antivirus',
				$minute, $hour, $dayofmonth, $month, $dayofweek,
				'root', COMMON_CORE_DIR . '/scripts/' . self::FILE_AVSCAN . " >/dev/null 2>&1");
		} catch(Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Removes an antivirus schedule.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function RemoveScanSchedule()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$cron = new Cron();

		try {
			if ($cron->ExistsCrondConfiglet('app-antivirus'))
				$cron->DeleteCrondConfiglet('app-antivirus');
		} catch(Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns configured antivirus schedule.
	 *
	 * @return array of the scanner's configured schedule. 
	 * @throws EngineException
	 */

	public function GetScanSchedule()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$hour = '*';
		$dayofmonth = '*';
		$month = '*';
		$cron = new Cron();

		if (!$cron->ExistsCrondConfiglet('app-antivirus')) return array('*', '*', '*');

		try {
			list($minute, $hour, $dayofmonth, $month, $dayofweek) = explode(' ',
				$cron->GetCrondConfiglet('app-antivirus'), 5);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return array($hour, $dayofmonth, $month);
	}

	/**
	 * Checks for existence of scan schedule.
	 *
	 * @return boolean true if a cron configlet exists.
	 * @throws EngineException
	 */

	public function ScanScheduleExists()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$cron = new Cron();
		return $cron->ExistsCrondConfiglet('app-antivirus');
	}

	/**
	 * Enables antivirus definition updates.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function EnableUpdates()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$freshclam = new Daemon("freshclam");
			$freshclam->SetBootState(true);
			$freshclam->SetRunningState(true);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vi: ts=4
?>
