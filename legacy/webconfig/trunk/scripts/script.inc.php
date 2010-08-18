<?php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks
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

// For checking 'stat()' mode bits
define('S_IFMT', 0170000);
define('S_IFIFO', 0010000);

// Base script exception class
class WebconfigScriptException extends Exception
{
	const CODE_NULL_CODE = -1;

	public function __construct($reason, $code)
	{
		parent::__construct($reason, $code);
	}

	public final function GetExceptionId()
	{
		$code_name = 'CODE_NULL_CODE';
		$instance = new ReflectionClass($this);
		$constants = $instance->getConstants();
		foreach ($constants as $name => $value) {
			if (!preg_match('/^CODE_/', $name)) continue;
			if ($value != $this->getCode()) continue;
			$code_name = $name;
			break;
		}
		return sprintf('%s::%s', get_class($this), $code_name);
	}
}

// State file exception class
class StateException extends WebconfigScriptException
{
	const CODE_OPEN = 1;
	const CODE_READ = 2;
	const CODE_CHMOD = 3;
	const CODE_CHOWN = 4;
	const CODE_CHGRP = 5;
	const CODE_INVALID_RESOURCE = 6;

	public function __construct($code, $filename)
	{
		parent::__construct($filename, $code);
	}
}

// File lock exception class
class FlockException extends WebconfigScriptException
{
	const CODE_OPEN = 1;
	const CODE_READ = 2;
	const CODE_WRITE = 3;
	const CODE_LOCK = 4;
	const CODE_UNLOCK = 5;
	const CODE_TRUNCATE = 6;
	const CODE_SEEK = 7;
	const CODE_INVALID_RESOURCE = 8;

	public function __construct($code)
	{
		parent::__construct('FlockException', $code);
	}
}

// Logger exception class
class LogException extends WebconfigScriptException
{
	const CODE_OPEN = 1;
	const CODE_WRITE = 2;
	const CODE_SYSLOG_OPEN = 3;
	const CODE_INVALID_RESOURCE = 4;

	public function __construct($code, $filename = null)
	{
		if ($filename != null)
			parent::__construct($filename, $code);
		else
			parent::__construct('LogException', $code);
	}
}

// Lock file exception class
class LockException extends WebconfigScriptException
{
	const CODE_OPEN = 1;
	const CODE_CREATE = 2;

	public function __construct($code)
	{
		parent::__construct('LockException', $code);
	}
}

// FIFO related exception class
class FifoException extends WebconfigScriptException
{
	const CODE_OPEN = 1;
	const CODE_CREATE = 2;
	const CODE_WRITE = 3;
	const CODE_FORK = 4;
	const CODE_WAITPID = 5;
	const CODE_PCNTL_REQUIRED = 6;
	const CODE_FIFO_INVALID = 7;
	const CODE_UNEXPECTED = 8;
	const CODE_TIMEOUT = 9;

	public function __construct($code)
	{
		parent::__construct('FifoException', $code);
	}
}

// General-purpose script exception class
class ScriptException extends WebconfigScriptException
{
	const CODE_INVALID_PARAMETER_TYPE = 1;
	const CODE_INSTANCE = 2;
	const CODE_INVALID_TIMEZONE = 3;

	public function __construct($code)
	{
		parent::__construct('ScriptException', $code);
	}
}

// Webconfig script base class
class WebconfigScript
{
	const FILE_RUNLOCK = '/var/run/%s.pid';
	const FILE_STATE = '/var/state/webconfig/%s.state';

	// Default duration in seconds to wait for a FIFO reader
	const FIFO_TIMEOUT = 5;

	protected $name = null;
	protected $state = array();
	protected $state_fh = null;
	protected $state_filename = null;
	protected $state_owner = 'root';
	protected $state_group = 'root';
	protected $state_mode = 0660;
	protected $debug = false;
	protected $log_syslog = false;
	protected $log_stderr = false;
	protected $log_file = null;
	protected $run_lock = null;

	public function __construct($name)
	{
		$this->name = str_ireplace('.php', '', $name);
		$this->state_filename = sprintf(self::FILE_STATE, $this->name);

		if (($user = posix_getpwuid(posix_getuid())) !== false)
			$this->state_owner = $user['name'];

		if (($group = posix_getgrgid(posix_getgid())) !== false)
			$this->state_group = $group['name'];

		if (($ph = popen('/usr/bin/tty', 'r'))) {
			$tty = chop(fgets($ph));
			pclose($ph);
			if ($tty != 'not a tty') $this->debug = true;
		}

		try {
			$this->SetTimeZone();
		} catch (ScriptException $e) {
			if ($e->getCode == ScriptException::CODE_INVALID_TIMEZONE)
				$this->LogMessage("Unabled to set time zone.", LOG_ERR);
			else throw $e;
		}
	}

	public function __destruct()
	{
		if ($this->state_fh) fclose($this->state_fh);
		if (is_resource($this->log_file)) fclose($this->log_file);
		if ($this->log_syslog) closelog();
		if ($this->run_lock != null) unlink($this->run_lock);
	}

	// Get script name
	public function GetName()
	{
		return $this->name;
	}

	// Get script state
	public function GetState()
	{
		return $this->state;
	}

	// Set script state
	public function SetState($state)
	{
		$this->state = $state;
		$this->SerializeState();
	}

	// Set debug mode
	public function SetDebug($debug = true)
	{
		$this->debug = $debug;
	}

	// Is debug mode enabled?
	public function IsDebug()
	{
		return $this->debug;
	}

	// Set time zone
	public function SetTimeZone()
	{
		$tz_abrv = 'UTC';
		$ph = popen('/bin/date \'+%Z\'', 'r');
		if (is_resource($ph)) {
			$buffer = chop(fgets($ph, 4096));
			if (pclose($ph) == 0) $tz_abrv = $buffer;
		}
		if (!class_exists('DateTimeZone')) {
			if (!@date_default_timezone_set($tz_abrv)) {
				if (!date_default_timezone_set('UTC'))
					throw new ScriptException(ScriptException::CODE_INVALID_TIMEZONE);
			}
			return;
		}
		$tz_abrv_list = DateTimeZone::listAbbreviations();
		if (!array_key_exists(strtolower($tz_abrv), $tz_abrv_list))
			throw new ScriptException(ScriptException::CODE_INVALID_TIMEZONE);
		if (!date_default_timezone_set($tz_abrv_list[strtolower($tz_abrv)][0]['timezone_id']))
			throw new ScriptException(ScriptException::CODE_INVALID_TIMEZONE);
	}

	// Lock a file
	public function FileLock($filename, $mode = 'a+')
	{
		$fh = null;
		if (is_resource($filename)) $fh = $filename;
		else $fh = fopen($filename, $mode);
		if (!is_resource($fh))
			throw new FlockException(FlockException::CODE_OPEN);
		if (flock($fh, LOCK_EX) === false) {
			if (!is_resource($filename)) fclose($fh);
			throw new FlockException(FlockException::CODE_LOCK);
		}
		if (fseek($fh, SEEK_SET, 0) == -1) {
			flock($fh, LOCK_UN);
			if (!is_resource($filename)) fclose($fh);
			throw new FlockException(FlockException::CODE_SEEK);
		}
		if (!is_resource($filename)) return $fh;
		return $filename;
	}

	// Unlock a file
	public function FileUnlock($fh, $leave_open = false)
	{
		if (!is_resource($fh))
			throw new FlockException(FlockException::CODE_INVALID_RESOURCE);
		if (flock($fh, LOCK_UN) === false) {
			if (!$leave_open) fclose($fh);
			throw new FlockException(FlockException::CODE_UNLOCK);
		}
		if (!$leave_open) fclose($fh);
	}

	// Lock a file and read it's contents
	public function FileReadLocked($filename)
	{
		$fh = $this->FileLock($filename, 'r');
		if (($contents = stream_get_contents($fh)) === false) {
			$this->FileUnlock($fh, is_resource($filename));
			throw new FlockException(FlockException::CODE_READ);
		}
		$this->FileUnlock($fh, is_resource($filename));
		return $contents;
	}

	// Lock a file and replace it's contents
	public function FileWriteLocked($filename, $contents)
	{
		$fh = $this->FileLock($filename);
		if (ftruncate($fh, 0) === false) {
			$this->FileUnlock($fh, is_resource($filename));
			throw new FlockException(FlockException::CODE_TRUNCATE);
		}
		if (fseek($fh, SEEK_SET, 0) == -1) {
			$this->FileUnlock($fh, is_resource($filename));
			throw new FlockException(FlockException::CODE_SEEK);
		}
		if (fwrite($fh, $contents) === false) {
			$this->FileUnlock($fh, is_resource($filename));
			throw new FlockException(FlockException::CODE_WRITE);
		}
		fflush($fh);
		$this->FileUnlock($fh, is_resource($filename));
	}

	// Initialize script state.  It's a good idea to override this method.
	public function ResetState()
	{
		$this->state = array();
	}

	// Set state owner
	public function SetStateOwner($owner, $group)
	{
		if (is_resource($this->state_fh)) {
			$this->LogMessage('Unable to set state owner/group, already initialized.', LOG_WARNING);
			return;
		}
		$this->state_owner = $owner;
		$this->state_group = $group;
	}

	// Set state mode
	public function SetStateMode($mode)
	{
		if (!is_resource($this->state_fh)) {
			$this->LogMessage('Unable to set state mode, already initialized.', LOG_WARNING);
			return;
		}
		$this->state_mode = $mode;
	}

	// Open state file
	public function OpenState($filename = null, $readonly = false)
	{
		if ($filename != null) {
			// Override default state filename
			if (is_resource($this->state_fh)) {
				fclose($this->state_fh);
				$this->state_fh = null;
			}
			$this->state_filename = $filename;
		} else if (is_resource($this->state_fh)) return;

		clearstatcache();
		if (!$readonly) {
			$state_dir = dirname($this->state_filename);
			if (!file_exists($state_dir)) mkdir($state_dir, 0755, true);
		}
		if (($exists = file_exists($this->state_filename)))
			$fh = fopen($this->state_filename, ($readonly) ? 'r' : 'r+');
		else if ($readonly) return;
		else $fh = fopen($this->state_filename, 'w+');

		if (!is_resource($fh))
			throw new StateException(StateException::CODE_OPEN, $this->state_filename);
		if (!$exists && !chmod($this->state_filename, $this->state_mode))
			throw new StateException(StateException::CODE_CHMOD, $this->state_filename);
		if (!$exists && !chown($this->state_filename, $this->state_owner))
			throw new StateException(StateException::CODE_CHOWN, $this->state_filename);
		if (!$exists && !chgrp($this->state_filename, $this->state_group))
			throw new StateException(StateException::CODE_CHGRP, $this->state_filename);

		$this->state_fh = $fh;
		$this->UnserializeState();
	}

	// Lock state file, write serialized data
	public final function SerializeState()
	{
		if (!is_resource($this->state_fh)) $this->OpenState();
		$this->FileWriteLocked($this->state_fh, serialize($this->state));
	}

	// Lock state file, read and unserialized data
	public final function UnserializeState()
	{
		if (!is_resource($this->state_fh)) {
			throw new StateException(StateException::CODE_INVALID_RESOURCE,
				$this->state_filename);
		}
		clearstatcache();
		$stats = fstat($this->state_fh);
	
		if ($stats['size'] == 0) {
			$this->ResetState();
			return;
		}

		if (($this->state = unserialize($this->FileReadLocked($this->state_fh))) === false)
			throw new StateException(StateException::CODE_READ, $this->state_filename);
	}

	// Ensure only one instance is running
	public final function LockInstance()
	{
		if ($this->run_lock != null) return;
		$run_lock = sprintf(self::FILE_RUNLOCK, $this->name);

		clearstatcache();

		if (file_exists($run_lock)) {
			if (!($fh = fopen($run_lock, 'r')))
				throw new LockException(LockException::CODE_OPEN);
			if (fscanf($fh, '%d', $pid) != 1) {
				// XXX: Malformed PID file (probably)...
				unlink($run_lock);
			} else {
				// Perhaps this is a stale lock file?
				if (!file_exists("/proc/$pid")) {
					// Yes, the process 'appears' to no longer be running...
					unlink($run_lock);
				} else {
					// Only one instance can run at a time
					throw new ScriptException(ScriptException::CODE_INSTANCE);
				}
			}
			fclose($fh);
		}

		// Save our PID to the lock file
		if (!($fh = fopen($run_lock, 'w')))
			throw new LockException(LockException::CODE_CREATE);
		fprintf($fh, "%d\n", posix_getpid());
		fclose($fh);

		$this->run_lock = $run_lock;
	}

	// Is there an instance running?
	public final function IsRunning()
	{
		clearstatcache();

		$run_lock = sprintf(self::FILE_RUNLOCK, $this->name);
		if (!file_exists($run_lock)) return false;

		$fh = fopen($run_lock, 'r');
		list($pid) = fscanf($fh, '%d');
		fclose($fh);

		// Perhaps this is a stale lock file?
		if (!file_exists("/proc/$pid")) {
			// Yes, the process 'appears' to no longer be running...
			return false;
		}

		return true;
	}

	// Configure logging options
	//
	// Set syslog openlog options and facility:
	// $options['syslog']['options'] = LOG_PID
	// $options['syslog']['facility'] = LOG_LOCAL0
	//
	// Enable logging to standard-error (stderr):
	// $options['stderr'] = true;
	//
	// Enable logging to a log file:
	// $options['log_file'] = 'filename.log';
	// (can also specify a stream resource)

	public final function LogOptions($options)
	{
		if (!is_array($options))
			throw new ScriptException(ScriptException::CODE_INVALID_PARAMETER_TYPE);

		if (isset($options['syslog']) && is_array($options['syslog'])) {
			$syslog_options = LOG_PID;
			$syslog_facility = LOG_LOCAL0;
			if (isset($syslog['options'])) $syslog_options = $options['syslog']['options'];
			if (isset($syslog['facility'])) $syslog_facility = $options['syslog']['facility'];
			if (!openlog($this->name, $syslog_options, $syslog_facility))
				throw new LogException(LogException::CODE_SYSLOG_OPEN);
			$this->log_syslog = true;
		}
		if (isset($options['stderr']) && is_bool($options['stderr']))
			$this->log_stderr = $options['stderr'];
		if (isset($options['log_file']) && is_string($options['log_file'])) {
			$log_file = fopen($options['log_file'], 'a+');
			if (!is_resource($log_file))
				throw new LogException(LogException::CODE_OPEN, $options['log_file']);
			$this->log_file = $log_file;
		}
		else if (isset($options['log_file']) && is_resource($options['log_file'])) {
			if (strtolower(get_resource_type($options['log_file'])) != 'stream')
				throw new LogException(LogException::CODE_INVALID_RESOURCE);
			$this->log_file = $options['log_file'];
		}
		if ($this->debug) $this->log_stderr = true;
	}

	// Log a message
	public final function LogMessage($message, $level = LOG_NOTICE, $subject = null)
	{
		if (!$this->debug && $level == LOG_DEBUG) return;
		if ($subject != null) $message = "$subject: $message";
		if ($this->log_syslog) syslog($level, $message);
		if ($this->log_stderr || is_resource($this->log_file)) {
			$prefix = strftime('[%d/%b/%Y:%T %z]');
			switch ($level) {
			case LOG_ERR:
			case LOG_CRIT:
				$prefix .= ' ERROR:';
				break;
			case LOG_WARNING:
				$prefix .= ' WARNING:';
				break;
			case LOG_DEBUG:
				$prefix .= ' DEBUG:';
				break;
			}
			if ($this->log_stderr) {
				fwrite(STDERR, "$prefix $message\n");
				fflush(STDERR);
			}
			if (is_resource($this->log_file)) fwrite($this->log_file, "$prefix $message\n");
		}
	}

	// Write to a FIFO, returns process of writer for FifoWait()
	public final function FifoWrite($path, $data)
	{
		if (!function_exists('pcntl_fork'))
			throw new FifoException(FifoException::CODE_PCNTL_REQUIRED);
		if (!function_exists('pcntl_waitpid'))
			throw new FifoException(FifoException::CODE_PCNTL_REQUIRED);
		if (!function_exists('pcntl_wifexited'))
			throw new FifoException(FifoException::CODE_PCNTL_REQUIRED);
		if (!function_exists('pcntl_wexitstatus'))
			throw new FifoException(FifoException::CODE_PCNTL_REQUIRED);
		if (($fifo_stat = @stat($path)) === false) {
			if (!file_exists($path) && posix_mkfifo($path, 0600) === false)
				throw new FifoException(FifoException::CODE_CREATE);
		} else if (S_IFIFO != (S_IFMT & $fifo_stat['mode']))
			throw new FifoException(FifoException::CODE_FIFO_INVALID);

		$fh = null;
		$pid = pcntl_fork();

		switch ($pid) {
		case 0:
			$fh = fopen($path, 'w');
			if (!is_resource($fh)) exit(-1);

			$bytes = fwrite($fh, $data);
			fclose($fh);
			unlink($path);

			if ($bytes === false || $bytes != strlen($data)) exit(-2);
			exit(0);

		case -1:
			throw new FifoException(FifoException::CODE_FORK);
		}
		return $pid;
	}

	// Wait on an open FIFO... waits up to FIFO_TIMEOUT seconds for a reader
	public final function FifoWait($pid, $timeout = self::FIFO_TIMEOUT)
	{
		$count = 0;
		$result = 0;
		$status = null;

		for ($count = 0; $count < $timeout; $count++) {
			$result = pcntl_waitpid($pid, $status, WNOHANG);
			if ($result == -1)
				throw new FifoException(FifoException::CODE_WAITPID);
			else if ($result == 0) {
				sleep(1);
				continue;
			} else if ($result == $pid) break;

			throw new FifoException(FifoException::CODE_UNEXPECTED);
		}

		if ($count == $timeout) {
			posix_kill($pid, SIGTERM);
			pcntl_waitpid($pid, $status);
			throw new FifoException(FifoException::CODE_TIMEOUT);
		}

		if (!pcntl_wifexited($status))
			throw new FifoException(FifoException::CODE_UNEXPECTED);
		switch (pcntl_wexitstatus($status)) {
		case -1:
			throw new FifoException(FifoException::CODE_OPEN);
		case -2:
			throw new FifoException(FifoException::CODE_WRITE);
		}
	}

	// Return a file's size, works for files >2GB
	public final function FileSize64($filename)
	{
		clearstatcache();
		if (!file_exists($filename)) return 0;
		if (($stats = @stat($filename)) !== false) return $stats['size'];
		else return (float) exec('/usr/bin/stat -c %s ' . escapeshellarg($filename));
	}
}

// vi: ts=4
