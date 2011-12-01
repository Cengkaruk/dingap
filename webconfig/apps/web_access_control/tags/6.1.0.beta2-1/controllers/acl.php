<?php

/**
 * Web access control summary controller.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_access_control/
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

use \clearos\apps\web_proxy\Squid as Squid;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ACL Summary controller.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_access_control/
 */

class ACL extends ClearOS_Controller
{
    /**
     * Web Access Control default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');

        // Load dependencies
        //------------------

        $this->lang->load('web_access_control');
        $this->lang->load('web_proxy');

        $data['acls'] = $this->squid->get_acl_list();

        $this->page->view_form('acl/summary', $data, lang('web_access_control_web_access_control'));
    }

    /**
     * Add view.
     *
     * @return view
     */

    function add()
    {
        $this->_add_edit('add');
    }

    /**
     * Delete an ACL definition.
     *
     * @param string $name    name of ACL rule
     * @param string $confirm confirm intentions to delete
     *
     * @return view
     */

    function delete($name, $confirm = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');

        // Load dependencies
        //------------------

        $this->lang->load('web_access_control');
        $this->lang->load('web_proxy');
        $confirm_uri = '/app/web_access_control/acl/delete/' . $name . "/1";
        $cancel_uri = '/app/web_access_control';

        if ($confirm != NULL) {
            $this->squid->delete_time_acl($name);
            $this->squid->reset(TRUE);

            redirect('/web_access_control/acl');
        }

        $items = array($name);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Edit view.
     *
     * @param string $name ACL name
     *
     * @return view
     */

    function edit($name)
    {
        $this->_add_edit('edit', $name);
    }

    /**
     * Add or edit a time definition
     *
     * @param string $name     name of time definition
     * @param int    $priority integer to move up/down in priority
     *
     * @return view
     */

    function priority($name, $priority)
    {
        // Load libraries
        //---------------

        $this->lang->load('web_access_control');
        $this->load->library('web_proxy/Squid');

        try {
            $this->squid->bump_time_acl_priority($name, $priority);
            $this->squid->reset(TRUE);

            $this->page->set_status_updated();
        } catch (Exception $e) {
            $this->page->view_exception($e);
        }

        redirect('/web_access_control/acl');
    }

    /**
     * Command add/edit view.
     *
     * @param string $form_type form type
     * @param string $name ACL name
     *
     * @return view
     */

    function _add_edit($form_type, $name = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');
        $this->load->factory('groups/Group_Manager_Factory');
        $this->lang->load('web_access_control');
        $this->lang->load('base');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('name', 'web_proxy/Squid', 'validate_name', TRUE);
        $this->form_validation->set_policy('time', 'web_proxy/Squid', 'validate_time_acl', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('update') && $form_ok)) {
            try {
                $group = '';
                $ips = array();
                $macs = array();

                if ($this->input->post('ident') == 'src')
                    $ips = explode("\n", $this->input->post('ident_ip'));
                else if ($this->input->post('ident') == 'arp')
                    $macs = explode("\n", $this->input->post('ident_mac'));
                else if ($this->input->post('ident') == 'group')
                    $group = $this->input->post('ident_group');

                $this->squid->set_time_acl(
                    $this->input->post('name'),
                    $this->input->post('type'),
                    $this->input->post('time'),
                    $this->input->post('restrict'),
                    $group,
                    $ips,
                    $macs,
                    ($form_type == 'add' ? FALSE : TRUE)
                );

                $this->squid->reset(TRUE);

                $this->page->set_status_updated();
                redirect('/web_access_control/acl');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        try {
            $data['groups'] = $this->group_manager->get_list();
            $data['ident_options'] = $this->squid->get_identification_types();
            $time_options = $this->squid->get_time_definition_list();
            $type_options = $this->squid->get_access_types();
            $acls = $this->squid->get_acl_list();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        $data['name'] = $name;
        $data['form_type'] = $form_type;
        $data['group_options'] = array_keys($groups);
        $data['type_options'] = $type_options;

        // FIXME: what to do when no time periods are defined
        // This function returns array of info about the time entry... we just want the name
        foreach ($time_options as $info)
            $data['time_options'][$info['name']] = $info['name'];

        ksort($data['time_options']);

        $data['restrict_options'] = array(
            lang('web_access_control_outside_range'),
            lang('web_access_control_within_range')
        );

        foreach ($acls as $acl) {
            if ($acl['name'] == $name) {
                $data['type'] = $acl['type'];
                $data['time'] = $acl['time'];
                $data['restrict'] = $acl['logic'];

                if ($acl['groups']) {
                    $data['ident'] = 'group';
                    $data['ident_group'] = $acl['groups'];
                } else if ($acl['ips']) {
                    $data['ident'] = 'src';
                    $data['ident_ip'] = $acl['ips'];
                } else if ($acl['macs']) {
                    $data['ident'] = 'arp';
                    $data['ident_mac'] = $acl['macs'];
                }

                break;
            }
        }

        // Load the views
        //---------------

        $this->page->view_form('web_access_control/acl/add_edit', $data, lang('base_add'));
    }
}
