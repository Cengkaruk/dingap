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
 * GeoIP class.
 *
 * @package Reports
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once(COMMON_CORE_DIR . '/gui/Gui.class.php');
require_once(COMMON_CORE_DIR . '/api/ConfigurationFile.class.php');
require_once('DB.php'); // Pear Database class
require_once('Net/GeoIP.php'); // Pear Net_Geoip class

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * GeoIP class.
 *
 * @package Reports
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class GeoIP extends Gui
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $db = null;
	protected $dbconfig = null;
	protected $region = null;

	const FILE_DATA = '/usr/share/GeoIP/GeoLiteCity.dat';
	const FILE_DBCONFIG = '/etc/system/database';
	const DB_HOST = "127.0.0.1";
	const DB_PORT = "3308";
	const DB_USER = "root";
	const DB_NAME = "reports";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		include_once(COMMON_CORE_DIR . "/gui/GeoIPregionvars.php");
		$this->region = array($ISO,$FIPS);
	}

	/**
	 * Converts hostname to IP.
	 *
	 * @param string $hostname
	 * @return string
	 */

	public function GetIp($hostname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$hostname = preg_replace('/:[\d]+$/', '', $hostname); //remove any trailing port numbers

		if (ip2long($hostname) === false) {
			// TODO this function needs to ensure that an IP is returned
			// even when the hostname no longer exists.
			$ip = "1.2.3.4";

			if (! is_object($this->db)) {
				$this->db = $this->_connect();

				if (! is_object($this->db)) {
					throw new EngineException("Error creating database object",COMMON_ERROR);
				}
			}

			$db =& $this->db;
			$res =& $db->query("SELECT INET_NTOA(`ip`) FROM `domains` WHERE `hostname` = '$hostname' LIMIT 1");
			// check the result

			if (PEAR::isError($res)) {
				throw new EngineException($res->getMessage().': '.__CLASS__.'('.__LINE__.')',COMMON_ERROR);
			}

			if ($res->numRows() == 0) {
				$ips  = gethostbynamel($hostname);
				$sth = $db->prepare("INSERT INTO `domains` ( `ip`, `hostname`  ) VALUES ( ?, ? )");

				if (PEAR::isError($sth)) {
					throw new EngineException($sth->getMessage().': '.__CLASS__.'('.__LINE__.')',COMMON_ERROR);
				}

				if (is_array($ips)) {
					foreach ($ips as $ip) {
						$data = array(sprintf("%u",ip2long($ip)),$hostname);
						$db->execute($sth, $data);

						if (PEAR::isError($db)) {
							throw new EngineException($db->getMessage().': '.__CLASS__.'('.__LINE__.')',COMMON_ERROR);
						}
					}
				}
			} else {
				$ip = end($res->fetchRow());
			}

		} else {
			$ip = $hostname;
		}

		return $ip;
	}

	/**
	 * Return the image tags for country flag to which hostname is associated with in GeoIP.
	 *
	 * @param string $hostname
	 * @return string
	 */
	public function GetFlag($hostname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ip = $this->GetIp($hostname);
		$geoip = Net_GeoIP::getInstance(self::FILE_DATA, Net_GeoIP::STANDARD);
		$location = $geoip->lookupLocation($ip);
		$png = (empty($location->countryCode)) ? "unknown" : $location->countryCode;
		$alt = ($png == "unknown") ? $alt = "?" : $location->countryName;
		$png = '/images/flags/'.strtolower($png).'.png';

		return WebReplacePngTags($png, $alt); // workaround IE png transparency bug.
	}

	/**
	 * Returns GeoIP city,region,country data associated with hostname
	 *
	 * @param string $hostname
	 * @return string
	 */
	public function GetLocation($hostname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ip = $this->GetIp($hostname);
		$geoip = Net_GeoIP::getInstance(self::FILE_DATA, Net_GeoIP::STANDARD);
		$location = $geoip->lookupLocation($ip);
		$loc  = '';

		if (is_object($location)) {
			$code = $location->countryCode;

			if (is_null($this->region)) {
				$region =  $location->region;
			} elseif($code == "US" || $code == "CA") {
				// commented out so we return state abbeviations, instead of the state names
				//$region = $this->region[0][$code][$location->region];
				$region =  $location->region;
			} else {
				$region = $this->region[1][$code][$location->region];
			}

			$loc = "{$location->city},{$region},{$location->countryCode3}";
			$loc = str_replace(',,',',',$loc);
			$loc = preg_replace('/^,/','',$loc);
		}

		return $loc;
	}

	/**
	 * Reads database parameters from file
	 */

	private function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$dbconfigfile = new ConfigurationFile(self::FILE_DBCONFIG, 'explode', '=', 2);

		$this->dbconfig = $dbconfigfile->Load();
	}

	/**
	 * Connect to mysql database
	 *
	 * @return object PEAR::DB object
	 */

	private function _connect()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->dbconfig))
			$this->_LoadConfig();

		$dsn = "mysql://" . self::DB_USER .
			":" . $this->dbconfig['password'] .
			"@" . self::DB_HOST .
			":" . self::DB_PORT .
			"/" . self::DB_NAME;

		$options = array(
		               'debug'       => 2,
		               'portability' => DB_PORTABILITY_ALL,
		           );

		$dbobj = new DB();
		$db =& $dbobj->connect($dsn, $options);

		if (PEAR::isError($db))
			throw new EngineException($db->getMessage().': '.__CLASS__.'('.__LINE__.')',COMMON_ERROR);

		return $db;
	}

	/**
	 * Class destructor
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_object($this->db))
			$this->db->disconnect();

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
