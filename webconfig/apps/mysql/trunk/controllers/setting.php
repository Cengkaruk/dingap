<?php

/**
 * MySQL controller.
 *
 * @category   Apps
 * @package    MySQL
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mysql/
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

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * MySQL setting controller.
 *
 * @category   Apps
 * @package    MySQL
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mysql/
 */

class Setting extends ClearOS_Controller
{
    /**
     * MySQL default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('mysql/MySQL');
        $this->lang->load('mysql');

        // Set validation rules
        //---------------------
         
        if ($this->input->post('submit')) {
            $this->form_validation->set_policy('current_password', 'mysql/MySQL', 'validate_password', TRUE);
            $this->form_validation->set_policy('password', 'mysql/MySQL', 'validate_password', TRUE);
            $this->form_validation->set_policy('verify', 'mysql/MySQL', 'validate_password', TRUE);
        } else {
            $this->form_validation->set_policy('new_password', 'mysql/MySQL', 'validate_password', TRUE);
            $this->form_validation->set_policy('new_verify', 'mysql/MySQL', 'validate_password', TRUE);
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') || $this->input->post('submit_new')) && $form_ok) {
            try {
                if ($this->input->post('submit')) {
                    $current_password = $this->input->post('current_password');
                    $password = $this->input->post('password');
                    $verify = $this->input->post('verify');
                } else {
                    $current_password = '';
                    $password = $this->input->post('new_password');
                    $verify = $this->input->post('new_verify');
                }

                $match = $this->mysql->validate_password_verify($password, $verify);

                // FIXME: form_validation widget can't handle two inputs?
                if ($match)
                    throw new Engine_Exception($match);

                $this->mysql->set_root_password($current_password, $password);

                $this->page->set_message(lang('mysql_password_updated'), 'info');
                redirect('/mysql');

            } catch (Exception $e) {
                $this->page->view_exception($e);
            }
        }

        // Load view data
        //---------------

        try {
            $is_running = $this->mysql->get_running_state();
            $data['is_password_set'] = $this->mysql->is_root_password_set();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('mysql/setting', $data, lang('mysql_mysql_database'));
    }
}
