<?php

//////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

/**
 * Graphical console controller.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU General Public License version 3 or later
 * @copyright Copyright 2010, ClearFoundation
 * @link http://www.clearfoundation.com	
 */

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Graphical console controller.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU General Public License version 3 or later
 * @copyright Copyright 2010, ClearFoundation
 * @link http://www.clearfoundation.com	
 */

class Graphical_console extends ClearOS_Controller 
{
	/**
	 * Default controller.
	 */

	function index()
	{
		redirect('/graphical_console/kill');
	}

	/**
	 * Kill console.
	 */

	function kill()
	{
		// Load libraries
		//---------------

		$this->load->library('graphical_console/GraphicalConsole');

		// Handle kill action
		//-------------------

		try {
			$this->graphicalconsole->killprocess();
		} catch (Exception $e) {
			$this->page->view_exception($e->GetMessage());
			return;
		}

		// Load views
		//-----------

		$this->page->set_title(lang('base_console'));
		$this->page->set_layout(MY_Page::TYPE_SPLASH);

		$this->load->view('theme/header');
		$this->load->view('theme/footer');
	}
}

// vim: syntax=php ts=4
?>
