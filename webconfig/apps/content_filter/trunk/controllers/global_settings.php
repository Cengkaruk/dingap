<?php

/**
 * Content filter groups controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
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
 * Content filter groups controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
 */

class Global_Settings extends ClearOS_Controller
{
    /**
     * Global settings view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');

        // Load the views
        //---------------

        $this->page->view_form('content_filter/global', $data, lang('content_filter_policy'));
    }

    /**
     * Delete policy view.
     *
     * @param string $policy policy
     *
     * @return view
     */

    function delete($policy)
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');
        $this->load->library('content_filter/DansGuardian');

        // Load view data
        //---------------

        try {
            $policy_name = $this->dansguardian->get_policy_name($policy);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Show confirm
        //-------------

        $confirm_uri = '/app/content_filter/policy/destroy/' . $policy;
        $cancel_uri = '/app/content_filter';
        $items = array($policy_name);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys policy view.
     *
     * @param string $policy policy
     *
     * @return view
     */

    function destroy($policy)
    {
        // Load libraries
        //---------------

        $this->load->library('content_filter/DansGuardian');

        // Handle delete
        //--------------

        try {
            $this->dansguardian->delete_policy($policy);
            $this->dansguardian->reset(TRUE);

            $this->page->set_status_deleted();
            redirect('/content_filter');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Add policy view.
     *
     * @param integer $policy policy ID
     *
     * @return view
     */

    function edit($policy)
    {
        $this->_item('edit', $policy);
    }

    /**
     * Add policy view.
     *
     * @param string  $form_type form type
     * @param integer $policy    policy ID
     *
     * @return view
     */

    function _item($form_type, $policy)
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');
        $this->load->library('content_filter/DansGuardian');
        $this->load->factory('groups/Group_Manager_Factory');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('group', 'content_filter/DansGuardian', 'validate_group');
        $this->form_validation->set_policy('policy_name', 'content_filter/DansGuardian', 'validate_policy_name');
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && $form_ok) {
            try {
                if ($form_type === 'edit') {
                    $this->dansguardian->set_policy(
                        $policy, 
                        $this->input->post('policy_name'),
                        $this->input->post('group')
                    );
                    $this->page->set_status_updated();
                } else {
                    $this->dansguardian->add_policy(
                        $this->input->post('policy_name'),
                        $this->input->post('group')
                    );
                    $this->page->set_status_added();
                }

                $this->dansguardian->reset(TRUE);
                redirect('/content_filter/policy');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            if ($form_type === 'edit')
                $data['policy_name'] = $this->dansguardian->get_policy_name($policy);

            $data['policy'] = $policy;
            $data['form_type'] = $form_type;
            $data['groups'] = $this->group_manager->get_list();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('content_filter/policy/item', $data, lang('content_filter_policy'));
    }
}
