<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2008 Point Clark Networks
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
// TODO: Missing documentation

/**
 * DmCrypt base class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Cron.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('Daemon.class.php');
require_once('ShellExec.class.php');
require_once(COMMON_CORE_DIR . '/scripts/dmcrypt.inc.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * DmCrypt base class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2008, Point Clark Networks
 */

class DmCrypt extends Engine
{
	///////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////

	var $config = array();
	var $loaded = false;

	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Dmcrypt constructor.
	 *
	 * @return void
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	final private function LoadConfiguration()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(DMCRYPT_CONFIG, true);

		if (!$file->Exists())
			$this->SaveConfiguration();

		$this->config = LoadConfiguration();
		$this->loaded = true;

		if (!$this->config['loaded'])
			throw new EngineException(DMCRYPT_LANG_ERR_LOAD_CONF, COMMON_ERROR);
	}

	final private function SaveConfiguration()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(DMCRYPT_CONFIG, true);

		try {
			if ($file->Exists())
				$file->Delete();

			$file->Create('webconfig', 'webconfig', '0600');
			$file->AddLines('# DM-CRYPT Webconfig module configuration');
			$file->AddLines('# DO NOT EDIT or your changes will be lost!');

			foreach ($this->config['volume'] as $volume)
				$file->AddLines(sprintf("%s|%s|%s\n", $volume['name'], $volume['mount_point'], $volume['device']));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	final public function GetVolumes()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->loaded)
			$this->LoadConfiguration();

		return $this->config['volume'];
	}

	final public function VolumeExists($name, $mount_point, $device)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->loaded)
			$this->LoadConfiguration();

		$exists = false;

		foreach ($this->config['volume'] as $volume) {
			if ($volume['name'] != $name)
				continue;
			$exists = true;
			break;
		}

		return $exists;
	}

	final private function SavePassword($passwd)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$tmp = tempnam("/tmp", "dmcrypt");

		// TODO: change to file class
		$fh = fopen($tmp, 'w');

		if (!$fh) {
			unlink($tmp);
			return null;
		}

		fputs($fh, str_pad($passwd, DMCRYPT_KEY_SIZE / 8, '0x', STR_PAD_BOTH));
		fclose($fh);

		return $tmp;
	}

	final private function GetState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$state = array();
		ResetState($state);

		// TODO: change to file class
		$fh = fopen(DMCRYPT_STATE, 'r');

		if ($fh) {
			UnserializeState($fh, $state);
			fclose($fh);
		}

		return $state;
	}

	final public function CreateVolume($name, $mount_point, $device, $size, $passwd, $verify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$storage_manager = new StorageDevice();

		$skip_size = false;

		try {
			$block_devices = $storage_manager->GetDevices();
			if (array_key_exists($device, $block_devices))
				$skip_size = true;
		} catch (Exception $e) {
			throw new ValidationException($e->GetMessage());
		}

		# Validation

		if (! $this->IsValidName($name)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if (! $this->IsValidMountPoint($mount_point)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if ($passwd != $verify) {
			throw new ValidationException(DMCRYPT_LANG_ERRMSG_PASSWORD_MATCH);
		}

		if (! $skip_size && ! $this->IsValidSize($size)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if (! $this->IsValidPassword($passwd)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if ($this->VolumeExists($name, $device, $mount_point))
			throw new EngineException(DMCRYPT_LANG_ERR_VOLUME_EXISTS, COMMON_ERROR);

		$volume = array();
		$volume['name'] = $name;
		$volume['mount_point'] = $mount_point;
		$volume['device'] = $device;
		$this->config['volume'][] = $volume;
		$this->SaveConfiguration();

		$tmp = $this->SavePassword($passwd);

		if (!strlen($tmp))
			throw new EngineException(DMCRYPT_LANG_ERR_CREATE_VOLUME, COMMON_ERROR);

		$dmcrypt = new ShellExec();

		try {
			$options = array();
			$options['escape'] = true;
			$dmcrypt->Execute(COMMON_CORE_DIR . '/scripts/dmcrypt.php', "create $name $size $tmp", true, $options);
		} catch (Exception $e) {
			@unlink($tmp);
			throw new EngineException(DMCRYPT_LANG_ERR_CREATE_VOLUME, COMMON_ERROR);
		}

		$state = $this->GetState();

		if ($state['status'] != 'Success')
			throw new EngineException(DMCRYPT_LANG_ERR_DMCRYPT_ERROR . $state['status'], COMMON_ERROR);
	}

	final public function DeleteVolume($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->VolumeExists($name))
			throw new EngineException(DMCRYPT_LANG_ERR_INVALID_VOLUME, COMMON_ERROR);

		$dmcrypt = new ShellExec();

		try {
			$options = array();
			$options['escape'] = true;
			$dmcrypt->Execute(COMMON_CORE_DIR . '/scripts/dmcrypt.php', "delete $name", true, $options);
		} catch (Exception $e) {
			@unlink($tmp);
			throw new EngineException(DMCRYPT_LANG_ERR_CREATE_VOLUME, COMMON_ERROR);
		}

		foreach ($this->config['volume'] as $id => $volume) {
			if ($volume['name'] != $name)
				continue;
			unset($this->config['volume'][$id]);
			break;
		}

		$this->SaveConfiguration();

		$state = $this->GetState();

		if ($state['status'] != 'Success')
			throw new EngineException(DMCRYPT_LANG_ERR_DMCRYPT_ERROR . $state['status'], COMMON_ERROR);
	}

	final public function MountVolume($name, $passwd)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate
		// TODO: two parameters are missing
		if (!$this->VolumeExists($name))
			throw new EngineException(DMCRYPT_LANG_ERR_INVALID_VOLUME, COMMON_ERROR);

		$tmp = $this->SavePassword($passwd);

		if (!strlen($tmp))
			throw new EngineException(DMCRYPT_LANG_ERR_CREATE_VOLUME, COMMON_ERROR);

		$dmcrypt = new ShellExec();

		try {
			$options = array();
			$options['escape'] = true;
			$dmcrypt->Execute(COMMON_CORE_DIR . '/scripts/dmcrypt.php', "mount $name $tmp", true, $options);
		} catch (Exception $e) {
			@unlink($tmp);
			throw new EngineException(DMCRYPT_LANG_ERR_CREATE_VOLUME, COMMON_ERROR);
		}

		$state = $this->GetState();
		if ($state['status'] != 'Success')
			throw new EngineException(DMCRYPT_LANG_ERR_DMCRYPT_ERROR . $state['status'], COMMON_ERROR);
	}

	final public function UnmountVolume($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->VolumeExists($name))
			throw new EngineException(DMCRYPT_LANG_ERR_INVALID_VOLUME, COMMON_ERROR);

		$dmcrypt = new ShellExec();

		try {
			$options = array();
			$options['escape'] = true;
			$dmcrypt->Execute(COMMON_CORE_DIR . '/scripts/dmcrypt.php', "unmount $name", true, $options);
		} catch (Exception $e) {
			@unlink($tmp);
			throw new EngineException(DMCRYPT_LANG_ERR_CREATE_VOLUME, COMMON_ERROR);
		}

		$state = $this->GetState();
		if ($state['status'] != 'Success')
			throw new EngineException(DMCRYPT_LANG_ERR_DMCRYPT_ERROR . $state['status'], COMMON_ERROR);
	}

	final public function IsMounted($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return IsEncryptedVolumeMounted(array('name' => $name));
	}

	///////////////////////////////
	//	V A L I D A T I O N	//
	///////////////////////////////

	/**
	 * Validation routine for a name.
	 *
	 * @param  string  $name  volume name
	 * @returns  boolean
	 */

	function IsValidName($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\-\.\_]+)$/", $name))
			return true;

		$this->AddValidationError(DMCRYPT_LANG_ERRMSG_INVALID_NAME, __METHOD__, __LINE__);

		return false;
	}

	/**
	 * Validation routine for mount point.
	 *
	 * @param  string  $name  volume name
	 * @returns  boolean
	 */

	function IsValidMountPoint($mount)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!isset($mount) || $mount == null || $mount = '') {
			$this->AddValidationError(DMCRYPT_LANG_ERRMSG_INVALID_MOUNT_POINT, __METHOD__, __LINE__);
			return false;
		}

		if (preg_match("/([ :;~`'\"\\\#!$])/", $mount)) {
			$this->AddValidationError(DMCRYPT_LANG_ERRMSG_INVALID_MOUNT_POINT, __METHOD__, __LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validation routine for password.
	 *
	 * @param  string  $passwd  volume password
	 * @returns  boolean
	 */

	function IsValidPassword($passwd)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!isset($passwd) || $passwd == null || $passwd = '') {
			$this->AddValidationError(DMCRYPT_LANG_ERRMSG_INVALID_PASSWORD_NULL, __METHOD__, __LINE__);
			return false;
		}

		return true;
		# TODO - Length checking?
		# TODO - Character checking?
	}

	/**
	 * Validation routine for volume size.
	 *
	 * @param  string  $size  volume size
	 * @returns  boolean
	 */

	function IsValidSize($size)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!isset($size) || $size == null || $size == '' || $size <= 0) {
			$this->AddValidationError(DMCRYPT_LANG_ERRMSG_INVALID_SIZE, __METHOD__, __LINE__);
			return false;
		}
		# TODO - MAX size?
		# TODO - Check for available space?
		return true;
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
