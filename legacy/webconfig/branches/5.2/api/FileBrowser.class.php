<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008 Point Clark Networks.
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

/**
 * File browser.
 *
 * @package Reports
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once(COMMON_CORE_DIR . '/api/Engine.class.php');
require_once(COMMON_CORE_DIR . '/api/File.class.php');
require_once(COMMON_CORE_DIR . '/api/Folder.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////
/**
 * Exception for FileBrowser.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008, Point Clark Networks
 */

class FileBrowserException extends EngineException
{
	public function __construct($msg)
	{
		parent::__construct($msg, COMMON_INFO);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * File browser.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008, Point Clark Networks
 */

class FileBrowser extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	private $sid = 0;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * FileBrowser constructor.
	 */

	function __construct($sessions = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($sessions) $this->sid = session_id();

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
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

	// Set 'root_path' for all files/directories.  This function will also
	// initialize the configuration.
	public function Configure($config_path, $root_path, $post_url, $loading_image = null)
	{
		$config = $this->LoadConfiguration($config_path);

		//$real_root_path = $root_path;
		//$real_root_path = '/tmp';
		//if (isset($root_path) && strlen($root_path)) {
		//	try {
		// 		$rp = new Folder($root_path, true);
		//		$real_root_path = $rp->GetFoldername();
		//	} catch (Exception $e) { }
		//}
		
		//$config['root_path'] = $real_root_path;
		$config['root_path'] = $root_path;
		$config['parent'] = '/';
		$this->SaveConfiguration($config_path, $config);

		printf("<script>FileBrowser.config = Base64.decode(Url.decode('%s'));</script>",
			urlencode(base64_encode($config_path)));
		printf("<script>FileBrowser.postUrl = Url.decode('%s');</script>", urlencode($post_url));
		if ($loading_image == null) $loading_image = WebSetIcon('icon_loading.gif', false);
		printf("<script>FileBrowser.loadingImage = Url.decode('%s');</script>", urlencode($loading_image));
	}

	public function ProcessPost($post)
	{
		if (!is_array($post)) return;
		if (!array_key_exists('fb_config', $post)) return;
		if (!array_key_exists('fb_action', $post)) return;
		if (!array_key_exists('fb_hash', $post)) return;

		$config_path = base64_decode(urldecode($post['fb_config']));

		if ($post['fb_action'] == 'change') {
			$this->ChangeDir($config_path, $post['fb_hash']);
		} else if ($post['fb_action'] == 'select') {
			$hash = str_replace('cb_', '', $post['fb_hash']);
			$this->SelectDir($config_path, $hash, array_key_exists('fb_select', $post));
		} else GenerateError(sprintf('%s: - %s',
			FILEBROWSER_LANG_ERR_INVALID_ACTION, $post['fb_action']));
	}

	private function GenerateError($error)
	{
		header('Content-Type: application/xml');
		printf("<?xml version='1.0'?>\n");
		printf("<error>%s</error>\n", $error);
		exit(0);
	}

	private function SelectDir($config_path, $hash, $selected = false)
	{
		try {
			$config = $this->LoadConfiguration($config_path);
		} catch (Exception $e) {
			header('Content-Type: application/xml');
			printf("<?xml version='1.0'?>\n<error>LoadConfiguration: %s</error>\n",
				$e->getMessage());
			exit(0);
		}

		if (!array_key_exists($hash, $config)) {
			header('Content-Type: application/xml');
			printf("<?xml version='1.0'?>\n<error>Invalid Hash: %s</error>\n", $hash);
			exit(0);
		}

		$config[$hash]['selected'] = $selected;

		try {
			$this->SaveConfiguration($config_path, $config);
		} catch (Exception $e) {
			header('Content-Type: application/xml');
			printf("<?xml version='1.0'?>\n<error>SaveConfiguration: %s</error>\n",
				$e->getMessage());
			exit(0);
		}

		header('Content-Type: application/xml');
		printf("<?xml version='1.0'?>\n<status>%selected: %s [%s]</status>\n",
			$selected ? 'S' : 'Des', $hash, $config[$hash]['filename']);
		exit(0);
	}

	private function ChangeDir($config_path, $hash)
	{
		try {
			$config = $this->LoadConfiguration($config_path);
		} catch (Exception $e) {
			header('Content-Type: application/xml');
			printf("<?xml version='1.0'?>\n<error>LoadConfiguration: %s</error>\n",
				$e->getMessage());
			exit(0);
		}

		if (!array_key_exists('root_path', $config) || !strlen($config['root_path'])) {
			header('Content-Type: application/xml');
			printf("<?xml version='1.0'?>\n<error>Invalid root path: null</error>\n");
			exit(0);
		}

		$path = '/';
		if (!strcmp($hash, 'parent') && array_key_exists('parent', $config)) {
			$path = $config['parent'];
			$config['parent'] = dirname($path);
		} else if (array_key_exists($hash, $config)) {
			$path = preg_replace('/^\/\//', '/',
				sprintf('%s/%s', $config[$hash]['path'], $config[$hash]['filename']));
			$config['parent'] = $config[$hash]['path'];
		}

		try {
			$rp = new Folder(sprintf('%s/%s', $config['root_path'], $path), true);
			$full_path = $rp->GetFoldername();
			$folder = new Folder($full_path, true);
			$entries = $folder->GetListing(true);
		} catch (Exception $e) {
			header('Content-Type: application/xml');
			printf("<?xml version='1.0'?>\n<error>Folder::GetListing: %s</error>\n",
				$e->getMessage());
			exit(0);
		}

		header('Content-Type: application/xml');
		printf("<?xml version='1.0'?>\n<entries>\n\t<config>%s</config>\n",
			urlencode(base64_encode($config_path)));
		if (strcmp($config['root_path'], '/'))
			$path = str_replace($config['root_path'], '', $full_path);
		else
			$path = $full_path;
		if (!strlen($path)) $path = '/';
		printf("\t<path>%s</path>\n",
			urlencode(base64_encode($path)));

		foreach ($entries as $entry) {
			try {
				$rp = new Folder(sprintf('%s/%s', $full_path, $entry['name']), true);
				if (strcmp($config['root_path'], '/'))
					$filename = str_replace($config['root_path'], '', $rp->GetFoldername());
				else
					$filename = $rp->GetFoldername();
			} catch (Exception $e) { continue; }
			if ($filename === false) continue;
			$entry_hash = md5($filename);
			$config[$entry_hash]['path'] = $path;
			$config[$entry_hash]['filename'] = $entry['name'];
			if (!array_key_exists('selected', $config[$entry_hash]))
				$config[$entry_hash]['selected'] = false;

			printf("\t<entry>\n");
			printf("\t\t<hash>%s</hash>\n", $entry_hash);
			printf("\t\t<filename>%s</filename>\n", urlencode($entry['name']));
			printf("\t\t<size>%d</size>\n", $entry['size']);
			printf("\t\t<properties>%s</properties>\n", $entry['properties']);
			printf("\t\t<modified>%s</modified>\n", $entry['modified']);
			printf("\t\t<modified_text>%s</modified_text>\n", strftime('%c', $entry['modified']));
			printf("\t\t<selected>%d</selected>\n", $config[$entry_hash]['selected']);
			printf("\t</entry>\n");
		}
		printf("</entries>\n");

		try {
			$this->SaveConfiguration($config_path, $config);
		} catch (Exception $e) {
			printf("<error>SaveConfiguration: %s</error>\n",
				$e->getMessage());
		}
		exit(0);
	}

	// XXX: All configuration routines are using direct file stream functions
	// (fopen, fseek, etc) because we need to use flock in order to support
	// concurrent webconfig access without mangling the data.

	private function LockConfiguration($config_path)
	{
		$fh = fopen($config_path, 'a+');
		if (!is_resource($fh)) return null;
		chown($config_path, 'webconfig');
		chgrp($config_path, 'webconfig');
		chmod($config_path, 0600);
		if (flock($fh, LOCK_EX) === false) {
			fclose($fh);
			return null;	
		}
		if (fseek($fh, SEEK_SET, 0) == -1) {
			flock($fh, LOCK_UN);
			fclose($fh);
			return null;
		}
		return $fh;
	}

	private function UnlockConfiguration($fh)
	{
		if (!is_resource($fh)) return;
		flock($fh, LOCK_UN);
		fclose($fh);
	}

	private function LogWrite($message)
	{
		$fh = fopen('/tmp/fb.log', 'a+');
		if (is_resource($fh)) {
			fwrite($fh, $message . "\n");
			fclose($fh);
		}
	}

	public function LoadConfiguration($config_path)
	{
		$fh = $this->LockConfiguration($config_path);
		if (!is_resource($fh))
			throw new FileBrowserException(FILEBROWSER_LANG_ERR_CONFIG_OPEN . " - $config_path",
				COMMON_ERROR);
		$contents = stream_get_contents($fh);
//		$contents = file_get_contents($config_path);
		$this->UnlockConfiguration($fh);
		if (!strlen($contents)) {
//			$this->LogWrite('Config empty.');
			return array();
		}
		$config = unserialize($contents);
		if ($config === false)
			throw new FileBrowserException(FILEBROWSER_LANG_ERR_CONFIG_UNSERIALIZE,
				COMMON_ERROR);
		if (!array_key_exists($this->sid, $config)) {
//			$this->LogWrite('Session entry not found for: ' . $this->sid);
			return array();
		}
//		ob_start();
//		var_dump($config[$this->sid]);
//		$this->LogWrite("Returning config:\n" . ob_get_clean());
		return $config[$this->sid];
	}

	private function SaveConfiguration($config_path, $config_session)
	{
		$fh = $this->LockConfiguration($config_path);
		if (!is_resource($fh))
			throw new FileBrowserException(FILEBROWSER_LANG_ERR_CONFIG_OPEN . " - $config_path",
				COMMON_ERROR);
//		if (($contents = file_get_contents($config_path)) === false) {
		if (($contents = stream_get_contents($fh)) === false) {
			$this->UnlockConfiguration($fh);
			throw new FileBrowserException(FILEBROWSER_LANG_ERR_CONFIG_READ,
				COMMON_ERROR);
			$this->UnlockConfiguration($fh);
		}
//		ob_start();
//		var_dump($contents);
//		$this->LogWrite("SaveConfiguration: current config:\n" . ob_get_clean());
		if (strlen($contents)) {
			if (($config = unserialize($contents)) === false) {
				$this->UnlockConfiguration($fh);
				throw new FileBrowserException(FILEBROWSER_LANG_ERR_CONFIG_UNSERIALIZE,
					COMMON_ERROR);
				$this->UnlockConfiguration($fh);
			}
		}
		if (fseek($fh, SEEK_SET, 0) == -1) {
			$this->UnlockConfiguration($fh);
			throw new FileBrowserException(FILEBROWSER_LANG_ERR_CONFIG_SEEK,
				COMMON_ERROR);
		}
		if (ftruncate($fh, 0) === false) {
			$this->UnlockConfiguration($fh);
			throw new FileBrowserException(FILEBROWSER_LANG_ERR_CONFIG_TRUNCATE,
				COMMON_ERROR);
		}
//		ob_start();
//		var_dump($config_session);
//		$this->LogWrite("SaveConfiguration: session config:\n" . ob_get_clean());
		if (array_key_exists($this->sid, $config))
			$config_session = array_merge($config[$this->sid], $config_session);
		$config[$this->sid] = $config_session;
		fwrite($fh, serialize($config));
//		ob_start();
//		var_dump($config);
//		$this->LogWrite("SaveConfiguration: new config:\n" . ob_get_clean());
		$this->UnlockConfiguration($fh);
	}

	public function ResetConfiguration($config_path)
	{
		$this->SaveConfiguration($config_path, array());
	}
}

// vim: syntax=php ts=4
?>
