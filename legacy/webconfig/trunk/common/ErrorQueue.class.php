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
 * ErrorQueue class.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

/**
 * Uncaught errors queue.
 *
 * All uncaught errors are queued in this singleton class.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class ErrorQueue
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @var ErrorQueue instance
	 */
	static private $instance = null;

	/**
	 * @var array error queue
	 */
	static private $errors = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * ErrorQueue constructor.
	 *
	 * @access private
	 */
	private function __construct()
	{}

	/**
	 * Do not allow attempts to clone our singleton.
	 *
	 * @access private
	 */
	private function __clone()
	{}

	/**
	 * Returns an ErrorQueue instance.
	 *
	 * @static
	 * @return ErrorQueue current instance
	 */
	static public function GetInstance()
	{
		if (self::$instance == NULL) {
			self::$instance = new ErrorQueue();
		}

		return self::$instance;
	}

	/**
	 * Add error to error queue.
	 *
	 * @param Error $error error object
	 * @return void
	 */
	public function Push(Error $error)
	{
		self::$errors[] = $error;
	}

	/**
	 * Return list of queued Error objects.
	 *
	 * @param boolean $purge if true, the queue will be purged
	 *
	 * @return array list of Errors
	 */
	public function GetAll($purge=true)
	{
	    $errors = self::$errors;
	    if ($purge){
	        self::$errors = array();
	    }
		return $errors;
	}

	/**
	 * Return list of queued error messages.
	 *
	 * @param boolean $purge if true, the queue will be purged
	 *
	 * @return array list of Errors
	 */
	public function GetAllMessages($purge=true)
	{
	    global $COMMON_ERROR_TYPE;
		$messages = array();
		foreach (self::$errors as $error) {
            if ($error->IsCaught()){
                $messages[] = $error->GetMessage();
            }else{
                if (ini_get('display_errors')){
                    $errno = $error->GetCode();
                    if( ( $errno & error_reporting()) == $errno ){
                        $tag = preg_replace("/.*\//", "", $error->GetTag());
                        $line = $error->GetLine();
                        $errmsg = $error->GetMessage();
                        $messages[] = sprintf("%s: %s (%d): %s", $COMMON_ERROR_TYPE[$errno], $tag, $line, $errmsg);
                    }
                }
            }
		}
	    if ($purge){
	        self::$errors = array();
	    }
		return $messages;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{}

}

// vim: syntax=php ts=4
?>
