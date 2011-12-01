<?php

/**
 * Network DNS server controller.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
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

use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\network\Network as Network;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network DNS server controller.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class DNS extends ClearOS_Controller
{
    /**
     * General DNS overview.
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * General DNS edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    /**
     * General DNS read-only view.
     *
     * @return view
     */

    function view()
    {
        $this->_view_edit('view');
    }

    /**
     * Common view/edit form
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _view_edit($form_type)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Resolver');

        // Set validation rules
        //---------------------
         
        $dns = $this->input->post('dns');

        for ($dns_id = 1; $dns_id <= count($dns); $dns_id++)
            $this->form_validation->set_policy('dns[' . $dns_id . ']', 'network/Resolver', 'validate_ip');

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->resolver->set_nameservers($this->input->post('dns'));

                $this->page->set_status_updated();
                redirect('/network/dns');
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['dns'] = $this->resolver->get_nameservers();
            $data['is_automatic'] = $this->resolver->is_automatically_configured();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if (clearos_console())
            $options['type'] = MY_Page::TYPE_CONSOLE;

        $this->page->view_form('dns/view_edit', $data, lang('network_dns'), $options);
    }
}
