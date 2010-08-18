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
 * MIME handler.
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
require_once('File.class.php');
require_once('FileTypes.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * MIME class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Mime extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $type = array("text", "multipart", "message", "application", "audio", "image", "video", "other");
	protected $encoding = array("7bit", "8bit", "binary", "base64", "quoted-printable", "other");

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Mime constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns the list of message parts.
	 *
	 * @param  obj  $structure  result of imap_fetchstructure()
	 * @return  array  list of message parts
	 * @throws  EngineException
	 */

	function GetParts($structure)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# TODO - Use recursive function
		$all = Array();
		$parts = Array();
		if (isset($structure->parts))
			$parts = $structure->parts;
		for($index = 0; $index < sizeof($parts); $index++) {
			$obj = $parts[$index];
			$pid = ($index + 1);
			# default to text
			if (!isset($obj->type) || $obj->type == "")
				$obj->type = 0;
			# default to 7bit
			if (!isset($obj->encoding) || $obj->encoding == "")
				$obj->encoding = 0;
			$all[$pid]["encoding"] = $this->encoding[$obj->encoding];
			isset($obj->bytes) ? $all[$pid]["size"] = strtolower($obj->bytes) : $all[$pid]["size"] = 0;
			isset($obj->disposition) ? $all[$pid]["disposition"] = strtolower($obj->disposition) : $all[$pid]["disposition"] = null;
			isset($obj->subtype) ? $all[$pid]["type"] = $this->type[$obj->type] . "/" . strtolower($obj->subtype) : $all[$pid]["type"] = null;
			if (isset($obj->ifid) && $obj->ifid) {
				$all[$pid]["Content-ID"] = $obj->id;
			}
			if (isset($obj->disposition) && eregi("^attachment$|^inline$", $obj->disposition)) {
				$params = $obj->dparameters;
				foreach ($params as $p) {
					if(strtoupper($p->attribute) == "FILENAME" || strtoupper($p->attribute) == "NAME") {
						$all[$pid]["name"] = $p->value;
					} else if(strtoupper($p->attribute) == "CHARSET") {
						$all[$pid]["charset"] = $p->value;
					}
				}
			}
			if (isset($obj->parts)) {
				$partsSub1 = $obj->parts;
				for($indexSub1 = 0; $indexSub1 < sizeof($partsSub1); $indexSub1++) {
					$objSub1 = $partsSub1[$indexSub1];
					$pid = ($index + 1) . "." . ($indexSub1 + 1);
					# default to text
					if (!isset($objSub1->type) || $objSub1->type == "")
						$objSub1->type = 0;
					# default to 7bit
					if (!isset($objSub1->encoding) || $objSub1->encoding == "")
						$objSub1->encoding = 0;
					$all[$pid]["encoding"] = $this->encoding[$objSub1->encoding];
					isset($objSub1->bytes) ? $all[$pid]["size"] = strtolower($objSub1->bytes) : $all[$pid]["size"] = 0;
					isset($objSub1->disposition) ? $all[$pid]["disposition"] = strtolower($objSub1->disposition) : $all[$pid]["disposition"] = null;
					$all[$pid]["type"] = $this->type[$objSub1->type] . "/" . strtolower($objSub1->subtype);
					if ($objSub1->ifid)
						$all[$pid]["Content-ID"] = $objSub1->id;
					if (isset($objSub1->disposition) && eregi("^attachment$|^inline$", $objSub1->disposition)) {
						$params = $objSub1->parameters;
						foreach ($params as $p) {
							if(strtoupper($p->attribute) == "FILENAME" || strtoupper($p->attribute) == "NAME") {
								$all[$pid]["name"] = $p->value;
							} else if(strtoupper($p->attribute) == "CHARSET") {
								$all[$pid]["charset"] = $p->value;
							}
						}
						$params = $objSub1->dparameters;
						foreach ($params as $p) {
							if(strtoupper($p->attribute) == "FILENAME" || strtoupper($p->attribute) == "NAME") {
								$all[$pid]["name"] = $p->value;
							} else if(strtoupper($p->attribute) == "CHARSET") {
								$all[$pid]["charset"] = $p->value;
							}
						}
					} else if (isset($objSub1->ifparameters)) {
						$params = $objSub1->parameters;
						foreach ($params as $p) {
							if(strtoupper($p->attribute) == "FILENAME" || strtoupper($p->attribute) == "NAME") {
								$all[$pid]["name"] = $p->value;
							} else if(strtoupper($p->attribute) == "CHARSET") {
								$all[$pid]["charset"] = $p->value;
							}

							if($p->attribute == "CHARSET") {
								$all[$pid]["charset"] = $p->value;
							}
						}
					}
				}
			}
		}
		return $all;
	}
}

// vim: syntax=php ts=4
?>
