<?php

/**
 * Samba mode controller.
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\samba\Samba as Samba;
use \clearos\apps\mode\Mode_Engine as Mode_Engine;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba mode controller.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Mode extends ClearOS_Controller
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
        $this->load->factory('mode/Mode_Factory');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('mode', 'samba/Samba', 'validate_mode');
        $this->form_validation->set_policy('domain', 'samba/Samba', 'validate_workgroup');
        $this->form_validation->set_policy('profiles', 'samba/Samba', 'validate_roaming_profiles_state', TRUE);
        $this->form_validation->set_policy('logon_drive', 'samba/Samba', 'validate_logon_drive', TRUE);
        $this->form_validation->set_policy('logon_script', 'samba/Samba', 'validate_logon_script');
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $mode = $this->input->post('mode');

                if (($mode == Samba::MODE_PDC) || ($mode == Samba::MODE_SIMPLE_SERVER)) {
                    $this->samba->set_roaming_profiles_state($this->input->post('profiles'));
                    $this->samba->set_logon_drive($this->input->post('logon_drive'));
                    $this->samba->set_logon_script($this->input->post('logon_script'));
                }

                $this->samba->set_mode($this->input->post('mode'));
                $this->samba->set_workgroup($this->input->post('domain'));

                $this->page->set_status_updated();
                redirect('/samba/mode');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['mode'] = $this->samba->get_mode();
            $data['domain'] = $this->samba->get_workgroup();
            $data['profiles'] = $this->samba->get_roaming_profiles_state();
            $data['logon_script'] = $this->samba->get_logon_script();
            $data['logon_drive'] = $this->samba->get_logon_drive();
            $data['logon_drives'] = array(
                'L:', 'M:', 'N:', 'O:', 'P:', 'Q:', 'R:', 'S:', 'T:', 'U:', 'V:', 'W:', 'X:', 'Y:', 'Z:'
            );

            $master_modes = array(
                Samba::MODE_PDC => lang('samba_pdc'),
                Samba::MODE_SIMPLE_SERVER => lang('samba_simple_server')
            );

            $slave_modes = array(
                Samba::MODE_BDC => lang('samba_bdc'),
            );

            $server_mode = $this->mode->get_mode();

            if ($server_mode === Mode_Engine::MODE_MASTER) {
                $data['mode_read_only'] = TRUE;
                $data['domain_read_only'] = FALSE;
                $data['should_be_pdc_warning'] = ($data['mode'] != Samba::MODE_PDC) ? TRUE : FALSE;
                $data['modes'] = $master_modes;
            } else if ($server_mode === Mode_Engine::MODE_SLAVE) {
                $data['mode_read_only'] = TRUE;
                $data['domain_read_only'] = TRUE;
                $data['should_be_bdc_warning'] = ($data['mode'] != Samba::MODE_BDC) ? TRUE : FALSE;
                $data['modes'] = $slave_modes;
            } else {
                $data['mode_read_only'] = FALSE;
                $data['domain_read_only'] = FALSE;
                $data['unsupported_bdc_warning'] = ($data['mode'] == Samba::MODE_BDC) ? TRUE : FALSE;
                $data['modes'] = $master_modes;
            }
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('samba/mode', $data, lang('samba_mode'));
    }
}
