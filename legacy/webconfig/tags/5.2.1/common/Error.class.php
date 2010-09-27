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
 * Error class.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

/**
 * Common error object.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Error
{
	protected $code;
	protected $message;
	protected $tag;
	protected $line;
	protected $context;
	protected $caught;
	protected $trace;
	protected $type;

	const TYPE_EXCEPTION = 'exception';
	const TYPE_ERROR = 'error';

	/**
	 * Error constructor.
	 *
	 * @param integer $code error code
	 * @param string $message error message
	 * @param string $tag a method name or some other nickname
	 * @param integer $line line number
	 * @param array $context error context
	 * @param integer $type type of error - exception or error
	 * @param boolean $caught true if error was caught by application
	 * @param array $trace error back trace
	 * @returns void
	 */
	function __construct($code, $message, $tag, $line, $context = null, $type, $caught = true, $trace = null)
	{
		$this->code = $code;
		$this->message = $message;
		$this->tag = $tag;
		$this->line = $line;
		$this->context = $context;
		$this->type = $type;
		$this->caught = $caught;
		$this->trace = $trace;
	}

	/**
	 * Returns error code.
	 *
	 * @returns integer error code
	 */
	function GetCode()
	{
		return $this->code;
	}

	/**
	 * Returns error message.
	 *
	 * @returns string error message
	 */
	function GetMessage()
	{
		return $this->message;
	}

	/**
	 * Returns error tag.
	 *
	 * @returns string error tag
	 */
	function GetTag()
	{
		return $this->tag;
	}

	/**
	 * Returns line number where error occurred.
	 *
	 * @returns integer line number
	 */
	function GetLine()
	{
		return $this->line;
	}

	/**
	 * Returns error context.
	 *
	 * @returns array error context
	 */
	function GetContext()
	{
		return $this->context;
	}

	/**
	 * Returns flag on state of the error.
	 *
	 * @returns boolean true if error was caught by application.
	 */
	function IsCaught()
	{
		return $this->caught;
	}

	/**
	 * Returns error type: error or exception.
	 *
	 * @returns string error type
	 */
	function GetType()
	{
		return $this->type;
	}

	/**
	 * Returns error trace.
	 *
	 * @returns array error trace.
	 */
	function GetTrace()
	{
		if ($this->trace)
			return $this->trace;
		else
			return array();
	}
}

// vim: syntax=php ts=4
?>
