<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008-2009 Point Clark Networks
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 3
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
//
// Nov 2008 : Original work submitted to Point Clark Networks (W.H.Welch)
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Mailzu class
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Postfix.class.php');
require_once('ConfigurationFile.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mailzu class
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008-2009, Point Clark Networks
 */

class Mailzu extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_DB_CONFIG = '/etc/system/database';
	const TYPE_SPAM ='spam';
	const TYPE_BANNED = 'banned';
	const TYPE_VIRUS = 'virus';
	const TYPE_HEADER = 'header';
	const TYPE_PENDING = 'pending';
	const TYPE_TOTAL = 'total';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Mailzu constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns access password.
	 *
	 * @return string access password
	 * @throws EngineException
	 */

	function GetPassword()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new ConfigurationFile(self::FILE_DB_CONFIG);
			$config = $file->Load();
		} catch (FileNotFoundException $e) {
			return "";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$retval = isset($config['amavisd.password']) ? $config['amavisd.password'] : "";

		return $retval;
	}

	/**
	 * Returns primary mail domain.
	 *
	 * @return string primary mail domain
	 * @throws EngineException
	 */

	function GetDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$postfix = new Postfix();
			$domain = $postfix->GetDomain();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $domain;
	}
}

// vim: syntax=php ts=4
?>
