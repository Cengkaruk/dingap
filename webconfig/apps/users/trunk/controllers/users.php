<?php

/**
 * Users controller.
 *
 * @category   Apps
 * @package    Users
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/users/
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
 * Users controller.
 *
 * @category   Apps
 * @package    Users
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/users/
 */

class Users extends ClearOS_Controller
{
    /**
     * Users server overview.
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->factory('users/User_Manager');
        $this->lang->load('users');

        // Load view data
        //---------------

        try {
            $data['users'] = $this->user_manager->get_details();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('summary', $data, lang('users_user_manager'));
    }

    /**
     * User add view.
     *
     * @param string $username username
     *
     * @return view
     */

    function add($username)
    {
        // Use common add/edit form
        $this->_add_edit_view($username, 'add');
    }

    /**
     * User delete view.
     *
     * @param string $username username
     *
     * @return view
     */

    function delete($username)
    {
        // Load libraries
        //---------------

        $this->lang->load('users');

        // Load views
        //-----------

        $this->page->set_title(lang('users_user'));
        $data['message'] = sprintf(lang('users_confirm_delete'), $username);
        $data['ok_anchor'] = '/app/users/destroy/' . $username;
        $data['cancel_anchor'] = '/app/users';
    
        $this->load->view('theme/header');
        $this->load->view('theme/confirm', $data);
        $this->load->view('theme/footer');
    }

    /**
     * Destroys user.
     *
     * @param string $username username
     *
     * @return view
     */

    function destroy($username)
    {
        // Load libraries
        //---------------

        $this->load->factory('users/User', $username);

        // Handle form submit
        //-------------------

        try {
            $this->user->delete();
            $this->page->set_status_deleted();
            redirect('/users');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * User edit view.
     *
     * @param string $username username
     *
     * @return view
     */

    function edit($username)
    {
        // Use common add/edit form
        $this->_add_edit_view($username, 'edit');
    }

    /**
     * User view.
     *
     * @param string $username username
     *
     * @return view
     */

    function view($username)
    {
        // Use common add/edit form
        $this->_add_edit_view($username, 'view');
    }


    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * User common add/edit form handler.
     *
     * @param string $username  username
     * @param string $form_type form type (add or edit)
     *
     * @return view
     */

    function _add_edit_view($username, $form_type)
    {
        // Load libraries
        //---------------

        $this->load->factory('users/User', $username);
        $this->lang->load('users');

        // Grab info map first
        //--------------------

        $info_map = $this->user->get_info_map();

        // Set validation rules
        //---------------------

        $this->load->library('form_validation');

        foreach ($info_map['extensions'] as $extension => $parameters) {
            foreach ($parameters as $key => $details) {
                $full_key = 'user_info[extensions][' . $extension . '][' . $key . ']';
                $this->form_validation->set_policy($full_key, $details['validator_class'], $details['validator']);
            }
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            try {
                $this->user->update($this->input->post('user_info'));

                $this->page->set_status_updated();
                // FIXME
                //redirect('/users');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['info_map'] = $info_map;
            $data['user_info'] = $this->user->get_info();

            // FIXME - where should extension_info come from
            $data['plugin_info'] = array();
            $data['plugin_info']['pptp']['description'] = 'PPTP VPN';
            $data['plugin_info']['ftp']['description'] = 'FTP';

            $data['extension_info'] = array();
            $data['extension_info']['contact']['description'] = 'Contact Extension'; // FIXME
            $data['extension_info']['samba']['description'] = 'Samba Extension'; // FIXME
            $data['extension_info']['kolab']['description'] = 'Kolab Extension'; // FIXME
            $data['username'] = $username;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $data['form_type'] = $form_type;

        // Load the views
        //---------------

        $this->page->view_form('users/add_edit', $data, lang('users_user_manager'));
    }
}
