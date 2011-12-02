<?php

/**
 * Web proxy bypass controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
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
 * Web proxy bypass controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Bypass extends ClearOS_Controller
{
    /**
     * Web proxy bypass overview.
     *
     * @return view
     */

    function index()
    {
        $this->_form('view');
    }

    /**
     * Web proxy bypass edit.
     *
     * @return view
     */

    function edit()
    {
        $this->_form('edit');
    }

    /**
     * Common view/edit form.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _form($form_type)
    {
        // Load dependencies
        //------------------

        $this->load->library('web_proxy/Squid');
        $this->lang->load('web_proxy');
        $this->lang->load('base');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('domain', 'web_proxy/Squid', 'validate_domain', TRUE);
        $this->form_validation->set_policy('authoritative', 'web_proxy/Squid', 'validate_authoritative_state', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok)) {
            try {
                // Update
                $this->dnsmasq->set_domain_name($this->input->post('domain'));
                $this->dnsmasq->set_authoritative_state((bool)$this->input->post('authoritative'));

                // clearsync handles reload
                $this->page->set_status_updated();
                redirect('/web_proxy/bypass');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;

            $data['adzapper'] = $this->squid->get_adzapper_state();

/*
            $data['authoritative'] = $this->dnsmasq->get_authoritative_state();
*/
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('web_proxy/bypass/summary', $data, lang('web_proxy_web_site_bypass'));
    }
}
