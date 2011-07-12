<?php

/**
 * Web proxy general settings controller.
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
 * Web proxy general settings controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Settings extends ClearOS_Controller
{
    /**
     * Web proxy general settings overview.
     *
     * @return view
     */

    function index()
    {
        $this->_form('view');
    }

    /**
     * Web proxy general settings edit.
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
                $this->dnsmasq->reset(TRUE);

                // Redirect to main page
                 $this->page->set_status_updated();
                redirect('/web_proxy/');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;

            $data['transparent'] = $this->squid->get_transparent_mode_state();
            $data['adzapper'] = $this->squid->get_adzapper_state();
            $data['filter'] = $this->squid->get_content_filter_state();
            $data['user_authentication'] = $this->squid->get_user_authentication_state();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('web_proxy/settings/form', $data, lang('base_settings'));
    }
}
