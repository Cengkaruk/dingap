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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Exceptions
//-----------

use \clearos\apps\flexshare\Flexshare_Parameter_Not_Found_Exception as Flexshare_Parameter_Not_Found_Exception;

clearos_load_library('flexshare/Flexshare_Parameter_Not_Found_Exception');

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
            $data['flexshares'] = $this->flexshare->get_share_summary();
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

        $this->load->library('flexshare/Flexshare');
        $this->load->library('groups/Group_Engine');
        $this->load->factory('groups/Group_Manager_Factory');
        $this->load->factory('users/User_Manager_Factory');
        $this->lang->load('flexshare');
        $this->lang->load('groups');
        $this->lang->load('users');

        // Create owner list
        //------------------
        try {
            $groups = $this->group_manager->get_details();
            $users = $this->user_manager->get_details();
            $group_options[-1] = lang('base_select');
            foreach ($groups as $name => $group)
                $group_options[$name] = lang('groups_group') . ' - ' . $name;
            foreach ($users as $name => $user)
                $group_options[$name] = lang('users_user') . ' - ' . $name;
            $data['group_options'] = $group_options;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        try {
            $data['directories'] = $this->flexshare->get_dir_options(NULL);
        } catch (Flexshare_Parameter_Not_Found_Exception $e) {
            // This is OK
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
        $this->page->view_form('flexshare/add_edit', $data, lang('flexshare_flexshares'));
    }
}
