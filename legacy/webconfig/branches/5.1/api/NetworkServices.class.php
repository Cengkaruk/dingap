<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2006 Point Clark Networks.
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
 * Network services class.
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
 * Network services class.
 *
 * A class that represents the /etc/services file.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class NetworkServices extends Engine
{
	///////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////

	const FILE_SERVICES = "/etc/services";

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Network services constructor.
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__construct();
    }

	/**
	 * Loads array representation of /etc/services.
	 *
	 * @return array services data
	 * @throws EngineException
	 */

	function GetList()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_SERVICES);
			$rawlines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$services = array();

		foreach($rawlines as $line) {
			if (preg_match("/^\s*#/", $line))
				continue;

			if (preg_match("/^\s+^/", $line))
				continue;

			$data = preg_split('[\s]', $line, -1, PREG_SPLIT_NO_EMPTY);

			if (!isset($data[1]))
				continue;

			$port = explode("/", $data[1]);
			$port[1] = strtolower($port[1]);

			if (empty($services[$port[0]][$port[1]]['name']))
				$services[$port[0]][$port[1]]['name'] = $data[0];
			else
				$services[$port[0]][$port[1]]['name'] .= "\n" . $data[0];

			$comment = array_search("#", $data);

			if ($comment) {
				unset($data[$comment]);
				$comments = trim(implode(" ", array_splice($data, $comment)));

				if ($comment > 2) {
					// splice out the aliases

					if (empty($services[$port[0]][$port[1]]['alias']))
						$services[$port[0]][$port[1]]['alias'] = trim(implode(" ", array_splice($data, 2)));
					else
						$services[$port[0]][$port[1]]['alias'] .= "\n".trim(implode(" ", array_splice($data, 2)));
				}

				if (! isset($services[$port[0]][$port[1]]['alias']))
					$services[$port[0]][$port[1]]['alias'] = '';

				if (empty($services[$port[0]][$port[1]]['comments']))
					$services[$port[0]][$port[1]]['comments'] = $comments;
				else
					$services[$port[0]][$port[1]]['comments'] .= "\n".$comments;
			} else {
				if (count($data) > 2) {
					// splice out the aliases

					if (empty($services[$port[0]][$port[1]]['alias']))
						$services[$port[0]][$port[1]]['alias'] = trim(implode(" ", array_splice($data, 2)));
					else
						$services[$port[0]][$port[1]]['alias'] .= "\n".trim(implode(" ", array_splice($data, 2)));
					$services[$port[0]][$port[1]]['name'] = '';
					$services[$port[0]][$port[1]]['comments'] = '';
				}
			}
		}

		ksort($services);

		return $services;
	}
}

// vim: syntax=php ts=4
?>
