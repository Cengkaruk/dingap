<?php

/**
 * Network interface controller.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
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

use \clearos\apps\network\Iface as IfaceAPI;
use \clearos\apps\network\Role as Role;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface controller.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Iface extends ClearOS_Controller
{
    /**
     * Network interface summary.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface_Manager');

        // Set validation rules
        //---------------------

        // Handle form submit
        //-------------------

        // Load view data
        //---------------

        try {
            $data['mode'] = $mode;
            $data['network_interface'] = $this->iface_manager->get_interface_details();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if (is_console())
            $options['type'] = MY_Page::TYPE_CONSOLE;

        $this->page->view_form('network/iface/summary', $data, lang('network_interfaces'), $options);
    }

    /**
     * Add interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function add($interface = NULL)
    {
        $this->_item('add', $interface);
    }

    /**
     * Delete interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function delete($interface = NULL)
    {
        $confirm_uri = '/app/network/iface/destroy/' . $interface;
        $cancel_uri = '/app/network/iface';
        $items = array($interface);

        if (is_console())
            $options['type'] = MY_Page::TYPE_CONSOLE;

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items, $options);
    }

    /**
     * Edit interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function edit($interface = NULL)
    {
        $this->_item('edit', $interface);
    }

    /**
     * Destroys interface.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function destroy($interface = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface', $interface);
        $this->load->library('network/Role');
        $this->load->library('network/Routes');

        // Handle delete
        //--------------

        try {
            $this->iface->delete_config();
            $this->role->remove_interface_role($interface);

            $current_route = $this->routes->get_gateway_device();

            if ($role === Role::ROLE_EXTERNAL) {
                $this->routes->set_gateway_device($interface);
            } else if ($interface == $current_route) {
                $this->routes->delete_gateway_device();
            }

            $this->page->set_status_deleted();
            redirect('/network');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * View interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function view($interface = NULL)
    {
        $this->_item('view', $interface);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Common add/edit/view form handler.
     *
     * @param string $form_type form type
     * @param string $interface interface
     *
     * @return view
     */

    function _item($form_type, $interface)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface', $interface);
        $this->load->library('network/Role');
        $this->load->library('network/Routes');
        $this->lang->load('network');

        // Set validation rules
        //---------------------

        $bootproto = $this->input->post('bootproto');
        $role = $this->input->post('role');

        $this->form_validation->set_policy('role', 'network/Role', 'validate_role', TRUE);
        $this->form_validation->set_policy('bootproto', 'network/Iface', 'validate_boot_protocol', TRUE);

        if ($bootproto == IfaceAPI::BOOTPROTO_STATIC) {
            $this->form_validation->set_policy('ipaddr', 'network/Iface', 'validate_ip', TRUE);
            $this->form_validation->set_policy('netmask', 'network/Iface', 'validate_netmask', TRUE);
            if ($role == Role::ROLE_EXTERNAL)
                $this->form_validation->set_policy('gateway', 'network/Iface', 'validate_gateway', TRUE);
        } else if ($bootproto == IfaceAPI::BOOTPROTO_DHCP)  {
            $this->form_validation->set_policy('hostname', 'network/Iface', 'validate_hostname');
            $this->form_validation->set_policy('dhcp_dns', 'network/Iface', 'validate_peerdns');
        } else if ($bootproto == IfaceAPI::BOOTPROTO_PPPOE)  {
            $this->form_validation->set_policy('username', 'network/Iface', 'validate_username', TRUE);
            $this->form_validation->set_policy('password', 'network/Iface', 'validate_password', TRUE);
            $this->form_validation->set_policy('mtu', 'network/Iface', 'validate_mtu');
            $this->form_validation->set_policy('pppoe_dns', 'network/Iface', 'validate_peerdns');
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            $aliases = array();

            try {
                // Set interface configuration
                //----------------------------

                if ($bootproto == IfaceAPI::BOOTPROTO_STATIC) {
                    $this->iface->save_static_config(
                        $this->input->post('ipaddr'),
                        $this->input->post('netmask'),
                        $this->input->post('gateway')
                    );

                    $this->iface->enable(FALSE);
                } else if ($bootproto == IfaceAPI::BOOTPROTO_DHCP) {
                    $this->iface->save_dhcp_config(
                        $this->input->post('hostname'),
                        (bool) $this->input->post('dhcp_dns')
                    );

                    $this->iface->enable(TRUE);
                } else if ($bootproto == IfaceAPI::BOOTPROTO_PPPOE) {
                    $interface = $this->iface->save_pppoe_config(
                        $interface,
                        $this->input->post('username'),
                        $this->input->post('password'),
                        $this->input->post('mtu'),
                        (bool) $this->input->post('pppoe_dns')
                    );
                }

                // Set routing
                //------------

                $current_route = $this->routes->get_gateway_device();

                if ($role === Role::ROLE_EXTERNAL) {
                    $this->routes->set_gateway_device($interface);
                } else if ($interface == $current_route) {
                    $this->routes->delete_gateway_device();
                }

                // Set interface role
                //-------------------

                $this->role->set_interface_role($interface, $role);

                // Return to summary page with status message
                //-------------------------------------------

                $this->page->set_status_updated();
                $this->page->redirect('/network');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['roles'] = $this->iface->get_supported_roles();
            $data['bootprotos'] = $this->iface->get_supported_bootprotos();
            $data['iface_info'] = $this->iface->get_info();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Set defaults
        if (empty($data['iface_info']['ifcfg']['bootproto']))
            $data['iface_info']['ifcfg']['bootproto'] = \clearos\apps\network\Iface::BOOTPROTO_STATIC;

        if (empty($data['iface_info']['ifcfg']['netmask']))
            $data['iface_info']['ifcfg']['netmask'] = '255.255.255.0';

        $data['form_type'] = $form_type;
        $data['interface'] = $interface;

        // Load the views
        //---------------

        if (is_console())
            $options['type'] = MY_Page::TYPE_CONSOLE;

        $this->page->view_form('network/iface/item', $data, lang('network_interface'), $options);
    }
}
