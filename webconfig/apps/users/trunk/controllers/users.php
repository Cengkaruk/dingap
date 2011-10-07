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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\accounts\Accounts_Engine as Accounts_Engine;
use \clearos\apps\accounts\Accounts_Not_Initialized_Exception as Accounts_Not_Initialized_Exception;
use \clearos\apps\accounts\Accounts_Driver_Not_Set_Exception as Accounts_Driver_Not_Set_Exception;

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
     * Users overview.
     */

    function index()
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            $this->status->widget();
            return;
        }

        // Load libraries and grab status information
        //-------------------------------------------

        try {
            $this->lang->load('users');
            $this->load->factory('users/User_Manager_Factory');
            $this->load->factory('accounts/Accounts_Factory');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load view data
        //---------------

        try {
            $data['users'] = $this->user_manager->get_core_details();
            $data['mode'] = ($this->accounts->get_capability() === Accounts_Engine::CAPABILITY_READ_WRITE) ? 'edit' : 'view';
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

    function add($username = NULL)
    {
        if (!isset($username) && $this->input->post('username'))
            $username = $this->input->post('username');

        $this->_item($username, 'add');
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
        $this->_item($username, 'edit');
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
        $this->_item($username, 'view');
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

    function _item($username, $form_type)
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            $this->status->widget();
            return;
        }

        // Load libraries
        //---------------

        $this->lang->load('users');
        $this->load->factory('users/User_Factory', $username);
        $this->load->factory('accounts/Accounts_Factory');

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
                if ($form_type === 'add')
                    $this->user->add($this->input->post('user_info'), $this->input->post('password'));
                else if ($form_type === 'edit')
                    $this->user->update($this->input->post('user_info'));

                $this->page->set_status_updated();
                redirect('/users');
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

            if ($form_type === 'add')
                $data['user_info'] = $this->user->get_info_defaults();
            else
                $data['user_info'] = $this->user->get_info();

            $data['extensions'] = $this->accounts->get_extensions();
            $data['plugins'] = $this->accounts->get_plugins();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('users/item', $data, lang('users_user_manager'));
    }
}
