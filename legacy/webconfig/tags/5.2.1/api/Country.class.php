<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
// Country data Copyright Michael Wallner - http://pear.php.net/package/I18Nv2/
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
 * Country class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('Locale.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Country class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Country extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $codes = array();
	protected $is_loaded = false;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Country constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns list of countries.
	 *
	 * @return array list of countries
	 * @throws EngineException
	 */

	function GetList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// Determine the output encoding

		try {
			$locale = new Locale();
			$code = $locale->GetLanguageCode();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// TODO: do lookup in Locale class
		if ($code == "zh_CN")
			$charset = "GB2312";
		else
			$charset = "UTF-8";

		// Sort the list alphabetically by country

		$names = array();

		foreach ($this->codes as $code => $name)
			$names[$name] = $code;

		ksort($names);

		$codes = array();

		foreach ($names as $name => $code)
			$codes[$code] = iconv("UTF-8", $charset, $name);

		return $codes;
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

		$mapping = array(
			'da_DK' => 'da',
			'de_DE' => 'de',
			'en_US' => 'en',
			'es_ES' => 'es',
			'fi_FI' => 'fi',
			'fr_FR' => 'fr',
			'hu_HU' => 'hu',
			'it_IT' => 'it',
			'nl_NL' => 'nl',
			'pl_PL' => 'pl',
			'pt_PT' => 'pt',
			'ro_RO' => 'ro',
			'ru_RU' => 'ru',
			'sl_SL' => 'sl',
			'sv_SE' => 'sv',
			'tr_TR' => 'tr',
			'zh_CN' => 'zh',
		);

		try {
			$locale = new Locale();
			$code = $locale->GetLanguageCode();
		} catch (Exception $e) {
			$code = 'en_US';
		}

		$mapvalue = isset($mapping[$code]) ? $mapping[$code] : $mapping['en_US'];

		if (file_exists(COMMON_CORE_DIR . "/api/Country/" . $mapvalue . ".php"))
			include(COMMON_CORE_DIR . "/api/Country/" . $mapvalue . ".php");
		else if (file_exists(COMMON_CORE_DIR . "/api/Country/en.php"))
			include(COMMON_CORE_DIR . "/api/Country/en.php");
		else
			throw new EngineException("Country list missing", COMMON_ERROR);

		$this->is_loaded = true;
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

// vim: syntax=php ts=4
?>
