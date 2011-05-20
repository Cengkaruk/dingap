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

        $this->load->factory('users/User_Manager_Factory');
        $this->load->factory('accounts/Accounts_Factory');
        $this->lang->load('users');

        // Load view data
        //---------------

        try {
            $data['users'] = $this->user_manager->get_details();
/*
            $is_initialized = $this->accounts->is_initialized();
            $is_available = $this->accounts->is_available();
*/
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
     * Delete user view.
     *
     * @param string $username username
     *
     * @return view
     */

    function delete($username = NULL)
    {
        $confirm_uri = '/app/users/destroy/' . $username;
        $cancel_uri = '/app/users';
        $items = array($username);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
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

        $this->load->factory('users/User_Factory', $username);

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

        $this->load->factory('users/User_Factory', $username);
        $this->load->factory('accounts/Accounts_Factory');
        $this->lang->load('users');

        // Validate prep
        //--------------

        $info_map = $this->user->get_info_map();
        $this->load->library('form_validation');

        // Validate core
        //--------------

        foreach ($info_map['core'] as $key => $details) {
            $full_key = 'user_info[core][' . $key . ']';
            $this->form_validation->set_policy($full_key, $details['validator_class'], $details['validator']);
        }

        // Validate extensions
        //--------------------

        foreach ($info_map['extensions'] as $extension => $parameters) {
            foreach ($parameters as $key => $details) {
                $full_key = 'user_info[extensions][' . $extension . '][' . $key . ']';
                $this->form_validation->set_policy($full_key, $details['validator_class'], $details['validator']);
            }
        }

        // Validate plugins
        //-----------------

        foreach ($info_map['plugins'] as $plugin) {
            $full_key = 'user_info[plugins][' . $plugin . '][state]';
            $this->form_validation->set_policy($full_key, 'accounts/Accounts_Engine', 'validate_plugin_state');
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
            $data['form_type'] = $form_type;

            $data['username'] = $username;
            $data['info_map'] = $info_map;
            $data['user_info'] = $this->user->get_info();

            $data['extensions'] = $this->accounts->get_extensions();
            $data['plugins'] = $this->accounts->get_plugins();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('users/add_edit', $data, lang('users_user_manager'));
    }
}
