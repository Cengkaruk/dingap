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
 * Horde class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('DaemonManager.class.php');
require_once('File.class.php');
require_once('Firewall.class.php');
require_once('NetStat.class.php');
require_once('Network.class.php');
require_once('Software.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Horde class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2007, Point Clark Networks
 */

class Horde extends Software
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG_PORT = '/usr/webconfig/conf/httpd.d/horde.conf';
	const FILE_CONFIG_PORT_ALTERNATE = '/usr/webconfig/conf/httpd.d/horde-alternate.conf';
	const FILE_CONFIG = "/usr/share/horde/config/conf.php";
	const FILE_CONFIG_EXTRAS = "/usr/share/horde/config/extras.php";
	const FILE_CONFIG_MIME = "/usr/share/horde/imp/config/mime_drivers.php";
	const PATH_LOGO_HORDE_FULL = "/usr/share/horde/themes/graphics";
	const PATH_LOGO_HORDE_WEB = "/horde/themes/graphics";
	const LOGO_MAX_WIDTH = 140;
	const LOGO_MAX_HEIGHT = 40;

	protected $is_loaded = false;
	protected $config = array();
	protected $bad_ports = array('81', '82', '83');

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Horde constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct("horde");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Deletes alternate port.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function DeleteAlternativePort()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG_PORT_ALTERNATE);
			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(),COMMON_WARNING);
		}
	}

	/**
	 * Deletes logo image.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function DeleteLogoImage()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$image = $this->GetLogoImage();

		$image2delete = basename($image);

		if (! empty($image2delete)) {
			try {
				$file = new File(self::PATH_LOGO_HORDE_FULL . "/" . $image2delete,true);

				if ($file->Exists()) {
					$file->Delete();
				}

				unset($file);
			} catch (Exception $e) {
				// not a big deal, deleting the image is being "tidy" so just log it.
				$this->Log(COMMON_INFO,$e->getMessage(), __METHOD__, __LINE__);
			}
		}

		$this->SetLogoImage('');
	}

	/**
	 * Returns the alternative TCP port.
	 *
	 * @return integer alternative TCP port
	 * @throws EngineException
	 */

	function GetAlternativePort()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG_PORT_ALTERNATE);

			if (!$file->Exists())
				return 0;

			$port = $file->LookupValue('/Listen/');
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return (int) $port;
	}

	/**
	 * Returns the value of the HTML inline policy.
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	function GetHtmlInlinePolicy()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return (boolean) $this->_GetValue("mime_drivers['imp']['html']['inline']", self::FILE_CONFIG_MIME);
	}

	/**
	 * Returns the value of the images inline policy.
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	function GetImagesInlinePolicy()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return (boolean) $this->_GetValue("mime_drivers['imp']['images']['inline']", self::FILE_CONFIG_MIME);
	}

	/**
	 * Returns the state of login block policy.
	 *
	 * @return boolean state of login block policy
	 * @throws EngineException
	 */

	function GetLoginBlockPolicy()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['auth']['params']['login_block']))
			return $this->config['auth']['params']['login_block'];
		else
			return false;
	}

	/**
	 * Returns the number of allowed failures before blocking.
	 *
	 * @return int number of allowed failures before blocking
	 * @throws EngineException
	 */

	function GetLoginBlockCount()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['auth']['params']['login_block_count']))
			return $this->config['auth']['params']['login_block_count'];
		else
			return 3;
	}

	/**
	 * Returns the number of minutes to block for login failures.
	 *
	 * @return int number of minutes to block for login failures
	 * @throws EngineException
	 */

	function GetLoginBlockTime()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['auth']['params']['login_block_time']))
			return $this->config['auth']['params']['login_block_time'];
		else
			return 5;
	}

	/**
	 * Returns the logo image path. 
	 *
	 * @return string path to logo image
	 * @throws EngineException
	 */

	function GetLogoImage()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (empty($this->config['logo']['image']))
			return '';
		else
			return "/usr/share" . "/" . $this->config['logo']['image'];

		// FIXME
		// return self::PATH_LOGO_HORDE_FULL . "/" . $this->config['logo']['image'];
	}

	/**
	 * Returns the logo URL.
	 *
	 * @return string logo URL
	 * @throws EngineException
	 */

	function GetLogoUrl()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		if (empty($this->config['logo']['link']))
			return '';
		else
			return $this->config['logo']['link'];
	}

	/**
	 * Returns the alternative TCP port.
	 *
	 * @return integer alternative TCP port
	 * @throws EngineException
	 */

	function GetPort()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG_PORT);

			if (!$file->Exists())
				return 0;

			$port = $file->LookupValue('/Listen/');
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return (int) $port;
	}

	/**

	/**
	 * Imports an image file.
	 *
	 * - Valid image types are gif, jpg, or png
	 * - The image will be resized if it exceeds the recommended size
	 * - The image is also copied into the webconfig tree 
	 *
	 * @param array $fileinfo (cf $_FILES, fileinfo is expected to be a single key from that array )
	 * @return void
	 * @throws ValidationEception, EngineException
	 */

	function ImportLogoImage($fileinfo)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$tmp_name = $fileinfo['tmp_name'];

		switch ($fileinfo['type']) {

		case 'image/gif':

		case 'image/x-gif':
			$img_name = "customlogo.gif";
			$func = 'imagegif';
			$img = @imagecreatefromgif($tmp_name);
			break;

		case 'image/png':

		case 'image/x-png':
			$img_name = "customlogo.png";
			$func = 'imagepng';
			$img = @imagecreatefrompng($tmp_name);
			break;

		case 'image/jpeg':

		case 'image/pjpeg':
			$img_name = "customlogo.jpg";
			$func = 'imagejpeg';
			$img = @imagecreatefromjpeg($tmp_name);
			break;

		default:
			$errmsg = LOCALE_LANG_ERRMSG_INVALID_TYPE . " - " . $fileinfo['type'];
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			throw new ValidationException($errmsg);
		}

		if (! $img)
			throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD,COMMON_ERROR);
		
		// Get image size and scale ratio
		$width = imagesx($img);
		$height = imagesy($img);
		$scale = min(self::LOGO_MAX_WIDTH/$width, self::LOGO_MAX_HEIGHT/$height);

		// If the image is larger than the max shrink it
		// TODO: this seems to break some images
		/*
		if ($scale < 1) {
			$new_width = floor($scale*$width);
			$new_height = floor($scale*$height);

			// Create a new temporary image
			$tmp_img = imagecreatetruecolor($new_width, $new_height);
			$tmp_name = tempnam(COMMON_TEMP_DIR,'customlogo');

			// Copy and resize old image into new image
			imagecopyresized($tmp_img, $img, 0, 0, 0, 0,$new_width, $new_height, $width, $height);

			// Ouput the new smaller image
			call_user_func_array($func,array($tmp_img,$tmp_name));

			// Get rid of the old one
			imagedestroy($img);
		}
		*/

		try {
			// remove any existing logos with the same name from horde
			$file = new File(self::PATH_LOGO_HORDE_FULL . "/" . $img_name, true);

			if ($file->Exists())
				$file->Delete();

			unset($file);

			// save the new logo
			$file = new File($tmp_name,true);
			$file->Chown('root','root');
			$file->Chmod('644');
			$file->MoveTo(self::PATH_LOGO_HORDE_FULL . "/" . $img_name);
			unset($file);

			$this->SetLogoImage(self::PATH_LOGO_HORDE_WEB . "/" . $img_name);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(),COMMON_WARNING);
		}
	}

	/**
	 * Sets an alternative TCP port.
	 *
	 * @param integer $port port number
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetAlternativePort($port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		$network = new Network();

		if (! $network->IsValidPort($port)) {
			$this->AddValidationError(NETWORK_LANG_PORT . " - " . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
			return;
		}

		// Do not allow pre-defined ports
		//-------------------------------

		if (in_array($port, $this->bad_ports)) {
			$this->AddValidationError(HORDE_LANG_PORT_ALREADY_IN_USE . " - Webconfig", __METHOD__, __LINE__);
			return;
		}

		// Sanity check running port
		//--------------------------

		try {
			$process = $this->_GetProcessOnPort($port);
			if ($process) {
				$this->AddValidationError(HORDE_LANG_PORT_ALREADY_IN_USE . " - " . $process, __METHOD__, __LINE__);
				return;
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(),COMMON_WARNING);
		}

		$conf = "
Listen $port

<VirtualHost _default_:$port>
	SSLEngine on
	SSLCertificateFile /usr/webconfig/conf/server.crt
	SSLCertificateKeyFile /usr/webconfig/conf/server.key
	SSLCipherSuite ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:!EXP:+eNULL
	DocumentRoot \"/usr/share/horde\"
	SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown downgrade-1.0 force-response-1.0
	RewriteEngine on
	RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK)
	RewriteRule .* - [F]
	RewriteRule !^/horde /horde [PT]
	Alias /horde /usr/share/horde
</VirtualHost>
";

		try {
			$hordeconf = new File(self::FILE_CONFIG_PORT_ALTERNATE);

			if ($hordeconf->Exists())
				$hordeconf->Delete();

			$hordeconf->Create('root','root','0644');
			$hordeconf->AddLines($conf);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(),COMMON_WARNING);
		}
	}

	/**
	 * Sets the HTML inline policy.
	 *
	 * @param boolean $inline toggle display of inline html
	 * @return void
	 * @throws EngineException
	 */

	function SetHtmlInlinePolicy($inline)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_SetBooleanValue("mime_drivers['imp']['html']['inline']", $inline, self::FILE_CONFIG_MIME);
	}

	/**
	 * Sets the image inline policy.
	 *
	 * @param boolean $inline toggle display of inline images
	 * @return void
	 * @throws EngineException
	 */

	function SetImagesInlinePolicy($inline)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_SetBooleanValue("mime_drivers['imp']['images']['inline']", $inline, self::FILE_CONFIG_MIME);
	}

	/**
	 * Sets the login block policy.
	 *
	 * @param boolean $state state of block policy
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetLoginBlockPolicy($state)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! is_bool($state))
			throw new ValidationException(LOCALE_LANG_ERRMSG_INVALID_TYPE);

		$this->_SetBooleanValue("conf['auth']['params']['login_block']", $state, self::FILE_CONFIG);
	}

	/**
	 * Sets the login block count.
	 *
	 * @param int $count login block count
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetLoginBlockCount($count)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// FIXME: validate
		$this->_SetIntValue("conf['auth']['params']['login_block_count']", $count, self::FILE_CONFIG);
	}

	/**
	 * Sets the login block time
	 *
	 * @param int $time block time in minutes
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetLoginBlockTime($time)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// FIXME: validate
		$this->_SetIntValue("conf['auth']['params']['login_block_time']", $time, self::FILE_CONFIG);
	}

	/**
	 * Sets logo filename.
	 *
	 * @param string $filename filename
	 * @return void
	 * @throws ValidationEception, EngineException
	 */

	function SetLogoImage($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_SetValue("conf['logo']['image']", $filename, self::FILE_CONFIG);
	}

	/**
	 * Sets the logo URL.
	 *
	 * @param string $link a string begining with "http://" or "https://" or an empty string
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetLogoUrl($link)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (
			(empty($link)) ||
			(substr($link, 0, 7) == "http://") ||
			(substr($link, 0, 8) == "https://")) {
			$this->_SetValue("conf['logo']['link']", $link, self::FILE_CONFIG);
		} else {
			//user supplied value is invalid
			$link = htmlentities($link);// sanitize user input for display in errmsg
			$errmsg = LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": $link";
			$this->AddValidationError($errmsg, __METHOD__, __LINE__);
			throw new ValidationException($errmsg);
		}
	}

	/**
	 * Sets default mail domain.
	 *
	 * @param string $domain mail domain
	 * @return void
	 * @throws EngineException
	 */

	function SetMailDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG_EXTRAS);

		try {
			if ($file->Exists())
				$file->Delete();

			$file->Create("webconfig", "webconfig", "0600");

			$file->AddLines(
				"<?php\n" .
				"// This file is automatically updated -- please do not edit.\n" .
				"\$conf['kolab']['imap']['maildomain'] = '$domain';\n" .
				"?>\n"
			);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration files.
	 *
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		try {
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$conf = array();
		
		foreach ($lines as $line) {
			// Only pull out configuration file lines
			// Avoid embedded Horde method calls, e.g. Horde::gettempdir()
			// Avoid undefined (i.e. unquoted) constants, e.g. PEAR_LOG_INFO
			if (
				preg_match('/^\$conf/', $line) && 
				!preg_match('/Horde::/', $line) &&
				!preg_match('/_LOG/', $line))
				eval($line);
		}

		$this->config = $conf;
		$this->is_loaded = true;
	}

	/**
	 * Retrieves the value of the specified key from the specified configurtion file.
	 *
	 * @access private
	 * @param $key the key to retrieve
	 * @param $configfile the config file to retrieve the key from
	 * @return mixed
	 * @throws EngineException
	 */

	protected function _GetValue($key, $configfile)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = null;

		try {
			$file = new File($configfile);
			$file->GetPermissions(); // ensure the file exists and we can read it
			include($configfile);
			eval("\$value = $$key;");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $value;
	}

	/**
	 * Sets a boolean value in the specified configuration file.
	 *
	 * @access private
	 * @param string $key the parameter to set
	 * @param boolean $value the new value
	 * @param string $configfile the name of config file to modify
	 * @return void
	 * @throws EngineException
	 */

	protected function _SetBooleanValue($key, $value, $configfile)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$search = '/\$'.preg_quote($key).'/';

		if ($value)
			$replacement = '$'.$key." = true;\n";
		else
			$replacement = '$'.$key." = false;\n";

		$this->_SetValue($search, $replacement, $configfile);
	}

	/**
	 * Sets an integer value in the specified configuration file.
	 *
	 * @access private
	 * @param string $key the parameter to set
	 * @param integer $value the new value. Note: it will be validated
	 * @param string $configfile the name of config file to modify
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	protected function _SetIntValue($key, $value, $configfile)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! preg_match("/\d+/",$value)) {
			$errmsg = LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.": $value";
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . $value);
		}

		$search = '/\$'.preg_quote($key).'/';
		$replacement = '$'.$key." = ".intval($value).";\n";
		$this->_SetValue($search, $replacement, $configfile);
	}

	/**
	 * Sets a value in the specified configuration file.
	 *
	 * @access private
	 * @param string $key the parameter to set
	 * @param string $value the new value.
	 * @param string $configfile the name of config file to modify
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	protected function _SetValue($key, $value, $configfile)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (empty($key))
			throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . ' $key', COMMON_WARNING);

		$this->is_loaded = false;

		// The verification below is a bit of an "ease of use" hack to allow chaining methods

		// was _SetBooleanValue or _SetInvValue called first?
		// check for a delimited key
		if (substr($key,0,1).substr($key,-1) == "//") {
			$search = $key;
		} else {
			$search = '/\$'.preg_quote($key).'/';
		}

		//was _SetBooleanValue or _SetInvValue called first?
		//check for a value making an assignment
		if (strpos($value,"=") === false) {
			$replacement = '$'."$key = '$value';\n";
		} else {
			$replacement = $value;
		}

		$file = new File($configfile);

		try {
			$file->LookupValue($search);//does it exist?
			$file->ReplaceLines($search, $replacement, 1);//update
		} catch (FileNoMatchException $e) {
			$file->AddLines($replacement); //add
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(),COMMON_ERROR);
		}
	}

	/**
	 * Returns the process name bound to the given port.
	 *
	 * No process name will be returned if port is not in use.
	 *
	 * @param string process name using the specifed port
	 * @return boolean
	 */

	protected function _GetProcessOnPort($port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$netstat = new NetStat();
			$results = preg_grep("/^unix/", $netstat->Execute('pan', false), PREG_GREP_INVERT);
			$results = preg_grep("/:$port\s+/", $results);
			$results = preg_grep("/LISTEN/", $results);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(),COMMON_ERROR);
		}

		if (!empty($results)) {
			$process = preg_replace("/.*\//", "", end($results));
			$daemons = new DaemonManager();
			$daemondata = $daemons->GetMetaData();
			$processname = $process;

			foreach ($daemondata as $daemoninfo) {
				if ($daemoninfo['processname'] == $process) {
					$processname = $daemoninfo['description'];
					break;
				}
			}

			return $processname;
		} else {
			return '';
		}
	}

	/**
	* @access private
	*/

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
