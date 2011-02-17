<?php

/**
 * IMAP controller.
 *
 * @category   Apps
 * @package    IMAP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/imap/
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

use \clearos\apps\imap\Cyrus as Cyrus;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * IMAP controller.
 *
 * @category   Apps
 * @package    IMAP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/imap/
 */

class IMAP extends ClearOS_Controller
{
    /**
     * IMAP default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('imap/Cyrus');
        $this->load->library('date/Time');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                $this->cyrus->set_service_state(Cyrus::SERVICE_POP3, $this->input->post('pop3'));
                $this->cyrus->set_service_state(Cyrus::SERVICE_POP3S, $this->input->post('pop3s'));
                $this->cyrus->set_service_state(Cyrus::SERVICE_IMAP, $this->input->post('imap'));
                $this->cyrus->set_service_state(Cyrus::SERVICE_IMAPS, $this->input->post('imaps'));
                $this->cyrus->set_idled_state($this->input->post('idled'));
                $this->cyrus->reset();

                $this->page->set_success(lang('base_system_updated'));
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['pop3'] = $this->cyrus->get_service_state(Cyrus::SERVICE_POP3);
            $data['pop3s'] = $this->cyrus->get_service_state(Cyrus::SERVICE_POP3S);
            $data['imap'] = $this->cyrus->get_service_state(Cyrus::SERVICE_IMAP);
            $data['imaps'] = $this->cyrus->get_service_state(Cyrus::SERVICE_IMAPS);
            $data['idled'] = $this->cyrus->get_idled_state();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->set_title(lang('imap_imap_and_pop_server'));

        $this->load->view('theme/header');
        $this->load->view('imap', $data);
        $this->load->view('theme/footer');
    }
}
