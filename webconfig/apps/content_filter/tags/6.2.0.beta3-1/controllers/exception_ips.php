<?php

/**
 * Content filter exception IPs controller.
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
 * Content filter exception IPs controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
 */

class Exception_IPs extends ClearOS_Controller
{
    /**
     * Content filter exception IPs management default controller.
     *
     * @return view
     */

    function index()
    {
        $this->edit();
    }

    /**
     * IP add view.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');
        $this->load->library('content_filter/DansGuardian');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('ip', 'content_filter/DansGuardian', 'validate_ip', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && $form_ok) {
            try {
                $this->dansguardian->add_exception_ip($this->input->post('ip'));
                $this->dansguardian->reset(TRUE);

                $this->page->set_status_updated();
                redirect('/content_filter/exception_ips/edit');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        $data['type'] = 'exception';
        $data['policy'] = $policy;
    
        // Load views
        //-----------

        $this->page->view_form('content_filter/ip', $data, lang('content_filter_exception_ips'));
    }

    /**
     * IP edit view.
     *
     * @return view
     */

    function edit()
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');
        $this->load->library('content_filter/DansGuardian');

        // Load view data
        //---------------

        try {
            $data['type'] = 'exception';
            $data['ips'] = $this->dansguardian->get_exception_ips();
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('content_filter/ips', $data, lang('content_filter_exception_ips'));
    }

    /**
     * Delete IP entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function delete($ip)
    {
        $confirm_uri = '/app/content_filter/exception_ips/destroy/' . $ip;
        $cancel_uri = '/app/content_filter/exception_ips';
        $items = array($ip);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys IP from list.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function destroy($ip)
    {
        // Load libraries
        //---------------

        $this->load->library('content_filter/DansGuardian');

        // Handle delete
        //--------------

        try {
            $this->dansguardian->delete_exception_ip($ip);
            $this->dansguardian->reset(TRUE);

            $this->page->set_status_deleted();
            redirect('/content_filter/exception_ips');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
