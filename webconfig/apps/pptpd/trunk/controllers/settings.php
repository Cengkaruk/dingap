<?php

/**
 * PPTPd settings controller.
 *
 * @category   Apps
 * @package    PPTPd
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pptpd/
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
 * PPTPd settings controller.
 *
 * @category   Apps
 * @package    PPTPd
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pptpd/
 */

class Settings extends ClearOS_Controller
{
    /**
     * PPTPd settings controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('pptpd/PPTPd');
        $this->lang->load('pptpd');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('remote_ip', 'pptpd/PPTPd', 'validate_ip_range');
        $this->form_validation->set_policy('local_ip', 'pptpd/PPTPd', 'validate_ip_range');
        $this->form_validation->set_policy('domain', 'pptpd/PPTPd', 'validate_domain');
        $this->form_validation->set_policy('wins', 'pptpd/PPTPd', 'validate_wins_server');
        $this->form_validation->set_policy('dns', 'pptpd/PPTPd', 'validate_dns_server');
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->pptpd->set_remote_ip($this->input->post('remote_ip'));
                $this->pptpd->set_local_ip($this->input->post('local_ip'));
                $this->pptpd->set_domain($this->input->post('domain'));
                $this->pptpd->set_wins_server($this->input->post('wins'));
                $this->pptpd->set_dns_server($this->input->post('dns'));
                $this->pptpd->reset(TRUE);

                $this->page->set_status_updated();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['local_ip'] = $this->pptpd->get_local_ip();
            $data['remote_ip'] = $this->pptpd->get_remote_ip();
            $data['domain'] = $this->pptpd->get_domain();
            $data['wins'] = $this->pptpd->get_wins_server();
            $data['dns'] = $this->pptpd->get_dns_server();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('pptpd/settings', $data, lang('pptpd_pptp_server'));
    }
}
