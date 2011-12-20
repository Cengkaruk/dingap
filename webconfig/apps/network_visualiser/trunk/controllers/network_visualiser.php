<?php

/**
 * Network visualiser controller.
 *
 * @category   Apps
 * @package    Network_Visualiser
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

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

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network_visualiser\Network_Visualiser as Net_Vis;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network visualiser controller.
 *
 * @category   Apps
 * @package    Network_Visualiser
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

class Network_Visualiser extends ClearOS_Controller
{
    /**
     * Network Visualiser summary view.
     *
     * @return view
     */

    function index()
    {
	clearos_profile(__METHOD__, __LINE__);

        // Load libraries
        //---------------

	$this->load->library('network_visualiser/Network_Visualiser');
        $this->lang->load('network_visualiser');

	// Check for detailed report type to see if we load the report layout
    	if ($this->network_visualiser->get_report_type() == Net_Vis::REPORT_DETAILED) {
	    $this->detailed();
            return;
	}

        // Load views
        //-----------

        $views = array('network_visualiser/settings','network_visualiser/report');

        $this->page->view_forms($views, lang('network_visualiser_app_name'));
    }

    /**
     * Network Visualiser simple report view.
     *
     * @return view
     */

    function simple()
    {
	clearos_profile(__METHOD__, __LINE__);

        // Load libraries
        //---------------
	$this->load->library('network_visualiser/Network_Visualiser');
        $this->lang->load('network_visualiser');

	$this->network_visualiser->set_report_type(Net_Vis::REPORT_SIMPLE);
    	redirect('/network_visualiser');
    }
 
    /**
     * Network Visualiser detailed report view.
     *
     * @return view
     */

    function detailed()
    {
	clearos_profile(__METHOD__, __LINE__);

        // Load libraries
        //---------------
	$this->load->library('network_visualiser/Network_Visualiser');
        $this->lang->load('network_visualiser');

	$data['report_type'] = Net_Vis::REPORT_DETAILED;

        // Load view
        //----------
	$this->page->view_form('network_visualiser/report', $data, lang('remote_backup_app_name'), array('type' => 'report'));
    }
}
