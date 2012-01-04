<?php

/**
 * Samba global settings controller.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
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
 * Samba global settings controller.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Settings extends ClearOS_Controller
{
    /**
     * Samba global settings controller.
     *
     * @return view
     */

    function index()
    {
        $this->_common('view');
    }

    /**
     * Edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_common('edit');
    }

    /**
     * View view.
     *
     * @return view
     */

    function view()
    {
        $this->_common('view');
    }

    /**
     * Common settings handler.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _common($form_type)
    {
        // Load dependencies
        //------------------

        $this->lang->load('base');
        $this->lang->load('samba');
        $this->load->library('samba/Samba');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('netbios', 'samba/Samba', 'validate_netbios_name', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
//                $this->pptpd->set_remote_ip($this->input->post('remote_ip'));
                $this->samba->set_netbios_name($this->input->post('netbios'));

                $this->page->set_status_updated();
                redirect('/samba/settings');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['netbios'] = $this->samba->get_netbios_name();
            $data['comment'] = $this->samba->get_server_string();
            $data['homes'] = $this->samba->get_homes_state();
            $data['wins_support'] = $this->samba->get_wins_support();
            $data['wins_server'] = $this->samba->get_wins_server();

            $data['show_printing'] = clearos_app_installed('print_server') ? TRUE : FALSE;
            $data['printing'] = $this->samba->get_printing_mode();
            $data['printing_options'] = $this->samba->get_printing_modes();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('samba/settings', $data, lang('base_settings'));
    }
}
