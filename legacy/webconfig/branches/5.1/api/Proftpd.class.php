<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * FTP (ProFTP) class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Daemon.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ProFTP FTP server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Proftpd extends Daemon
{
    const FILE_CONFIG = '/etc/proftpd.conf';

	///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Locale constructor.
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        parent::__construct('proftpd');

        require_once(GlobalGetLanguageTemplate(__FILE__));
    }

	/**
	 * Returns max instances.
	 *
	 * @return int max instances
	 * @throws EngineException
	 */

	function GetMaxInstances()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$retval = $file->LookupValue("/^MaxInstances\s+/i");
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		return $retval;
	}

	/**
	 * Returns port number.
	 *
	 * @return int port
	 * @throws EngineException
	 */

	function GetPort()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$retval = $file->LookupValue("/^Port\s+/i");
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		return $retval;
	}

	/**
	 * Returns server name.
	 *
	 * @return string server name
	 * @throws EngineException
	 */

	function GetServerName()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$retval = $file->LookupValue("/^ServerName\s+/i");
			$retval = preg_replace("/\"/", "", $retval);
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		return $retval;
	}

	/**
	 * Sets max instances.
	 *
	 * @param int $maxinstances max instances
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetMaxInstances($maxinstances)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidMaxInstances($maxinstances))
            throw new ValidationException(PROFTP_LANG_ERRMSG_MAXINSTANCES_INVALID);

		// Update tag if it exists
		//------------------------

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^MaxInstances\s+/i", "MaxInstances $maxinstances\n");

			// If tag does not exist, add it
			//------------------------------

			if (! $match)
				$file->AddLinesAfter("MaxInstances $maxinstances\n", "/^[^#]/");

		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

	}

	/**
	 * Sets port number.
	 *
	 * @param int $port port
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetPort($port)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidPort($port))
            throw new ValidationException(PROFTP_LANG_ERRMSG_PORT_INVALID);

		// Update tag if it exists
		//------------------------

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^Port\s+/i", "Port $port\n");

			// If tag does not exist, add it
			//------------------------------

			if (! $match)
				$file->AddLinesAfter("Port $port\n", "/^[^#]/");

		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}


	/**
	 * Sets server name.
	 *
	 * @param string $servername server name
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetServerName($servername)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidServerName($servername))
            throw new ValidationException(PROFTP_LANG_ERRMSG_SERVERNAME_INVALID);

		// Update tag if it exists
		//------------------------

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^ServerName\s+/i", "ServerName \"$servername\"\n");

			// If tag does not exist, add it
			//------------------------------

			if (! $match)
				$file->AddLinesAfter("ServerName \"$servername\"\n", "/^[^#]/");

		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/*************************************************************************/
	/* V A L I D A T I O N   R O U T I N E S                                 */
	/*************************************************************************/

	/**
	 * Validation routine for servername
	 *
	 * @param string $servername server name
	 * @return boolean true if servername is valid
	 */

	function IsValidServerName($servername)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^[A-Za-z0-9\.\- ]+$/", $servername))
			return true;
		$this->AddValidationError(PROFTP_LANG_ERRMSG_SERVERNAME_INVALID, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for maxinstances
	 *
	 * @param string $maxinstances max instances
	 * @return boolean true if maxinstances is valid
	 */

	function IsValidMaxInstances($maxinstances)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^[0-9]+$/", $maxinstances))
			return true;
		$this->AddValidationError(PROFTP_LANG_ERRMSG_MAXINSTANCES_INVALID, __METHOD__, __LINE__);
		return false;
	}


	/**
	 * Validation routine for port
	 *
	 * @param int $port port
	 * @return boolean true if port is valid
	 */

	function IsValidPort($port)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^[0-9]+$/", $port))
			return true;
		$this->AddValidationError(PROFTP_LANG_ERRMSG_PORT_INVALID, __METHOD__, __LINE__);
		return false;
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
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        parent::__destruct();
    }
}

// vim: syntax=php ts=4
?>
