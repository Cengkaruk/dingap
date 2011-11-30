<?php

/**
 * FTP controller.
 *
 * @category   Apps
 * @package    FTP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ftp/
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
 * FTP controller.
 *
 * @category   Apps
 * @package    FTP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ftp/
 */

class Settings extends ClearOS_Controller
{
    /**
     * FTP default controller
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * FTP edit controller
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    /**
     * Common view/edit form
     *
     * @param string $mode form mode
     *
     * @return view
     */

    function _view_edit($mode)
    {
        // Load dependencies
        //------------------

        $this->load->library('ftp/ProFTPd');
        $this->lang->load('ftp');

        $data['mode'] = $mode;

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('server_name', 'ftp/ProFTPd', 'validate_server_name', TRUE);
        $this->form_validation->set_policy('max_instances', 'ftp/ProFTPd', 'validate_max_instances', TRUE);
        $this->form_validation->set_policy('port', 'ftp/ProFTPd', 'validate_port', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->proftpd->set_server_name($this->input->post('server_name'));
                $this->proftpd->set_max_instances($this->input->post('max_instances'));
                $this->proftpd->set_port($this->input->post('port'));

                $this->proftpd->reset(TRUE);

                $this->page->set_status_updated();
                redirect('/ftp');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['server_name'] = $this->proftpd->get_server_name();
            $data['max_instances'] = $this->proftpd->get_max_instances();
            $data['port'] = $this->proftpd->get_port();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('settings', $data, lang('ftp_ftp_server'));
    }
}
