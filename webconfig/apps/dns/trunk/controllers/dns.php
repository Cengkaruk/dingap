<?php

/**
 * DNS server controller.
 *
 * @category   Apps
 * @package    DNS
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
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
 * DNS server controller.
 *
 * @category   Apps
 * @package    DNS
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
 */

class Dns extends ClearOS_Controller
{
    /**
     * DNS server summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hosts');
        $this->lang->load('dns');

        // Load view data
        //---------------

        try {
            $data['hosts'] = $this->hosts->get_entries();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('dns/summary', $data);
    }

    /**
     * Add DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function add($ip = NULL)
    {
        $this->_addedit($ip, 'add');
    }

    /**
     * Delete DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function delete($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->lang->load('dns');

        // Load views
        //-----------

        $data['message'] = sprintf(lang('dns_confirm_delete'), $ip);
        $data['ok_anchor'] = '/app/dns/destroy/' . $ip;
        $data['cancel_anchor'] = '/app/dns';
    
        $this->page->view_form('theme/confirm', $data);
    }

    /**
     * Edit DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function edit($ip = NULL)
    {
        $this->_addedit($ip, 'edit');
    }

    /**
     * Destroys DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function destroy($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hosts');
        $this->load->library('dns/Dnsmasq');

        // Handle delete
        //--------------

        try {
            $this->hosts->delete_entry($ip);
            $this->dnsmasq->reset();

            $this->page->set_status_deleted();
            redirect('/dns');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * DNS entry rommon add/edit form handler.
     *
     * @param string $ip        IP address
     * @param string $form_type form type
     *
     * @return view
     */

    function _addedit($ip, $form_type)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hosts');
        $this->load->library('dns/Dnsmasq');
        $this->lang->load('dns');
        $this->lang->load('network');

        // Set validation rules
        //---------------------

        // FIXME: discuss best approach
        $key_validator = ($form_type === 'edit') ? 'validate_ip' : 'validate_unique_ip';

        // TODO: Review the messy alias1/2/3 handling
        $this->form_validation->set_policy('ip', 'network/Hosts', $key_validator, TRUE);
        $this->form_validation->set_policy('hostname', 'network/Hosts', 'validate_hostname', TRUE);
        $this->form_validation->set_policy('alias1', 'network/Hosts', 'validate_alias');
        $this->form_validation->set_policy('alias2', 'network/Hosts', 'validate_alias');
        $this->form_validation->set_policy('alias3', 'network/Hosts', 'validate_alias');

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            $ip = $this->input->post('ip');
            $hostname = $this->input->post('hostname');
            $aliases = array();

            if ($this->input->post('alias1'))
                $aliases[] = $this->input->post('alias1');

            if ($this->input->post('alias2'))
                $aliases[] = $this->input->post('alias2');

            if ($this->input->post('alias3'))
                $aliases[] = $this->input->post('alias3');

            try {
                if ($form_type === 'edit') 
                    $this->hosts->edit_entry($ip, $hostname, $aliases);
                else
                    $this->hosts->add_entry($ip, $hostname, $aliases);

                $this->dnsmasq->Reset();

                // Return to summary page with status message
                $this->page->set_success(lang('base_system_updated'));
                redirect('/dns');
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message(), $view);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            if ($form_type === 'edit') 
                $entry = $this->hosts->get_entry($ip);
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e->get_message(), $view);
            return;
        }

        $data['form_type'] = $form_type;

        $data['ip'] = $ip;
        $data['hostname'] = isset($entry['hostname']) ? $entry['hostname'] : '';
        $data['aliases'] = isset($entry['aliases']) ? $entry['aliases'] : '';

        // Load the views
        //---------------

        $this->page->view_form('dns/add_edit', $data);
    }
}
