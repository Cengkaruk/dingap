<?php

/**
 * Flexshare FTP controller.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
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

// Classes
//--------

use \clearos\apps\flexshare\Flexshare as Flexshare;

// TODO for Pete:  Why does enabling line below give:
// Fatal error: Call to a member function load() on a non-object i
// Is it needed?
//clearos_load_library('flexshare/Flexshare');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Flexshare FTP controller.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
 */

class FTP extends ClearOS_Controller
{
    /**
     * Flexshare FTP default controller.
     */

    function index($share)
    {
        $this->configure($share);
    }

    /**
     * Flexshare edit view.
     *
     * @param string $share share
     *
     * @return view
     */

    function configure($share)
    {
        // Load libraries
        //---------------

        $this->load->library('flexshare/Flexshare');
        $this->lang->load('flexshare');

        $this->form_validation->set_policy('ftp_enabled', 'flexshare/Flexshare', 'validate_ftp_enabled', TRUE);
        $this->form_validation->set_policy('ftp_server_url', 'flexshare/Flexshare', 'validate_ftp_server_url', TRUE);
        $this->form_validation->set_policy('ftp_req_ssl', 'flexshare/Flexshare', 'validate_ftp_req_ssl', TRUE);
        //$this->form_validation->set_policy('ftp_override_port', 'flexshare/Flexshare', 'validate_ftp_override_port', TRUE);
        //$this->form_validation->set_policy('ftp_allow_passive', 'flexshare/Flexshare', 'validate_ftp_allow_passive', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->flexshare->set_ftp_enabled($share, $this->input->post('ftp_enabled'));
                $this->flexshare->set_ftp_server_url($share, $this->input->post('ftp_server_url'));
                $this->flexshare->set_ftp_req_ssl($share, $this->input->post('ftp_req_ssl'));
                $this->flexshare->set_ftp_override_port(
                    $share, $this->input->post('ftp_override_port'),
                    $this->input->post('ftp_port')
                );
                $this->flexshare->set_ftp_allow_passive($share, $this->input->post('ftp_allow_passive'));
            } catch (Exception $e) {
                $this->page->set_message(clearos_exception_message($e));
            }
        }

        // Load view data
        //--------------- 

        try {
            $data['share'] = $this->flexshare->get_share($share);

            // Default Port
            if ((int)$data['share']['FtpPort'] == 0)
                $data['share']['FtpPort'] = Flexshare::DEFAULT_PORT_FTP;

            // Passive port range
            if ((int)$data['share']['FtpPassivePortMin'] == 0)
                $data['share']['FtpPassivePortMin'] = Flexshare::FTP_PASV_MIN;

            if ((int)$data['share']['FtpPassivePortMax'] == 0)
                $data['share']['FtpPassivePortMax'] = Flexshare::FTP_PASV_MAX;

            $data['group_permission_options'] = $this->flexshare->get_ftp_permission_options();
            $data['anonymous_permission_options'] = $this->flexshare->get_ftp_permission_options();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('flexshare/ftp', $data, lang('flexshare_ftp'));
    }
}
