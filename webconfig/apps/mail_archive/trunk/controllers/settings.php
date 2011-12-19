<?php

/**
 * Mail Archive Settings controller.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
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

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail Archive controller.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
 */

class Settings extends ClearOS_Controller
{

    /**
     * Mail Archive default controller
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * Mail Archive edit controller
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    function _view_edit($mode = null)
    {
        // Load dependencies
        //------------------

        $this->load->library('mail_archive/Mail_Archive');
        $this->lang->load('mail_archive');

        $this->mail_archive->run_bootstrap();

        $data['mode'] = $mode;

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('archive_status', 'mail_archive/Mail_Archive', 'validate_archive_status', TRUE);
        $this->form_validation->set_policy('discard_attachments', 'mail_archive/Mail_Archive', 'validate_discard_attachments', TRUE);
        $this->form_validation->set_policy('auto_archive', 'mail_archive/Mail_Archive', 'validate_auto_archive', TRUE);
        $this->form_validation->set_policy('encrypt', 'mail_archive/Mail_Archive', 'validate_encrypt', TRUE);
        if ($this->input->post('encrypt'))
            $this->form_validation->set_policy('encrypt_password', 'mail_archive/Mail_Archive', 'validate_encrypt_password', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->mail_archive->set_archive_status($this->input->post('archive_status'));
                $this->mail_archive->set_discard_attachments($this->input->post('discard_attachments'));
                $this->mail_archive->set_auto_archive($this->input->post('auto_archive'));
                $this->mail_archive->set_encrypt($this->input->post('encrypt'));
                if ($this->input->post('encrypt'))
                    $this->mail_archive->set_encrypt_password($this->input->post('encrypt_password'));
                $this->page->set_status_updated();
                redirect('/mail_archive');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['archive_status'] = $this->mail_archive->get_archive_status();
            $data['discard_attachments'] = $this->mail_archive->get_discard_attachments();
            $data['auto_archive'] = $this->mail_archive->get_auto_archive();
            $data['encrypt'] = $this->mail_archive->get_encrypt();
            if ($mode == 'edit')
                $data['encrypt_password'] = $this->mail_archive->get_encrypt_password();
            else
                $data['encrypt_password'] = str_pad('', strlen($this->mail_archive->get_encrypt_password()), "*");
            $data['discard_attachments_options'] = $this->mail_archive->get_discard_attachments_options();
            $data['auto_archive_options'] = $this->mail_archive->get_auto_archive_options();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('mail_archive/settings', $data, lang('mail_archive_mail_archive'));
    }

}
