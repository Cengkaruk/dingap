<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2006 Point Clark Networks.
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
 * System watch class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('File.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * Unknown state exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class SyswatchUnknownStateException extends EngineException
{
	/**
	 * SyswatchUnknownStateException constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg, $code)
	{
		parent::__construct($errmsg, $code);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * System watch class.
 *
 * System watcher keeps an eye on network up/down events, and various
 * other system health issues.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

class Syswatch extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $ifs_in_use = array();
	protected $ifs_working = array();
	protected $is_state_loaded = false;

	const FILE_STATE = '/var/lib/syswatch/state';
	const CMD_KILLALL = '/usr/bin/killall';
	const CONSTANT_UNKNOWN = 'unknown';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Syswatch constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("syswatch");
	}

	/**
	 * Returns list of working external (WAN) interfaces.
	 *
	 * Syswatch monitors the connections to the Internet.  A connection
	 * is considered online when it can ping the Internet.
	 *
	 * @return array list of working WAN interfaces
	 * @throws EngineException, SyswatchUnknownStateException
	 */

	function GetWorkingExternalInterfaces()
	{
		if (!$this->is_state_loaded)
			$this->LoadState();

		return $this->ifs_working;
	}

	/**
	 * Returns list of in use external (WAN) interfaces.
	 *
	 * Syswatch monitors the connections to the Internet.  A connection
	 * is considered in use when it can ping the Internet and is actively
	 * used to connect to the Internet.  A WAN interface used for only backup
	 * purposes is only included in this list when non-backup WANs are all down.
	 *
	 * @return array list of in use WAN interfaces
	 * @throws EngineException, SyswatchUnknownStateException
	 */

	function GetInUseExternalInterfaces()
	{
		if (!$this->is_state_loaded)
			$this->LoadState();

		return $this->ifs_in_use;
	}

	/**
	 * Loads state file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException, SyswatchUnknownStateException
	 */

	function LoadState()
	{
		$file = new File(self::FILE_STATE);
		$fileok = false;

		try {
			$fileok = $file->Exists();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// TODO: localize
		if (!$fileok)
			throw new SyswatchUnknownStateException("State of external interfaces unknown", COMMON_INFO);

		try {
			$lines = $file->GetContentsAsArray();
			foreach ($lines as $line) {
				$match = array();
				if (preg_match('/^SYSWATCH_WANIF=(.*)/', $line, $match)) {
					$ethraw = $match[1];
					$ethraw = preg_replace('/"/', '', $ethraw);
					$ethlist = explode(' ', $ethraw);
					$this->ifs_in_use = explode(' ', $ethraw);
					$this->is_state_loaded = true;
				}

				if (preg_match('/^SYSWATCH_WANOK=(.*)/', $line, $match)) {
					$ethraw = $match[1];
					$ethraw = preg_replace('/"/', '', $ethraw);
					$ethlist = explode(' ', $ethraw);
					$this->ifs_working = explode(' ', $ethraw);
					$this->is_state_loaded = true;
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Resets daemons after a network change.
	 *
	 * Daemons will automagically configure themselves depending on the
	 * network settings.  For example, if a user swaps the network roles
	 * of eth0 and eth1 (LAN/WAN to WAN/LAN), the Samba software will
	 * also swap its configuration around.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function ReconfigureNetworkSettings()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	
		try {
			$shell = new ShellExec();
			$shell->Execute(self::CMD_KILLALL, "-USR2 syswatch", true);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Reconfigures small bits of the system.
	 *
	 * There are some chicken and egg moments to re-configuring webconfig.
	 * Since webconfig is used to install software, it is not desirable
	 * to restart webconfig during any software installation.  This
	 * poses a problem to things like Horde web mail (which requires
	 * a webconfig restart).
	 *
	 * The syswatch daemon has a special signal to handle this situation. 
	 *
	 * @return void
	 * @throws EngineException
	 */

	function ReconfigureSystem()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	
		try {
			$shell = new ShellExec();
			$shell->Execute(self::CMD_KILLALL, "-USR1 syswatch", true);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Send signal to syswatch daemon.
	 *
	 * @param string $signal kill signal
	 * @return void
	 * @throws EngineException
	 */

	function SendSignal($signal)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	
		try {
			$shell = new ShellExec();
			$shell->Execute(self::CMD_KILLALL, "-$signal syswatch", true);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
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

// vim: syntax=php ts=4
?>
