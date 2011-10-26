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

class Policy extends ClearOS_Controller
{
    /**
     * DNS server summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');
        $this->load->library('content_filter/DansGuardian');

        // Load view data
        //---------------

        try {
            $data['groups'] = $this->dansguardian->get_filter_groups();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('content_filter/policies', $data, lang('content_filter_policies'));
    }

    /**
     * Add DNS entry view.
     *
     * @param string $policy policy
     *
     * @return view
     */

    function add()
    {
        $this->page->view_form('content_filter/policy/add', $data, lang('content_filter_policies'));
    }

    /**
     * Delete DNS entry view.
     *
     * @param string $policy policy
     *
     * @return view
     */

    function delete($policy)
    {
        $confirm_uri = '/app/dns/destroy/' . $policy;
        $cancel_uri = '/app/dns';
        $items = array($policy);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys DNS entry view.
     *
     * @param string $policy policy
     *
     * @return view
     */

    function destroy($policy)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hosts');
        $this->load->library('dns/Dnsmasq');

        // Handle delete
        //--------------

        try {
            $this->hosts->delete_entry($policy);
            $this->dnsmasq->reset();

            $this->page->set_status_deleted();
            redirect('/dns');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Edit policy view.
     *
     * @param string $policy policy
     *
     * @return view
     */

    function edit($policy)
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');
        $this->load->library('content_filter/DansGuardian');

        // Set validation rules
        //---------------------

/*
        $check_exists = ($form_type === 'add') ? TRUE : FALSE;

        $this->form_validation->set_policy('ip', 'network/Hosts', 'validate_ip', TRUE, $check_exists);
        $this->form_validation->set_policy('hostname', 'network/Hosts', 'validate_hostname', TRUE);

        foreach ($_POST as $key => $value) {
            if (preg_match('/^alias([0-9])+$/', $key))
                $this->form_validation->set_policy($key, 'network/Hosts', 'validate_alias');
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            $ip = $this->input->post('ip');
            $hostname = $this->input->post('hostname');
            $aliases = array();

            foreach ($_POST as $key => $value) {
                if (preg_match('/^alias([0-9])+$/', $key) && !(empty($value)))
                    $aliases[] = $this->input->post($key);
            }

            try {
                if ($form_type === 'edit') 
                    $this->hosts->edit_entry($ip, $hostname, $aliases);
                else
                    $this->hosts->add_entry($ip, $hostname, $aliases);

                $this->dnsmasq->reset();

                // Return to summary page with status message
                $this->page->set_status_added();
                redirect('/dns');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            if ($form_type === 'edit') 
                $entry = $this->hosts->get_entry($ip);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
*/

        $data['policy'] = $policy;
        $data['name'] = 'bob'; // FIXME

        // Load the views
        //---------------

        $this->page->view_form('content_filter/policy/summary', $data, lang('content_filter_policy'));
    }
}
