<?php

/**
 * DHCP subnets controller.
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
 * DHCP subnets controller.
 *
 * @category   Apps
 * @package    DHCP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
 */

class Subnets extends ClearOS_Controller
{
    /**
     * DHCP server overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('dhcp/Dnsmasq');
        $this->lang->load('dhcp');

        // Load view data
        //---------------

        try {
            $data['subnets'] = $this->dnsmasq->get_subnets();
            $data['ethlist'] = $this->dnsmasq->get_dhcp_interfaces();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('dhcp/subnets/summary', $data);
    }

    /**
     * DHCP server add subnet.
     *
     * @param string $iface network interface
     *
     * @return view
     */

    function add($iface)
    {
        $this->_add_edit($iface, 'add');
    }

    /**
     * DHCP server delete subnet view.
     *
     * @param string $iface   interface
     * @param string $network network
     *
     * @return view
     */

    function delete($iface, $network)
    {
        $confirm_uri = '/app/dhcp/subnets/destroy/' . $iface;
        $cancel_uri = '/app/dhcp/subnets';
        $items = array($iface . ' - ' . $network);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys DHCP server subnet.
     *
     * @param string $iface network interface
     *
     * @return view
     */

    function destroy($iface)
    {
        try {
            $this->load->library('dhcp/Dnsmasq');
    
            $this->dnsmasq->delete_subnet($iface);

            $this->page->set_status_deleted();
            redirect('/dhcp/subnets');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * DHCP server edit subnet.
     *
     * @param string $iface network interface
     *
     * @return view
     */

    function edit($iface)
    {
        $this->_add_edit($iface, 'edit');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * DHCP server common add/edit form handler.
     *
     * @param string $iface     network interface
     * @param string $form_type form type
     *
     * @return view
     */

    function _add_edit($iface, $form_type)
    {
        // Load libraries
        //---------------

        $this->load->library('dhcp/Dnsmasq');
        $this->lang->load('dhcp');

        // Set validation rules
        //---------------------

        $this->load->library('form_validation');
        $this->form_validation->set_policy('gateway', 'dhcp/Dnsmasq', 'validate_gateway', TRUE);
        $this->form_validation->set_policy('lease_time', 'dhcp/Dnsmasq', 'validate_lease_time', TRUE);
        $this->form_validation->set_policy('start', 'dhcp/Dnsmasq', 'validate_start_ip', TRUE);
        $this->form_validation->set_policy('end', 'dhcp/Dnsmasq', 'validate_end_ip', TRUE);
        $this->form_validation->set_policy('dns1', 'dhcp/Dnsmasq', 'validate_dns_server');
        $this->form_validation->set_policy('dns2', 'dhcp/Dnsmasq', 'validate_dns_server');
        $this->form_validation->set_policy('dns3', 'dhcp/Dnsmasq', 'validate_dns_server');
        $this->form_validation->set_policy('wins', 'dhcp/Dnsmasq', 'validate_wins_server');
        $this->form_validation->set_policy('tftp', 'dhcp/Dnsmasq', 'validate_tftp_server');
        $this->form_validation->set_policy('ntp', 'dhcp/Dnsmasq', 'validate_ntp_server');
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $subnet['network'] = $this->input->post('network');
            $subnet['gateway'] = $this->input->post('gateway');
            $subnet['start'] = $this->input->post('start');
            $subnet['end'] = $this->input->post('end');
            $subnet['wins'] = $this->input->post('wins');
            $subnet['tftp'] = $this->input->post('tftp');
            $subnet['ntp'] = $this->input->post('ntp');
            $subnet['lease_time'] = $this->input->post('lease_time');
            $subnet['dns'] = array(
                $this->input->post('dns1'),
                $this->input->post('dns2'),
                $this->input->post('dns3'),
            );

            try {
                if ($form_type === 'add') {
                    $this->dnsmasq->add_subnet(
                        $iface,
                        $subnet['start'],
                        $subnet['end'],
                        $subnet['lease_time'],
                        $subnet['gateway'],
                        $subnet['dns'],
                        $subnet['wins'],
                        $subnet['tftp'],
                        $subnet['ntp']
                    );
                } else {
                    $this->dnsmasq->update_subnet(
                        $iface,
                        $subnet['start'],
                        $subnet['end'],
                        $subnet['lease_time'],
                        $subnet['gateway'],
                        $subnet['dns'],
                        $subnet['wins'],
                        $subnet['tftp'],
                        $subnet['ntp']
                    );
                }

                $this->dnsmasq->reset(TRUE);

                // Return to summary page with status message
                $this->page->set_status_added();
                redirect('/dhcp/subnets');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            if ($form_type === 'add') 
                $subnet = $this->dnsmasq->get_subnet_default($iface);
            else
                $subnet = $this->dnsmasq->get_subnet($iface);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $data['form_type'] = $form_type;

        $data['interface'] = $iface;
        $data['network'] = (isset($subnet['network'])) ? $subnet['network'] : '';
        $data['gateway'] = (isset($subnet['gateway'])) ? $subnet['gateway'] : '';
        $data['start'] = (isset($subnet['start'])) ? $subnet['start'] : '';
        $data['end'] = (isset($subnet['end'])) ? $subnet['end'] : '';
        $data['dns'] = (isset($subnet['dns'])) ? $subnet['dns'] : '';
        $data['wins'] = (isset($subnet['wins'])) ? $subnet['wins'] : '';
        $data['tftp'] = (isset($subnet['tftp'])) ? $subnet['tftp'] : '';
        $data['ntp'] = (isset($subnet['ntp'])) ? $subnet['ntp'] : '';
        $data['lease_time'] = (isset($subnet['lease_time'])) ? $subnet['lease_time'] : '';

        $data['lease_times'] = array();
        $data['lease_times'][12] = 12 . " " . lang('base_hours');
        $data['lease_times'][24] = 24 . " " . lang('base_hours');
        $data['lease_times'][48] = 2 . " " . lang('base_days');
        $data['lease_times'][72] = 3 . " " . lang('base_days');
        $data['lease_times'][96] = 4 . " " . lang('base_days');
        $data['lease_times'][120] = 5 . " " . lang('base_days');
        $data['lease_times'][144] = 6 . " " . lang('base_days');
        $data['lease_times'][168] = 7 . " " . lang('base_days');
        $data['lease_times'][336] = 2 . " " . lang('base_weeks');
        $data['lease_times'][504] = 3 . " " . lang('base_weeks');
        $data['lease_times'][672] = 4 . " " . lang('base_weeks');
        $data['lease_times'][\clearos\apps\dhcp\Dnsmasq::CONSTANT_UNLIMITED_LEASE] = lang('base_unlimited');
 
        // Load the views
        //---------------

        $this->page->view_form('dhcp/subnets/add_edit', $data, lang('dhcp_subnets'));
    }
}
