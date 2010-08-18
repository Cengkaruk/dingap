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
 * Organization class.
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
require_once('Country.class.php');
require_once('Engine.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('Hostname.class.php');
require_once('Network.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Organization class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Organization extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/system/organization";
	const LOG_TAG = 'organization';

	protected $is_loaded = false;
	protected $config = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Organization constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns city.
	 *
	 * @return string city
	 * @throws EngineException
	 */

	function GetCity()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['city'];
	}

	/**
	 * Returns country.
	 *
	 * @return string country
	 * @throws EngineException
	 */

	function GetCountry()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['country'];
	}

	/**
	 * Returns domain.
	 *
	 * @return string domain name
	 * @throws EngineException
	 */

	function GetDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['domain'];
	}

	/**
	 * Returns default hostname for accessing this system.
	 *
	 * @return string default hostname
	 * @throws EngineException
	 */

	function GetInternetHostname()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['internet_hostname'];
	}

	/**
	 * Returns name of organization.
	 *
	 * @return string name of organization
	 * @throws EngineException
	 */

	function GetName()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['organization'];
	}

	/**
	 * Returns postal code.
	 *
	 * @return string postal code
	 * @throws EngineException
	 */

	function GetPostalCode()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['postalcode'];
	}

	/**
	 * Returns region (state or province).
	 *
	 * @return string region (state or province)
	 * @throws EngineException
	 */

	function GetRegion()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['region'];
	}

	/**
	 * Returns street address.
	 *
	 * @return string street address
	 * @throws EngineException
	 */

	function GetStreet()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['street'];
	}

	/**
	 * Returns name of organization unit.
	 *
	 * @return string name of organization unit
	 * @throws EngineException
	 */

	function GetUnit()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['organization_unit'];
	}

	/**
	 * Sets city.
	 *
	 * @param string $city city
	 * @return void
	 * @throws EngineException
	 */

	function SetCity($city)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($city)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . ORGANIZATION_LANG_CITY, __METHOD__, __LINE__
			);
			return;
		} else if (! $this->IsValidCity($city)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_CITY, __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('city', $city);
	}

	/**
	 * Sets country.
	 *
	 * @param string $country country
	 * @return void
	 * @throws EngineException
	 */

	function SetCountry($country)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($country)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . ORGANIZATION_LANG_COUNTRY, __METHOD__, __LINE__
			);
			return;
		} else if (! $this->IsValidCountry($country)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_COUNTRY, __METHOD__, __LINE__
			);
			return;
		} 

		// TODO: In earlier versions (circa 4.0), the country was a text field.
		// Try save two-letter country code for consistency.  If not, just log it.

		$countryobj = new Country();
		$list = $countryobj->GetList();

		if (! isset($list[$country])) {
			$valid = false;

			foreach ($list as $code => $name) {
				if ($name == $country) {
					$country = $code;
					$valid = true;
					break;
				}
			}

			if (! $valid) {
				if (preg_match("/united states/i", $country) || preg_match("/^USA$/i", $country))
					$country = "US";
				else
					Logger::Syslog(self::LOG_TAG, "Not fatal, but unable to find country code for the following: $country");
			}
		}

		$this->_SetParameter('country', $country);
	}

	/**
	 * Sets domain.
	 *
	 * @param string $domain domain
	 * @return void
	 * @throws EngineException
	 */

	function SetDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$network = new Network();

		if (! $network->IsValidDomain($domain)) {
			$this->AddValidationError($this->AddValidationError(implode($network->GetValidationErrors(true))),
				 __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('domain', $domain);
	}

	/**
	 * Sets internet hostname for accessing this system.
	 *
	 * @param string $hostname
	 * @return void
	 * @throws EngineException
	 */

	function SetInternetHostname($hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$network = new Network();

		if (! $network->IsValidHostname($hostname)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_INTERNET_HOSTNAME, __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('internet_hostname', $hostname);
	}
	/**
	 * Sets organization name.
	 *
	 * @param string $name organization name
	 * @return void
	 * @throws EngineException
	 */

	function SetName($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($name)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . ORGANIZATION_LANG_ORGANIZATION, __METHOD__, __LINE__
			);
			return;
		} else if (! $this->IsValidName($name)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ORGANIZATION, __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('organization', $name);
	}

	/**
	 * Sets postal code.
	 *
	 * @param string $postalcode postal code
	 * @return void
	 * @throws EngineException
	 */

	function SetPostalCode($postalcode)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidPostalCode($postalcode)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_POSTAL_CODE, __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('postalcode', $postalcode);
	}

	/**
	 * Sets region.
	 *
	 * @param string $region region (state or province)
	 * @return void
	 * @throws EngineException
	 */

	function SetRegion($region)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidRegion($region)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_REGION, __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('region', $region);
	}

	/**
	 * Sets street address.
	 *
	 * @param string $street street address
	 * @return void
	 * @throws EngineException
	 */

	function SetStreet($street)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (empty($street)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . ORGANIZATION_LANG_STREET, __METHOD__, __LINE__
			);
			return;
		} else if (! $this->IsValidStreet($street)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_STREET, __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('street', $street);
	}

	/**
	 * Sets organization unit.
	 *
	 * @param string $unit organization unit
	 * @return void
	 * @throws EngineException
	 */

	function SetUnit($unit)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidUnit($unit)) {
			$this->AddValidationError(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . ORGANIZATION_LANG_ORGANIZATION_UNIT, __METHOD__, __LINE__
			);
			return;
		}

		$this->_SetParameter('organization_unit', $unit);
	}

	/**
	 * Suggests a sane default domain based on DNS or hostname.
	 *
	 * @return string suggested default domain
	 * @throws EngineException
	 */

	function SuggestDefaultDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$hostname = "";
		
		// Try getting dynamic DNS name
		if (file_exists(COMMON_CORE_DIR . "/api/DynamicDns.class.php")) {
			require_once(COMMON_CORE_DIR  . "/api/DynamicDns.class.php");
			$dyndns = new DynamicDns();
			try {
				$hostinfo = $dyndns->GetInfo();
				$hostname = $hostinfo['domain'];
			} catch (Exception $e) {
				// Not fatal
			}
		}

		// Try getting the system hostname

		if (empty($hostname)) {
			$hostobject = new Hostname();
			try {
				$hostname = $hostobject->Get();
			} catch (Exception $e) {
				// Not fatal
			}
		}

		// With a hostname like aaa.bbb.ccc, assume the domain to be bbb.ccc

		$dotcount = substr_count($hostname, ".");

		if ($dotcount <= 1)
			$domain = $hostname;
		else
			$domain = preg_replace("/^[^\.]*\./", "", $hostname);

		if ($domain == "pointclark.net")
			$domain = $hostname;

		return $domain;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for city.
	 *
	 * @param string $city city
	 * @return boolean true if city is valid
	 */

	function IsValidCity($city)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $city))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for country.
	 *
	 * @param string $country country
	 * @return boolean true if country is valid
	 */

	function IsValidCountry($country)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $country))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for organization.
	 *
	 * @param string $organization organization
	 * @return boolean true if organization is valid
	 */

	function IsValidName($organization)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $organization))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for organization unit.
	 *
	 * @param string $unit organization unit
	 * @return boolean true if organization unit is valid
	 */

	function IsValidUnit($unit)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $unit))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for state or province.
	 *
	 * @param string $region region
	 * @return boolean true if region is valid
	 */

	function IsValidRegion($region)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $region))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for postal code.
	 *
	 * @param string $postalcode postal code
	 * @return boolean true if postal code is valid
	 */

	function IsValidPostalCode($postalcode)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $postalcode))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for street.
	 *
	 * @param string $street street
	 * @return boolean true if street is valid
	 */

	function IsValidStreet($street)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!preg_match("/([:;\/#!@])/", $street))
			return true;
		else
			return false;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S 
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration files.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(self::FILE_CONFIG);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	 * Sets a parameter in the config file.
	 *
	 * @access private
	 * @param string $key name of the key in the config file
	 * @param string $value value for the key
	 * @return void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->is_loaded = false;

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^$key\s*=\s*/", "$key = $value\n");
			if (!$match)
				$file->AddLines("$key = $value\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
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
