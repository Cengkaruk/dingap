<?php

/**
 * Web access control time definition summary controller.
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
 * Web access control time definition summary controller.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_access_control/
 */

class Time extends ClearOS_Controller
{
    /**
     * Web Access Control default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('web_access_control');
        $this->load->library('web_proxy/Squid');

        // Load view data
        //---------------

        try {
            $data['time_definitions'] = $this->squid->get_time_definition_list();
            $data['day_of_week_options'] = $this->squid->get_days_of_week();
        } catch (Exception $e) {
            $this->page->view_exception($e);
        }

        // Load view
        //----------

        $this->page->view_form('web_access_control/time/summary', $data, lang('web_access_control_time_definitions'));
    }

    /**
     * Add time definition view.
     *
     * @return view
     */

    function add()
    {
        $this->_add_edit('add');
    }

    /**
     * Delete a time definition.
     *
     * @param string $name    name of time rule
     * @param string $confirm confirm intentions to delete
     *
     * @return view
     */

    function delete($name, $confirm = NULL)
    {
        // Load dependencies
        //------------------

        $this->lang->load('web_access_control');
        $this->load->library('web_proxy/Squid');

        // Handle form submit
        //-------------------

        $confirm_uri = '/app/web_access_control/time/delete/' . $name . "/1";
        $cancel_uri = '/app/web_access_control';

        if ($confirm != NULL) {
            try {
                $this->squid->delete_time_definition($name);
                $this->squid->reset(TRUE);
            } catch (Exception $e) {
                $this->page->view_exception($e);
            }

            redirect('/web_access_control/time');
        }

        $items = array($name . '  (' . lang('web_access_control_time_delete_warning') . ')');

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Add time definition view.
     *
     * @param string $name name of time definition
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
     * @param string $form_type form type
     * @param string $name name of time definition
     *
     * @return view
     */

    function _add_edit($form_type, $name = NULL)
    {
        // Load libraries
        //---------------

        $this->lang->load('base');
        $this->lang->load('web_access_control');
        $this->load->library('web_proxy/Squid');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('name', 'web_proxy/Squid', 'validate_name', TRUE);
        $this->form_validation->set_policy('dow', 'web_proxy/Squid', 'validate_day_of_week', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('update') && $form_ok)) {
            try {
                $this->squid->set_time_definition(
                    $this->input->post('name'),
                    $this->input->post('dow'),
                    $this->input->post('start_time'),
                    $this->input->post('end_time'),
                    ($form_type === 'add' ? FALSE : TRUE)
                );

                $this->squid->reset(TRUE);

                $this->page->set_status_added();
                redirect('/web_access_control/time');
            } catch (Exception $e) {
                $this->page->view_exception($e);
            }
        }

        try {
            $data['day_of_week_options'] = $this->squid->get_days_of_week();
            $time_definitions = $this->squid->get_time_definition_list();
        } catch (Exception $e) {
            $this->page->view_exception($e);
        }

        $data['name'] = $name;
        $data['form_type'] = $form_type;
        $data['time_options'] = array();

        for ($hour = 0; $hour < 24; $hour++) {
            for ($minute = 0; $minute < 60; $minute = $minute +15)
                $data['time_options'][] = sprintf("%02d", $hour) . ":" . sprintf("%02d", $minute);

        }

        $data['time_options'][] = '24:00';

        foreach ($time_definitions as $time) {
            if ($time['name'] == $name) {
                $data['start_time'] = $time['start'];
                $data['end_time'] = $time['end'];
                $data['days'] = array_values($time['dow']);
                break;
            }
        }

        // Load the views
        //---------------

        $this->page->view_form('web_access_control/time/add_edit', $data, lang('base_add'));
    }
}
