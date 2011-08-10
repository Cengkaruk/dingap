<?php

/**
 * Mail notification controller.
 *
 * @category   Apps
 * @package    Mail_Notification
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_notification/
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
 * Mail notification controller.
 *
 * @category   Apps
 * @package    Mail_Notification
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_notification/
 */

class Mail_Notification extends ClearOS_Controller
{
    /**
     * Mail Notification default controller
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * Mail Notification edit controller
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    function _view_edit($mode = null)
    {
        // Load dependencies
        //------------------

        $this->load->library('mail_notification/Mail_Notification');
        $this->lang->load('mail_notification');

        $data['mode'] = $mode;

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('sender', 'mail_notification/Mail_Notification', 'validate_email', TRUE);
        $this->form_validation->set_policy('host', 'mail_notification/Mail_Notification', 'validate_host', TRUE);
        $this->form_validation->set_policy('username', 'mail_notification/Mail_Notification', 'validate_username', FALSE);
        $this->form_validation->set_policy('password', 'mail_notification/Mail_Notification', 'validate_password', FALSE);
        $this->form_validation->set_policy('port', 'mail_notification/Mail_Notification', 'validate_port', TRUE);
        $this->form_validation->set_policy('ssl', 'mail_notification/Mail_Notification', 'validate_ssl', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->mail_notification->set_host($this->input->post('host'));
                $this->mail_notification->set_port($this->input->post('port'));
                $this->mail_notification->set_ssl($this->input->post('ssl'));
                $this->mail_notification->set_username($this->input->post('username'));
                $this->mail_notification->set_password($this->input->post('password'));
                $this->mail_notification->set_sender($this->input->post('sender'));
                $this->page->set_status_updated();
                redirect('/mail_notification');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['host'] = $this->mail_notification->get_host();
            $data['port'] = $this->mail_notification->get_port();
            $data['ssl'] = $this->mail_notification->get_ssl();
            $data['username'] = $this->mail_notification->get_username();
            $data['password'] = $this->mail_notification->get_password();
            $data['sender'] = $this->mail_notification->get_sender();
            $data['ssl_options'] = $this->mail_notification->get_ssl_options();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('mail_notification/settings', $data, lang('mail_notification_app_name'));
    }

    /**
     * Mail Notification test controller
     *
     * @return view
     */

    function test()
    {
        // Load dependencies
        //------------------

        $this->load->library('mail_notification/Mail_Notification');
        $this->lang->load('mail_notification');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('email', 'mail_notification/Mail_Notification', 'validate_email', TRUE);
        $form_ok = $this->form_validation->run();

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->mail_notification->test_relay($this->input->post('email'));
                $this->page->set_message(lang('mail_notification_test_success'), 'info');
                redirect('/mail_notification');
            } catch (Exception $e) {
                $this->page->set_message(clearos_exception_message($e));
            }
        }

        $this->page->view_form('mail_notification/test', $data, lang('mail_notification_test'));
    }
}
