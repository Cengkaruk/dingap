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
 * System locale manager.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * System locale manager.
 *
 * Provides tools for getting/setting locale configuration on the system.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Locale extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $code = null;

	const FILE_I18N = "/etc/sysconfig/i18n";
	const FILE_CONFIG = "/usr/share/system/settings/locale";
	const FILE_KEYBOARD = "/etc/sysconfig/keyboard";
	const DEFAULT_KEYBOARD = "us";
	const DEFAULT_LANGUAGE = "en_US";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Locale constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns the system keyboard setting.
	 *
	 * @return string keyboard setting eg us
	 * @throws EngineException
	 */

	function GetKeyboard()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_KEYBOARD);

			if ($file->Exists())
				$keyboard = $file->LookupValue("/^KEYTABLE=/");
			else
				return self::DEFAULT_KEYBOARD;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return preg_replace("/\"/", "", $keyboard);
	}

	/**
	 * Returns the list of available keyboards supported by the system.
	 *
	 * @return array list of keyboard layouts
	 * @throws EngineException
	 */

	function GetKeyboards()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$longlist = $file->LookupValue("/^language_list\s*=\s/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$keyboards = array();
		$langitems = explode("|", $longlist);
		foreach ($langitems as $lang) {
			$details = explode(",", $lang);
			$keyboards[$details[2]] = $details[2];
		}

		// Get rid of duplicates
		ksort($keyboards);

		return $keyboards;
	}

	/**
	 * Returns the system language code.
	 *
	 * @return string language code eg en_US, fr_FR, pt_BR
	 * @throws EngineException
	 */

	function GetLanguageCode()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->code)) {
			$file = new File(Locale::FILE_I18N);

			try {
				$code = $file->LookupValue("/^LANG=/");
				$code = preg_replace("/\..*/", "", $code);
				$code = preg_replace("/\"/", "", $code);
			} catch (FileNotFoundException $e) {
				$code = Locale::DEFAULT_LANGUAGE;
			} catch (FileNoMatchException $e) {
				$code = Locale::DEFAULT_LANGUAGE;
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			$this->code = $code;
		}

		return $this->code;
	}

	/**
	 * Returns the character set for the current locale.
	 *
	 * @return string character set 
	 * @throws EngineException
	 */

	function GetCharacterSet()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			switch (self::GetLanguageCode()) {
			case 'zh_CN':
				return 'GB2312';
			default:
				return 'UTF-8';
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns the text direction for the current locale.
	 *
	 * @return string direction, RTL or LTR
	 * @throws EngineException
	 */

	function GetTextDirection()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return 'LTR';
	}

	/**
	 * Returns the configured two-letter language code.
	 *
	 * Some applications do not support the full length language codes,
	 * so we created this simple method!
	 *
	 * @return string two-letter language code eg en, fr, it
	 * @throws EngineException
	 */

	function GetLanguageCodeSimple()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$code = $this->GetLanguageCode();

		return preg_replace("/_.*/", "", $code);
	}

	/**
	 * Returns the list of installed languages for this API.
	 *
	 * The method returns a hash array keyed on the language code.  Each
	 * entry in the array contains another hash array with the following fields:
	 *
	 *  - language code - eg "en_US"
	 *  - language short code - eg "en"
	 *  - language - eg English
	 *  - keyboard - eg de-latin1-nodeadkeys
	 *
	 * @return array hash array of language information
	 * @throws EngineException
	 */

	function GetLanguageInfo()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$longlist = $file->LookupValue("/^language_list\s*=\s/");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$languageinfo = array();
		$langlist = array();
		$langitems = explode("|", $longlist);
		foreach ($langitems as $lang) {
			$details = explode(",", $lang);
			$langlist["code"] = $details[0];
			$langlist["shortcode"] = preg_replace("/_.*/", "", $details[0]);
			$langlist["description"] = $details[1];
			$langlist["keyboard"] = $details[2];
			$languageinfo[$details[0]] = $langlist;
		}

		return $languageinfo;
	}

	/**
	 * Sets the keyboard.
	 *
	 * @param string $keyboard keyboard code
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetKeyboard($keyboard)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidKeyboard($keyboard))
			throw new ValidationException(LOCALE_LANG_KEYBOARD . " - " . LOCALE_LANG_INVALID);

		try {
			$file = new File(self::FILE_KEYBOARD);

			if ($file->Exists()) {
				$file->ReplaceLines("/^KEYTABLE=/", "KEYTABLE=\"$keyboard\"\n");
			} else {
				$file->Create("root", "root", "0644");
				$file->AddLines("KEYTABLE=\"$keyboard\"\n");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets the language.
	 *
	 * @param string $code language code
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetLanguageCode($code)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidLanguageCode($code))
			throw new ValidationException(LOCALE_LANG_ERRMSG_CODE_INVALID);

		try {
			$file = new File(self::FILE_I18N);

			if ($file->Exists()) {
				//TODO: fix hard-coded UTF-8?
				$file->ReplaceLines("/^LANG=/", "LANG=\"$code.UTF-8\"\n");
			} else {
				$file->Create("root", "root", "0644");
				$file->AddLines("LANG=\"$code.UTF-8\"\n");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets the language and keyboard based on defaults.
	 *
	 * @param string $code language code
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetLocale($code)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidLanguageCode($code))
			throw new ValidationException(LOCALE_LANG_ERRMSG_CODE_INVALID);

		$langinfo = $this->GetLanguageInfo();

		$this->SetLanguageCode($code);

		foreach ($langinfo as $langitem) {
			if ($langitem["code"] == $code) {
				$this->SetKeyboard($langitem["keyboard"]);
				return;
			}
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

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for keyboard.
	 *
	 * @param string $keyboard keyboard
	 * @return boolean true if keyboard is valid
	 * @throws EngineException
	 */

	function IsValidKeyboard($keyboard)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$langinfo = $this->GetLanguageInfo();

		foreach($langinfo as $langitem) {
			if ($langitem["keyboard"] == $keyboard)
				return true;
		}

		return false;
	}

	/**
	 * Validation routine for language code.
	 *
	 * @param string $code language code
	 * @return boolean true if language code is valid
	 * @throws EngineException
	 */

	function IsValidLanguageCode($code)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$langinfo = $this->GetLanguageInfo();

		foreach($langinfo as $langitem) {
			if ($langitem["code"] == $code)
				return true;
		}

		return false;
	}
}

// vim: syntax=php ts=4
?>
