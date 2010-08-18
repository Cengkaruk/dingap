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
 * Connection tracking class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('Network.class.php');
require_once('NetworkServices.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Connection tracking class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class ConnectionTracking extends Engine
{
	///////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////

	protected $services = array();

	const CMD_HPING2 = "/usr/sbin/hping2";
	const CMD_TRACKLIST = "/usr/sbin/tracklist";
	const CMD_ZGREP = "/usr/bin/zgrep";

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Connection tracking constructor.
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__construct();
    }

	/**
	* Returns list of ip_conntrack data.
	*
	* @return  mixed	array ip_conntrack data or false on error
	*/

	function GetList()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($this->services)) {
			$networkservices = new NetworkServices();
			$servicelist = $networkservices->GetList();
			$this->services = $servicelist;
		}

		$output = array();

		$shell = new ShellExec();

		try {
			// This command can return non-zero in normal operations (e.g. standalone mode)
			$retval = $shell->Execute(self::CMD_TRACKLIST, "-n", true);
			$output = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$tracklist = array();

		foreach($output as $line) {
			if (preg_match("/^PROT/", $line))
				continue;

			$linedata = preg_split('[\s]', $line, -1, PREG_SPLIT_NO_EMPTY);
			$ports = preg_split('/[->]/', end($linedata), -1, PREG_SPLIT_NO_EMPTY);

			$dest = (isset($ports[1])) ? $ports[1] : "";
			$protocol = isset($linedata[0]) ? strtolower($linedata[0]) : "";
			$nickname = isset($this->services[$dest][$protocol]['name']) ? $this->services[$dest][$protocol]['name'] : "";
			$linedata[] = strtoupper($nickname);
			$tracklist[] = $linedata;
		}

		return $tracklist;
	}

	/**
	 * Return sorted list of sources from ip_conntrack data.
	 *
	 * @return array sorted array of source address or error message
	 */

	function GetSourcesList()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$output = array();

		$shell = new ShellExec();

		try {
			$retval = $shell->Execute(self::CMD_TRACKLIST, "-s", true);
			if ($retval != 0)
				throw new EngineException($shell->GetLastOutputLine(), COMMON_WARNING);
			$output = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		natsort($output);
		array_unshift($output,'all');

		return array_unique($output);
	}

    /**
     * @access private
     */

    public function __destruct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__destruct();
    }
}

// vim: syntax=php ts=4
?>
