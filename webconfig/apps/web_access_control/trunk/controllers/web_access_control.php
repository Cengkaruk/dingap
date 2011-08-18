<?php

/**
 * Web access control controller.
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
 * Mail Archive controller.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_access_control/
 */

class Web_Access_Control extends ClearOS_Controller
{

    /**
     * Mail Archive default controller
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

        // Load view
        //----------

        $this->page->view_form('web_access_control', $data, lang('web_access_control_web_access_control'));
    }

    function add_edit($name = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');
        $this->lang->load('users');
        $this->load->factory('users/User_Manager_Factory');
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
                // Want to grab the index of the time option
                $time_options = $this->squid->get_time_definition_list();
                $this->squid->set_time_acl(
                    $this->input->post('name'),
                    $this->input->post('type'),
                    $time_options[$this->input->post('time')]['name'],
                    $this->input->post('time_logic'),
                    $this->input->post('ident_user'),
                    explode("\n", $this->input->post('ident_ip')),
                    explode("\n", $this->input->post('ident_mac'))
                );

                $this->page->set_status_added();
                redirect('/web_access_control');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        try {
            $users = $this->user_manager->get_details();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // TODO - LDAP list
        $data['user_options'] = array_keys($users);
        $data['type_options'] = $this->squid->get_access_type_array();
        // This function returns array of info about the time entry...we just want the name
        $time_options = $this->squid->get_time_definition_list();
        $data['time_options'][-10] = lang('base_select');
        $data['time_options'][-1] = lang('web_access_control_add_time');
        foreach ($time_options as $info)
            $data['time_options'][] = $info['name'];
        ksort($data['time_options']);
        $data['restrict_options'] = array(
            lang('web_access_control_within_range'),
            lang('web_access_control_outside_range')
        );
        $data['ident_options'] = $this->squid->get_identification_type_array();
        if ($name == NULL)
            $data['mode'] = 'add'; 

        // Load the views
        //---------------

        $this->page->view_form('web_access_control/add_edit', $data, lang('base_add'));
    }

    function add_edit_time($name = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');
        $this->lang->load('web_access_control');
        $this->lang->load('base');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('name', 'web_proxy/Squid', 'validate_name', TRUE);
        $this->form_validation->set_policy('dow', 'web_proxy/Squid', 'validate_dow', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('update') && $form_ok)) {
            try {
                $this->squid->set_time_definition(
                    $this->input->post('name'),
                    $this->input->post('dow'),
                    $this->input->post('start_time'),
                    $this->input->post('end_time')
                );

                $this->page->set_status_added();
                // Send back to ACL add form
                redirect('/web_access_control/add_edit');
            } catch (Exception $e) {
                $this->page->set_message(clearos_exception_message($e));
            }
        }

        $data['day_of_week_options'] = array(
            'm' => lang('base_monday'),
            't' => lang('base_tuesday'),
            'w' => lang('base_wednesday'),
            'h' => lang('base_thursday'),
            'f' => lang('base_friday'),
            'a' => lang('base_saturday'),
            's' => lang('base_sunday')
        );
        $data['time_options'] = array();
        for ($hour = 0; $hour < 24; $hour++) {
            for ($minute = 0; $minute < 60; $minute = $minute +15)
                $data['time_options'][] = sprintf("%02d", $hour) . ":" . sprintf("%02d",$minute);
        }
        $data['time_options'][] = '24:00';

        if ($name == NULL)
            $data['mode'] = 'add'; 

        // Load the views
        //---------------

        $this->page->view_form('web_access_control/add_edit_time', $data, lang('base_add'));
    }
}
