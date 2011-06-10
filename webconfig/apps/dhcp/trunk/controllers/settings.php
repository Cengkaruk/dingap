<?php

/**
 * DHCP general settings controller.
 *
 * @category   Apps
 * @package    DHCP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
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
 * DHCP general settings controller.
 *
 * @category   Apps
 * @package    DHCP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
 */

class Settings extends ClearOS_Controller
{
    /**
     * DHCP general settings overview.
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * DHCP general settings edit.
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    /**
     * Common view/edit form.
     *
     * @return view
     */

    function _view_edit($form_type)
    {
        // Load dependencies
        //------------------

        $this->load->library('dhcp/Dnsmasq');
        $this->lang->load('dhcp');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('domain', 'dhcp/Dnsmasq', 'validate_domain', TRUE);
        $this->form_validation->set_policy('authoritative', 'dhcp/Dnsmasq', 'validate_authoritative_state', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok)) {
            try {
                // Update
                $this->dnsmasq->set_domain_name($this->input->post('domain'));
                $this->dnsmasq->set_authoritative_state((bool)$this->input->post('authoritative'));
                $this->dnsmasq->reset(TRUE);

                // Redirect to main page
                 $this->page->set_status_updated();
                redirect('/dhcp/');
            } catch (Exception $e) {
                $this->page->view_exception($e->GetMessage(), $view);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;

            $data['domain'] = $this->dnsmasq->get_domain_name();
            $data['authoritative'] = $this->dnsmasq->get_authoritative_state();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('dhcp/settings/view_edit', $data, lang('base_settings'));
    }
}
