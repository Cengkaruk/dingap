<?php

/**
 * NAT firewall controller.
 *
 * @category   Apps
 * @package    NAT_Firewall
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/nat_firewall/
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

use \clearos\apps\network\Role as Role;
use \clearos\apps\nat_firewall\One_To_One_NAT as One_To_One_NAT;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * NAT firewall controller.
 *
 * @category   Apps
 * @package    NAT_Firewall
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/nat_firewall/
 */

class Nat_Firewall extends ClearOS_Controller
{
    /**
     * NAT firewall overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('nat_firewall');
        $this->load->library('nat_firewall/One_To_One_NAT');
        $this->load->library('network/Iface_Manager');

        // Sanity check - make sure there is a external interface configured
        //-------------

        $sanity_ok = FALSE;
        $network_interface = $this->iface_manager->get_interface_details();
        foreach ($network_interface as $interface => $detail) {
            if ($detail['role'] == Role::ROLE_EXTERNAL)
                $sanity_ok = TRUE;
        }

        if (!$sanity_ok)
            $this->page->set_message(lang('nat_firewall_no_external_nic'), 'warning');

        // Load view data
        //---------------

        try {
            $data['nat_rules'] = $this->one_to_one_nat->get_nat_rules();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('nat_firewall/summary', $data, lang('nat_firewall_app_name'));
    }

    /**
     * Add rule.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        $this->load->library('nat_firewall/One_To_One_NAT');
        $this->load->library('network/Iface_Manager');
        $this->lang->load('nat_firewall');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('nickname', 'nat_firewall/One_To_One_NAT', 'validate_name', TRUE);
        $this->form_validation->set_policy('interface', 'nat_firewall/One_To_One_NAT', 'validate_interface', TRUE);
        $this->form_validation->set_policy('private_ip', 'nat_firewall/One_To_One_NAT', 'validate_ip', TRUE);
        $this->form_validation->set_policy('public_ip', 'nat_firewall/One_To_One_NAT', 'validate_ip', TRUE);
            
        if ($this->input->post('all') != 'on') {
            $this->form_validation->set_policy('protocol', 'nat_firewall/One_To_One_NAT', 'validate_protocol', TRUE);
            $this->form_validation->set_policy('port', 'nat_firewall/One_To_One_NAT', 'validate_port', TRUE);
        }
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $my_protocol = $this->input->post('protocol');
                $my_port = $this->input->post('port');

                if ($this->input->post('all') == 'on') {
                    $my_protocol = One_To_One_NAT::PROTOCOL_ALL;
                    $my_port = One_To_One_NAT::CONSTANT_ALL_PORTS;
                }

                $this->one_to_one_nat->add(
                    $this->input->post('nickname'),
                    $this->input->post('public_ip'),
                    $this->input->post('private_ip'),
                    $this->input->post('protocol'),
                    $this->input->post('port'),
                    $this->input->post('interface')
                );

                $this->page->set_status_added();
                redirect('/nat_firewall');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['protocols'] = $this->one_to_one_nat->get_protocols();
            // Only want TCP and UDP
            foreach ($data['protocols'] as $key => $protocol) {
                if ($key != One_To_One_NAT::PROTOCOL_TCP && $key != One_To_One_NAT::PROTOCOL_UDP)
                    unset($data['protocols'][$key]);
            }

            $interfaces = $this->iface_manager->get_interface_details();
            // Only want external
            foreach ($interfaces as $key => $interface) {
                if ($interface['role'] == Role::ROLE_EXTERNAL)
                    $data['interfaces'][$key] = $key;
            }
            if (empty($data['interfaces']))
                $data['interfaces'][-1] = lang('base_select');
            
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load the views
        //---------------

        $this->page->view_form('nat_firewall/add', $data, lang('base_add'));
    }

    /**
     * Delete rule.
     *
     * @param string $name      nickname
     * @param string $wan_ip    IP address
     * @param string $lan_ip    IP address
     * @param string $protocol  protocol
     * @param string $port      port
     * @param string $interface port
     *
     * @return view
     */

    function delete($name, $wan_ip, $lan_ip, $protocol, $port, $interface)
    {
        $this->lang->load('nat_firewall');
        $confirm_uri = '/app/nat_firewall/destroy/' . $wan_ip . '/' . $lan_ip . '/' . $protocol . '/' . $port . '/' . $interface;
        $cancel_uri = '/app/nat_firewall';
        $items = array($name . ' (' . $wan_ip . ' > ' . $lan_ip . ') ' . lang('nat_firewall_on') . ' ' . $interface);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys rule.
     *
     * @param string $wan_ip    IP address
     * @param string $lan_ip    IP address
     * @param string $protocol  protocol
     * @param string $port      port
     * @param string $interface port
     *
     * @return view
     */

    function destroy($wan_ip, $lan_ip, $protocol, $port, $interface)
    {
        try {
            $this->load->library('nat_firewall/One_To_One_NAT');

            $this->one_to_one_nat->delete($wan_ip, $lan_ip, $protocol, ($port ? $port : 0), $interface);

            $this->page->set_status_deleted();
            redirect('/nat_firewall');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disables rule.
     *
     * @param string $name      nickname
     * @param string $wan_ip    IP address
     * @param string $lan_ip    IP address
     * @param string $protocol  protocol
     * @param string $port      port
     * @param string $interface port
     *
     * @return view
     */

    function disable($name, $wan_ip, $lan_ip, $protocol, $port, $interface)
    {
        try {
            $this->load->library('nat_firewall/One_To_One_NAT');

            $this->one_to_one_nat->toggle_enable(FALse, $wan_ip, $lan_ip, $protocol, $port, $interface);

            $this->page->set_status_disabled();
            redirect('/nat_firewall');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enables rule.
     *
     * @param string $name      nickname
     * @param string $wan_ip    IP address
     * @param string $lan_ip    IP address
     * @param string $protocol  protocol
     * @param string $port      port
     * @param string $interface port
     *
     * @return view
     */

    function enable($name, $wan_ip, $lan_ip, $protocol, $port, $interface)
    {
        try {
            $this->load->library('nat_firewall/One_To_One_NAT');

            $this->one_to_one_nat->toggle_enable(TRUE, $wan_ip, $lan_ip, $protocol, $port, $interface);

            $this->page->set_status_enabled();
            redirect('/nat_firewall');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
