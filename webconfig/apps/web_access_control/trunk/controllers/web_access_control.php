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

        $acls = $this->squid->get_acl_list();

        // Load view
        //----------

        $this->page->view_form('web_access_control', NULL, lang('web_access_control_web_access_control'));
    }

    function add()
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');
        $this->lang->load('web_access_control');
        $this->lang->load('base');

        // Set validation rules
        //---------------------

        $is_action = FALSE;

        $this->form_validation->set_policy('iptables', 'web_access_control/Web_Access_Control', 'validate_iptables', TRUE);
        $this->form_validation->set_policy('description', 'web_access_control/Web_Access_Control', 'validate_description', TRUE);

        // Handle form submit
        //-------------------

        if ($this->form_validation->run()) {
            try {
                $this->squid->add_acl(
                    $this->input->post('iptables'),
                    $this->input->post('description'),
                    $this->input->post('enabled'),
                    $this->input->post('priority')
                );

                $this->page->set_status_added();
                redirect('/web_access_control');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // TODO - LDAP list
        $data['user_options'] = array('benjamin', 'joe', 'kris', 'mark');
        $data['type_options'] = $this->squid->get_access_type_array();
        $data['time_options'] = $this->squid->get_time_definition_list();
        $data['time_options'][-10] = lang('base_select');
        $data['time_options'][-1] = lang('web_access_control_add_time');
        ksort($data['time_options']);
        $data['restrict_options'] = array(
            lang('web_access_control_within_range'),
            lang('web_access_control_outside_range')
        );
        $data['ident_options'] = $this->squid->get_identification_type_array();
        $data['mode'] = 'add'; 

        // Load the views
        //---------------

        $this->page->view_form('web_access_control/add_edit', $data, lang('base_add'));
    }

    function add_time()
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');
        $this->lang->load('web_access_control');
        $this->lang->load('base');

        // Set validation rules
        //---------------------

        $is_action = FALSE;

        $this->form_validation->set_policy('iptables', 'web_access_control/Web_Access_Control', 'validate_iptables', TRUE);
        $this->form_validation->set_policy('description', 'web_access_control/Web_Access_Control', 'validate_description', TRUE);

        // Handle form submit
        //-------------------

        if ($this->form_validation->run()) {
            try {
                $this->squid->add_acl(
                    $this->input->post('iptables'),
                    $this->input->post('description'),
                    $this->input->post('enabled'),
                    $this->input->post('priority')
                );

                $this->page->set_status_added();
                redirect('/web_access_control');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
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

        $data['mode'] = 'add'; 

        // Load the views
        //---------------

        $this->page->view_form('web_access_control/add_edit_time', $data, lang('base_add'));
    }
}
