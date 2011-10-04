<?php

/**
 * Certificate manager controller.
 *
 * @category   Apps
 * @package    Certificate_Manager
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/certificate_manager/
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Certificate manager controller.
 *
 * @category   Apps
 * @package    Certificate_Manager
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/certificate_manager/
 */

class Certificate extends ClearOS_Controller
{
    /**
     * CA controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('certificate_manager');
        $this->load->library('certificate_manager/SSL');

        // Load view data
        //---------------

        try {
            $data['certificates'] = $this->ssl->get_certificates();
        } catch (Engine_Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('certificate/summary', $data, lang('certificate_manager_certificates'));
    }

    function edit()
    {
        // Load dependencies
        //------------------

        $this->load->library('certificate_manager/SSL');
        $this->lang->load('certificate_manager');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
/*
                $this->pptpd->set_remote_ip($this->input->post('remote_ip'));
                $this->pptpd->set_local_ip($this->input->post('local_ip'));
                $this->pptpd->set_domain($this->input->post('domain'));
                $this->pptpd->set_wins_server($this->input->post('wins'));
                $this->pptpd->set_dns_server($this->input->post('dns'));
                $this->pptpd->reset(TRUE);
*/

                $this->page->set_status_updated();
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['attributes'] = $this->ssl->get_certificate_authority_attributes();
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('item', $data, lang('certificate_manager_certificate_authority'));
    }
}
