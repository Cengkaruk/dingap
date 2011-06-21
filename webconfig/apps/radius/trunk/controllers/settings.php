<?php

/**
 * RADIUS server controller.
 *
 * @category   Apps
 * @package    RADIUS
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/radius/
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
 * RADIUS server controller.
 *
 * @category   Apps
 * @package    RADIUS
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/radius/
 */

class Settings extends ClearOS_Controller
{
    /**
     * RADIUS server summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('radius/FreeRADIUS');
        $this->lang->load('radius');

        // Load view data
        //---------------

        try {
            $data['clients'] = $this->freeradius->get_clients();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('radius/summary', $data, lang('radius_radius_server'));
    }

    /**
     * Add RADIUS entry view.
     *
     * @param string $ip IP
     *
     * @return view
     */

    function add($ip = NULL)
    {
        $this->_addedit($ip, 'add');
    }

    /**
     * Delete RADIUS entry view.
     *
     * @param string $ip IP
     *
     * @return view
     */

    function delete($ip = NULL)
    {
        $confirm_uri = '/app/radius/settings/destroy/' . $ip;
        $cancel_uri = '/app/radius/settings';
        $items = array($ip);
    
        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys RADIUS entry view.
     *
     * @param string $ip IP
     *
     * @return view
     */

    function destroy($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('radius/FreeRADIUS');

        // Handle delete
        //--------------

        try {
            $this->freeradius->delete_client($ip);

            $this->page->set_status_deleted();
            redirect('/radius');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Edit RADIUS entry view.
     *
     * @param string $ip IP
     *
     * @return view
     */

    function edit($ip = NULL)
    {
        $this->_addedit($ip, 'edit');
    }
    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * RADIUS entry rommon add/edit form handler.
     *
     * @param string $ip        IP
     * @param string $form_type form type
     *
     * @return view
     */

    function _addedit($ip, $form_type)
    {
        // Load libraries
        //---------------

        $this->load->library('radius/FreeRADIUS');

        // Set validation rules
        //---------------------

        // FIXME: discuss best approach
//        $key_validator = ($form_type === 'edit') ? 'validate_ip' : 'validate_unique_ip';

        if ($form_type === 'add')
            $this->form_validation->set_policy('ip', 'radius/FreeRADIUS', 'validate_ip', TRUE);

        $this->form_validation->set_policy('nickname', 'radius/FreeRADIUS', 'validate_nickname', TRUE);
        $this->form_validation->set_policy('password', 'radius/FreeRADIUS', 'validate_password', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            $ip = $this->input->post('ip');
            $nickname = $this->input->post('nickname');
            $password = $this->input->post('password');

            try {
                if ($form_type === 'edit') 
                    $this->freeradius->update_client($ip, $password, $nickname);
                else
                    $this->freeradius->add_client($ip, $password, $nickname);

                $this->freeradius->reset(TRUE);

                // Return to summary page with status message
                $this->page->set_status_added();
                redirect('/radius');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            if ($form_type === 'edit') 
                $info = $this->freeradius->get_client_info($ip);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $data['form_type'] = $form_type;
        $data['ip'] = $ip;
        $data['password'] = isset($info['password']) ? $info['password'] : '';
        $data['nickname'] = isset($info['nickname']) ? $info['nickname'] : '';

        // Load the views
        //---------------

        $this->page->view_form('add_edit', $data, lang('radius_client'));
    }
}
