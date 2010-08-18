<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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
 * Bnetd class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('ShellExec.class.php');
require_once('Locale.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Bnetd class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Bnetd extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	const CMD_DAEMON = '/usr/webconfig/bin/bnetd';

	const FILE_SOCKET = '/tmp/.bnetd-socket';
	const FILE_LOCKFILE = '/var/run/bnetd.pid';

	const SOCKET_TIMEOUT = 5;

	const CLIENT_AUTH = 1;
	const CLIENT_COMMAND = 2;
	const CLIENT_CLOSE = 3;

	const SERVER_OK = 1;
	const SERVER_ERROR = 2;
	const SERVER_REPLY = 3;

	/**
	 * @var handle socket stream handle
	 */

	protected $sock = null;

	/**
	 * @var string Bacula director host
	 */

	protected $host = 'localhost';

	/**
	 * @var integer Session timeout in seconds
	 */

	protected $session_timeout = 1800;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Bnetd constructor.
	 *
	 * @param integer $session_timeout Optional session time-out value in seconds
	 */

	function __construct($session_timeout = 0)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		settype($session_timeout, 'integer');

		if ($session_timeout > 0)
			$this->session_timeout = $session_timeout;

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Check if lock file is present and bnetd is running...
	 *
	 * @return boolean true if bnetd is running
	 * @throws EngineException
	 */

	final public function IsRunning()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_SOCKET);

			if (! $file->Exists()) return false;

			$file = new File(self::FILE_LOCKFILE);

			if (! $file->Exists()) return false;

			$contents = $file->GetContents();
			if (! strlen($contents)) return false;

			list($pid) = sscanf($contents, '%d');

			$file = new File("/proc/$pid");

			if (! $file->Exists()) return false;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		// Seems to be running...
		return true;
	}

	/**
	 * Open a connection to bnetd and attempt to authenticate with Bacula.
	 *
	 * @param string $passwd Bacula director password
	 * @param string $host Optional Bacula director host (default localhost)
	 * @return void
	 * @throws EngineException
	 */

	final public function Connect($passwd, $host = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Already connected?
		if ($this->sock != null)
			throw new EngineException(BNETD_LANG_ERR_ALREADY_CONNECTED, COMMON_WARNING);

		// Set hostname
		if ($host != null) $this->host = $host;

		// Start daemon if it isn't already running
		if (! $this->IsRunning()) $this->Spawn();

		// Connect to bnetd socket
		$errno = 0;
		$errstr = null;

		if (! ($this->sock = fsockopen('unix://' . self::FILE_SOCKET,
			0, $errno, $errstr, self::SOCKET_TIMEOUT))) {
			throw new EngineException(BNETD_LANG_ERR_CONNECT_FAILED . " - $errstr",
				COMMON_ERROR);
		}

		// Authenticate
		if (self::SendCommand(self::CLIENT_AUTH, $passwd) == -1)
			throw new EngineException(BNETD_LANG_ERR_SEND, COMMON_ERROR);

		$code = 0;
		$length = 0;
		$payload = null;
		
		if (self::RecvResponse($code, $length, $payload) == -1)
			throw new EngineException(BNETD_LANG_ERR_RECV, COMMON_ERROR);

		if ($code != self::SERVER_OK) {
			if ($code == self::SERVER_ERROR && $length > 0) {
				throw new EngineException(BNETD_LANG_ERR_AUTH_FAILURE . " - $payload",
					COMMON_ERROR);
			}
			throw new EngineException(BNETD_LANG_ERR_UNEXPECTED_RESPONSE, COMMON_ERROR);
		}
	}

	/**
	 * Shutdown the running bnetd server.
	 *
	 * @return void
	 * @throws EngineException
	 */

	final public function Shutdown()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Send shutdown command
		if (self::SendCommand(self::CLIENT_CLOSE) == -1)
			throw new EngineException(BNETD_LANG_ERR_SEND, COMMON_WARNING);

		// Accepted?
		$code = 0;
		$length = 0;
		$payload = null;
		
		if (self::RecvResponse($code, $length, $payload) == -1)
			throw new EngineException(BNETD_LANG_ERR_RECV, COMMON_ERROR);

		if ($code != self::SERVER_OK) {
			if($code == self::SERVER_ERROR && $length) {
				throw new EngineException(BNETD_LANG_ERR_SHUTDOWN . " - $payload",
					COMMON_ERROR);
			}
			else throw new EngineException(BNETD_LANG_ERR_SHUTDOWN, COMMON_ERROR);
		}

		$this->sock = null;
	}

	/**
	 * Send command to Bacula director via bnetd.
	 *
	 * @param integer $code bnetd client code.
	 * @param string $payload Optional command payload.
	 * @return integer 0 on success, -1 on failure
	 * @throws EngineException
	 */

	final public function SendCommand($code, $payload = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Connected?
		if ($this->sock == null)
			throw new EngineException(BNETD_LANG_ERR_NOT_CONNECTED, COMMON_ERROR);

		// Create packet
		$length = strlen($payload);
		$buffer = pack('V', $code) . pack('V', $length) . $payload;

		// Write packet to socket
		if(fwrite($this->sock, $buffer) != strlen($buffer)) return -1;

		// Flush stream buffer
		fflush($this->sock);

		return 0;
	}

	/**
	 * Receive Bacula director response via bnetd.
	 *
	 * @param integer $code bnetd server response variable address.
	 * @param integer $length Payload length variable address.
	 * @param string $payload Conditional response variable address.
	 * @return integer 0 on success, -1 on failure
	 * @throws EngineException
	 */

	final public function RecvResponse(&$code, &$length, &$payload)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Reset packet variables
		$code = 0; $length = 0; $payload = null;

		// Connected?
		if ($this->sock == null)
			throw new EngineException(BNETD_LANG_ERR_NOT_CONNECTED, COMMON_ERROR);

		// Read 8-byte packet header (code:length)
		$buffer = stream_get_contents($this->sock, 8);
		if(strlen($buffer) != 8) return -1;

		// Unpack header
		$header = unpack('Vcode/Vlength', $buffer);
		$code = $header['code'];
		$length = $header['length'];

		// Read payload if non-zero in length
		if($length > 0)
		{
			$payload = stream_get_contents($this->sock, $length);
			if(strlen($payload) != $length) return -1;
		}

		return 0;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Spawn a an instance of the Bacula network daemon (bnetd)
	 *
	 * @return void
	 * @throws EngineException
	 */

	final private function Spawn()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$options['env'] = 'BNETD_HOST=' . escapeshellarg($this->host);
			$options['env'] .= ' BNETD_SESSION_TIMEOUT=' . $this->session_timeout;
			if ($shell->Execute(self::CMD_DAEMON, '', true, $options) != 0) {
				echo BNETD_LANG_ERR_SPAWN_FAILURE . "\n";
				exit;
			}

			// Wait for socket file to appear (for up to ~1 second)...
			$file = new File(self::FILE_SOCKET);
			for ($i = 0; $i < 10 && !$file->Exists(); $i++) usleep(100000);

			// TODO: Check again, throw exception if the socket still isn't there?

		} catch (Exception $e) {
				echo $e->getMessage() . "\n";
				exit;
		}
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->sock != null) fclose($this->sock);

		parent::__destruct();
	}

}

// vim: syntax=php ts=4
?>
