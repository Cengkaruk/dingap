<?php

/**
 * Groups controller.
 *
 * @category   Apps
 * @package    Groups
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/groups/
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

use \clearos\apps\groups\Group as Group;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Groups controller.
 *
 * @category   Apps
 * @package    Groups
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/groups/
 */

class Groups extends ClearOS_Controller
{
    /**
     * Groups server overview.
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->factory('groups/Group_Manager');
        $this->lang->load('groups');

        // Load view data
        //---------------

        try {
            $data['normal_groups'] = $this->group_manager->get_details();
            $data['plugin_groups'] = $this->group_manager->get_details(Group::TYPE_PLUGIN);
            $data['windows_groups'] = $this->group_manager->get_details(Group::TYPE_WINDOWS);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('summary', $data, lang('groups_group_manager'));
    }

    /**
     * Group add view.
     *
     * @param string $group_name groupname
     *
     * @return view
     */

    function add($group_name)
    {
        $this->_add_edit_view($group_name, 'add');
    }

    /**
     * Group delete view.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function delete($group_name)
    {
        // Load libraries
        //---------------

        $this->lang->load('groups');

        // Load views
        //-----------

        $this->page->set_title(lang('groups_group'));
        $data['message'] = sprintf(lang('groups_confirm_delete'), $group_name);
        $data['ok_anchor'] = '/app/groups/destroy/' . $group_name;
        $data['cancel_anchor'] = '/app/groups';
    
        $this->load->view('theme/header');
        $this->load->view('theme/confirm', $data);
        $this->load->view('theme/footer');
    }

    /**
     * Destroys group.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function destroy($group_name)
    {
        // Load libraries
        //---------------

        $this->load->factory('groups/Group', $group_name);

        // Handle form submit
        //-------------------

        try {
            $this->group->delete();
            $this->page->set_status_deleted();
            redirect('/groups');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Group edit view.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function edit($group_name)
    {
        // Use common add/edit form
        $this->_add_edit_view($group_name, 'edit');
    }

    /**
     * User view.
     *
     * @param string $group_name group_name
     *
     * @return view
     */

    function view($group_name)
    {
        $this->_add_edit_view($group_name, 'view');
    }


    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Group common add/edit form handler.
     *
     * @param string $group_name group_name
     * @param string $form_type  form type (add, edit or view)
     *
     * @return view
     */

    function _add_edit_view($group_name, $form_type)
    {
        // Load libraries
        //---------------

        $this->load->factory('groups/Group', $group_name);
        $this->lang->load('groups');

        // Set validation rules
        //---------------------

        $this->load->library('form_validation');

        // $this->form_validation->set_policy($full_key, $details['validator_class'], $details['validator']);

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
            if ($form_type !== 'add')
                $data['group_info'] = $this->group->get_info();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $data['form_type'] = $form_type;

        // Load the views
        //---------------

        $this->page->view_form('groups/add_edit', $data, lang('groups_group_manager'));
    }
}
