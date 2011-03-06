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

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

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

class MySQL extends ClearOS_Controller
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

        $this->load->library('mysql/Mysql');

        // Set validation rules
        //---------------------
         
        // $this->form_validation->set_policy('current_password', 'mysql/Mysql', 'validate_password', TRUE);
        $this->form_validation->set_policy('password', 'mysql/Mysql', 'validate_password', TRUE);
        $this->form_validation->set_policy('verify', 'mysql/Mysql', 'validate_password', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                if ($this->input->post($current_password))
                    $current_password = $this->input->post($current_password);
                else
                    $current_password = '';

echo "dude $current_password " . $this->input->post($password);

                $this->mysql->set_root_password($current_password, $this->input->post($password));

                $this->page->set_success(lang('base_system_updated'));
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
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

        $this->page->set_title(lang('mysql_msyql'));

        $this->load->view('theme/header');

        if ($is_running)
            $this->load->view('mysql', $data);

        $this->load->view('theme/footer');
    }
}
