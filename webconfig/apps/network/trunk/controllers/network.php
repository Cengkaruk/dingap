<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
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
 * Basic network configuration.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// C O N T R O L L E R
///////////////////////////////////////////////////////////////////////////////
 
/**
 * Basic network configuration.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 */

class Network extends ClearOS_Controller
{
	/**
	 * Basic network overview.
	 */

	//function index($mode = 'edit')
	function index()
	{
		// Load libraries
		//---------------

        $this->load->library('network/Network');
        $this->load->library('network/Hostname');

        // Load view data
        //---------------

        try {
            $data['mode'] = $mode;
            $data['network_mode'] = $this->network->get_mode();
            $data['network_modes'] = $this->network->get_modes();
            $data['hostname'] = $this->hostname->get();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

		// Load views
		//-----------

        $views = array('general');

        //$this->page->view_forms($views, 'w000');
        $this->page->view_form('general/view_edit', $data);
	}
}
