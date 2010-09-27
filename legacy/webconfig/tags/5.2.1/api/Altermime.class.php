<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2007 Point Clark Networks.
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
 * Altermime e-mail class.
 *
 * @package Api
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2007, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Software.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Altermime e-mail class.
 *
 * @package Api
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2007, Point Clark Networks
 */

class Altermime extends Software
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_DISCLAIMER_PLAINTEXT = '/etc/altermime/disclaimer.txt';
	const FILE_DISCLAIMER_STATE = '/etc/altermime/disclaimer.state';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Altermime constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        parent::__construct("altermime");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns the text of the e-mail disclaimer.
	 *
	 * @return string disclaimer text
	 * @throws EngineException
	 */

	function GetDisclaimerPlaintext()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_DISCLAIMER_PLAINTEXT);

		try {
			if ($file->Exists())
            	return htmlentities($file->GetContents(), ENT_COMPAT, 'UTF-8');
			else
				return '';
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns the state of the e-mail disclaimer service.
	 *
	 * @return boolean state of the e-mail string disclaimer text
	 * @throws EngineException
	 */

	function GetDisclaimerState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File(self::FILE_DISCLAIMER_STATE);

		try {
			if ($file->Exists())
            	return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets the text of the e-mail disclaimer.
	 *
     * @param string $text e-mail disclaimer
	 * @return void
	 * @throws EngineException
	 */

	function SetDisclaimerPlaintext($text)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Try not allow HTML here.
		$plaintext = strip_tags(html_entity_decode($text));

		if ($plaintext != $text) {
			$this->AddValidationError(ALTERMIME_LANG_INVALID_TEXT_DISCLAIMER, __METHOD__, __LINE__);
			return;
		}

		$file = new File(self::FILE_DISCLAIMER_PLAINTEXT);

		try {
			if ($file->Exists())
				$file->Delete();

			$plaintext = trim($plaintext);

			if (! empty($plaintext)){
				// Remove carriage returns (DOS format)
				$plaintext = preg_replace("/\r/", "", $plaintext);
				$file->Create('root', 'root', '0644');
				$file->AddLines("$plaintext\n");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets the state of the e-mail disclaimer service.
	 *
	 * @param boolean $state state of the e-mail disclaimer service
	 * @return voide
	 * @throws EngineException, ValidationException
	 */

	function SetDisclaimerState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! is_bool($state)) {
			$this->AddValidationError(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . LOCALE_LANG_STATUS, __METHOD__, __LINE__);
			return;
		}
			
		try {
			$file = new File(self::FILE_DISCLAIMER_STATE);
			$exists = $file->Exists();

			if ($state && !$exists)
				$file->Create("root", "root", "0644");
			else if (!$state && $exists)
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
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
