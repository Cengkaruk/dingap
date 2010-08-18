<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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
 * Provides interface for configuring AutoFS.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('ConfigurationFile.class.php');
require_once('Folder.class.php');
require_once('Daemon.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Automount Utility.
 *
 * Class wrapper to the AutoFS package.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class AutoFs extends Daemon 
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/auto.master";
	protected $config = Array();
	protected $is_loaded = false;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * AutoFs constructor.
	 *
	 * 
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('autofs');
	}

	/** Retrieve a list of all defined mount points.
	 *
	 * @return array
     * @throws EngineException
	 */

	function GetMountPoints()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config;
	}

	/**
     * Set global mount option.  If the option does not exist, it will be added automatically.
     *
     * @param string $mountpoint the mountpoint
     * @param string $key parameter that is being replaced
     * @param string $value the value
     * @return void
     * @throws EngineException, ValidationException
     */

    function SetGlobalOption($mountpoint, $key, $value)
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Make sure mountpoint exists
		if (! isset($this->config[$mountpoint]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_INVALID_MOUNTPOINT);

		# Set option
		$this->config[$mountpoint]["options"][$key] = $value;

		$this->_SaveConfig();
	}

	/**
     * Set the name of the mapfile.
     *
     * @param string $mountpoint the mountpoint
     * @param string $filename the filenamename of the mapfile
     * @return void
     * @throws EngineException, ValidationException
     */

    function SetMapFile($mountpoint, $filename)
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Make sure mountpoint exists
		if (! isset($this->config[$mountpoint]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_INVALID_MOUNTPOINT);

		# Set mapfile
		$this->config[$mountpoint]["mapfile"] = $filename;

		$this->_SaveConfig();
	}

	/**
     * Set a mount option.  If the option does not exist, it will be added automatically.
     *
     * @param string $mountpoint the mountpoint
     * @param string $mount the mount name
     * @param string $key parameter that is being replaced
     * @param string $value the value
     * @return void
     * @throws EngineException, ValidationException
     */

    function SetMountOption($mountpoint, $mount, $key, $value)
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Make sure mountpoint exists
		if (! isset($this->config[$mountpoint]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_INVALID_MOUNTPOINT);

		# Set option
		$this->config[$mountpoint]["mountpoint"][$mount]["options"][$key] = $value;

		$this->_SaveConfig();
	}

	/**
     * Set the mount device.  If the device does not exist, it will be added automatically.
     *
     * @param string $mountpoint the mountpoint
     * @param string $mount the mount name
     * @param string $device the device
     * @return void
     * @throws EngineException, ValidationException
     */

    function SetMountDevice($mountpoint, $mount, $device)
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Make sure mountpoint exists
		if (! isset($this->config[$mountpoint]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_INVALID_MOUNTPOINT);

		# Make sure mount exists
		if (! isset($this->config[$mountpoint]["mountpoint"][$mount]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_INVALID_MOUNTPOINT);

		# Set option
		$this->config[$mountpoint]["mountpoint"][$mount]["device"] = $device;

		$this->_SaveConfig();
	}

	/**
     * Add a new mount to a mountpoint.
     *
     * @param string $mountpoint the mountpoint
     * @param string $mount the mount name
     * @param string $device the device
     * @param string $options any options
     * @return void
     * @throws EngineException, ValidationException
     */

    function AddMountPoint($mountpoint, $mapfile, $options = "")
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Make sure mountpoint does not exists
		if (isset($this->config[$mountpoint]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_MOUNTPOINT_EXISTS);

		$folder = new Folder($mountpoint);
		if (! $folder->exists())
			$folder->Create("root", "root", "0770");
		# Format $options variable
		$opt = array();
		if ($options) {
			$parts = explode(",", $options);
			foreach ($parts as $part) {
				list($key, $value) = explode("=", $part);
				$opt[$key] = $value; 
			}
		}
		# Add 
		$this->config[$mountpoint] = Array("mapfile" => $mapfile, "options" => $opt);

		$this->_SaveConfig();
	}

	/**
     * Add a new mount to a mountpoint.
     *
     * @param string $mountpoint the mountpoint
     * @param string $mount the mount name
     * @param string $device the device
     * @param string $options any options
     * @return void
     * @throws EngineException, ValidationException
     */

    function AddMount($mountpoint, $mount, $device, $options = "fstype=auto")
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Make sure mountpoint exists
		if (! isset($this->config[$mountpoint]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_INVALID_MOUNTPOINT);

		# Make sure mount does not already exists
		if (isset($this->config[$mountpoint]["mountpoint"][$mount]))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_MOUNT_EXISTS);

		# Check for device
		if (! $this->IsValidDevice($device))
			throw new ValidationException(AUTOFS_LANG_ERRMSG_INVALID_DEVICE);

		# Format $options variable
		$opt = array();
		if ($options) {
			$parts = explode(",", $options);
			foreach ($parts as $part) {
				list($key, $value) = explode("=", $part);
				$opt[$key] = $value; 
			}
		}
		# Add 
		$this->config[$mountpoint]["mountpoint"][$mount] = Array("options" => $opt, "device" => $device);

		$this->_SaveConfig();
	}

	/*************************************************************************/
	/* V A L I D A T I O N   R O U T I N E S								 */
	/*************************************************************************/

	/**
	 * Validation routine for device name.
	 *
	 * @param  string $device  a device on the system
	 * @return  boolean  true if valid
	 */

	function IsValidDevice($device)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		if (! $device)
			return false;
		return true;
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
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}

	/**
	 * Loads configuration.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(self::FILE_CONFIG, "split", "/[\s]+/", 3);

		try {
			$master = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($master as $dir => $config) {
			# Get directories to watch
			$options = explode(" ", $config[1]);
			$this->config[$dir]["mapfile"] = $config[0];
			foreach ($options as $option) {
				list($key, $value) = explode("=", $option);
				if (!$key)
					continue;
				$this->config[$dir]["options"][str_replace("--", "", $key)] = $value;
			}
			# Now get details of each directory
			$configfile = new ConfigurationFile($config[0], "split", "/[\s]+/", 3);
			$mounts = $configfile->Load();
			foreach ($mounts as $mount => $details) {
				$options = explode(",", $details[0]);
				foreach ($options as $option) {
					list($key, $value) = explode("=", $option);
					$key = eregi_replace("^-", "", $key);
					$this->config[$dir]["mountpoint"][$mount]["options"][$key] = $value;
				}
				$this->config[$dir]["mountpoint"][$mount]["device"] = $details[1];
			}
		}
		$this->is_loaded = true;
	}

	/**
	 * Save configuration changes.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _SaveConfig()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$master = new File(self::FILE_CONFIG);

		try {
			if ($master->Exists()) {
				$lines = $master->GetContentsAsArray();
				foreach ($this->config as $mountpoint => $details) {
					$found = false;
					$index = 0;
					$options = $this->config[$mountpoint]["options"];
					$opt = "";
					foreach ($options as $key => $value) {
						$opt .= "--" . $key; 
						if ($value)
							$opt .= "=" . $value;
						$opt .= " ";
					}
					$opt = trim($opt);
					foreach ($lines as $line) {
						if (ereg("^$mountpoint", $line)) {
							$found = true;
							$lines[$index] = $mountpoint . "\t" . $this->config[$mountpoint]["mapfile"] . "\t" . $opt; 
						}
						$index++;
					}
					if (!$found) {
						$lines[] = $mountpoint . "\t" . $this->config[$mountpoint]["mapfile"] . "\t" . $opt;
					}

					# Get mapfile
					$mapfile = new File($this->config[$mountpoint]["mapfile"], true);
					if (! $mapfile->Exists())
						$mapfile->Create("root", "root", "0640");
					$mapfilelines = $mapfile->GetContentsAsArray();
					$mounts = $this->config[$mountpoint]["mountpoint"];
					foreach ($mounts as $mount => $details) {
						$mntindex = 0;
						$opt = "";
						# Reset flag
						$found = false;
						$options = $details["options"];
						foreach ($options as $key => $value)
							$opt .= $key . "=" . $value . ",";
						$opt = "-" . substr($opt, 0, strlen($opt) - 1);
						foreach ($mapfilelines as $line) {
							if (ereg("^$mount", $line)) {
								$found = true;
								$mapfilelines[$mntindex] = $mount . "\t" .	$opt . "\t" . $details["device"];
							}
							$mntindex++;
						}
						if (!$found) {
							$mapfilelines[] = $mount . "\t" . $opt . "\t" . $details["device"];
						}
					}
					# Write map file
					try {
						$temp = new File($mapfile->GetFilename() . ".cctmp");
						if ($temp->Exists())
							$temp->Delete();
						$temp->Create("root", "root", "0640");
						$temp->DumpContentsFromArray($mapfilelines);
						$temp->MoveTo($mapfile->GetFilename());
					} catch (Exception $e) {
						throw new EngineException($e->GetMessage(), COMMON_WARNING);
					}
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		# Write master file
		try {
			$temp = new File(self::FILE_CONFIG . ".cctmp");
			if ($temp->Exists())
				$temp->Delete();
			$temp->Create("root", "root", "0640");
			$temp->DumpContentsFromArray($lines);
			$temp->MoveTo(self::FILE_CONFIG);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Reset our internal data structures
		// Reset our internal data structures
		$this->is_loaded = false;
		$this->config = array();
	}
}

// vim: syntax=php ts=4
?>
