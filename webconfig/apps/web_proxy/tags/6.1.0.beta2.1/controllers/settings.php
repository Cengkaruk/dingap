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

        $this->lang->load('web_proxy');
        $this->load->library('web_proxy/Squid');
        $this->load->library('web_proxy/Squid_Firewall');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                $this->squid_firewall->set_proxy_transparent_state($this->input->post('transparent'));
                $this->squid->set_user_authentication_state($this->input->post('user_authentication'));
                // clearsync handles reload

                $this->page->set_status_updated();
                redirect('/web_proxy/settings');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;

            $data['transparent'] = $this->squid_firewall->get_proxy_transparent_state();
            $data['adzapper'] = $this->squid->get_adzapper_state();
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
