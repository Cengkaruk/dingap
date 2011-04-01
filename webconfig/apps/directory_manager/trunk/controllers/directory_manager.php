<?php

/**
 * Directory manager controller.
 *
 * @category   Apps
 * @package    Directory_Manager
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_manager/
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
 * Directory manager controller.
 *
 * @category   Apps
 * @package    Directory_Manager
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_manager/
 */

class Directory_Manager extends ClearOS_Controller
{
    /**
     * Directory default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('directory_manager/Directory_Manager');
        $this->lang->load('directory_manager');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('mode', 'directory_manager/Directory_Manager', 'validate_mode', TRUE);

        $mode = $this->input->post('mode');

        if ($mode === 'master') {
            $this->load->factory('directory_manager/Directory');
            $this->form_validation->set_policy('domain', 'openldap/Directory_Driver', 'validate_domain', TRUE);

            echo $this->directory->get_groups_ou();
            
        $domain = $this->input->post('domain');
echo " master mode / $domain";
        }

        $form_ok = $this->form_validation->run();
/*
        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->time->set_time_zone($this->input->post('timezone'));
                $this->page->set_status_updated();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

*/
        try {
            $data['modes'] = $this->directory_manager->get_modes();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('directory_manager', $data, lang('directory_directory_manager'));
    }
}
