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
 * Netstat class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Netstat class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

class Netstat extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // F I E L D S
    ///////////////////////////////////////////////////////////////////////////

	const ENV_NETSTAT = 'COLUMNS=180';
	const CMD_NETSTAT = '/bin/netstat';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Netstat constructor.
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__construct();
    }

    /**
     * Execute the netstat command.
     *
     * @param boolean $hidesockets filter unix sockets from output
     * @param string $switches the switches to be used by netstat
     * @return int exit code from netstat execution
     */

    function Execute($switches, $hidesockets)
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        $shell_switches  = '';

        if ($switches != "") {
            $shell_switches = "-" . $switches;
            $shell_switches = str_replace("x", " --numeric-hosts ", $shell_switches);
            $shell_switches = str_replace("y", " --numeric-ports ", $shell_switches);
            $shell_switches = str_replace("z", " --numeric-users ", $shell_switches);
            $shell_switches = preg_replace("/- -/", "-", $shell_switches);
        }

		// set some flags so we know how to parse the data later
		$showstats = array();
		$colorize = array();
		
        preg_match("/r|i|g|s/", $switches, $showstats);
        preg_match("/n|N|x/", $switches, $colorize);

		$output = array();

		try {
			$shell = new ShellExec();
			$retval = $shell->Execute(self::CMD_NETSTAT, $shell_switches, true);

			if ($retval != 0)
				throw new EngineException($shell->GetLastOutputLine(), COMMON_WARNING);

			$output = $shell->GetOutput();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

/*
        if ($hidesockets & (!$showstats)) {
            $output = preg_grep("/^unix/",$this->raw_output,PREG_GREP_INVERT);
            $output = array_slice($output, 0, -2);
        } else {
            $output = $this->raw_output;
        }
*/

        return $output;
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
