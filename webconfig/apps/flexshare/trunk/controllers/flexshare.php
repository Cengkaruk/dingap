<?php

/**
 * Flexshare controller.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
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
 * Flexshare controller.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
 */

class Flexshare extends ClearOS_Controller
{
    /**
     * Flexshare server overview.
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('flexshare/Flexshare');
        $this->lang->load('flexshare');

        // Load view data
        //---------------

        try {
            $data['flexshares'] = $this->flexshare->get_shares();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('summary', $data, lang('flexshare_flexshare'));
    }

    /**
     * Flexshare add view.
     *
     * @param string $share share
     *
     * @return view
     */

    function add($share)
    {
        $this->_add_edit_view($share, 'add');
    }

    /**
     * Flexshare delete view.
     *
     * @param string $share share
     *
     * @return view
     */

    function delete($share = NULL)
    {
        $confirm_uri = '/app/flexshare/destroy/' . $share;
        $cancel_uri = '/app/flexshare';
        $items = array($share);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys Flexshare share.
     *
     * @param string $share share
     *
     * @return view
     */

    function destroy($share)
    {
        // Load libraries
        //---------------

        $this->load->factory('users/User_Factory', $share);

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
     * Flexshare edit view.
     *
     * @param string $share share
     *
     * @return view
     */

    function edit($share)
    {
        // $this->_add_edit_view($share, 'edit');
//        $views = array("flexshare/file/edit/$share", "flexshare/ftp/edit/$share");
        $views = array("flexshare/file/edit/$share", "flexshare/ftp/edit/$share");

        $this->page->view_forms($views, lang('flexshare_flexshare'));
    }

    /**
     * Flexshare view.
     *
     * @param string $share share
     *
     * @return view
     */

    function view($share)
    {
        $this->_add_edit_view($share, 'view');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Flexshare common add/edit/view form handler.
     *
     * @param string $share  share
     * @param string $form_type form type (add or edit)
     *
     * @return view
     */

    function _add_edit_view($share, $form_type)
    {
        // Load libraries
        //---------------

        $this->lang->load('flexshare');
/*
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
*/
        // Load the views
        //---------------

        $this->page->view_form('flexshare/add_edit', $data, lang('flexshare_flexshares'));
    }
}
