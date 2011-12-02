<?php

/**
 * Web proxy warning controller.
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network\Network as Network;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Web proxy warning controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Warning extends ClearOS_Controller
{
    /**
     * Web proxy warning overview.
     *
     * @return view
     */

    function index($code, $url, $ip, $ftp_reply)

    {
        // Load dependencies
        //------------------

        $this->load->library('web_proxy/Squid');
        $this->lang->load('web_proxy');
        $this->lang->load('base');

        // Load view data
        //---------------

// FIXME: validate
        try {
/*
            $data['cache'] = $this->squid->get_cache_size();
            $data['object'] = $this->squid->get_maximum_object_size();
            $data['download'] = $this->squid->get_maximum_file_download_size();
*/
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $data['message'] = lang('web_proxy_warning_' . strtolower($code));
        $data['url'] = base64_decode(strtr($url, '-_.', '+/='));
        $data['ip'] = base64_decode(strtr($ip, '-_.', '+/='));
        $data['ftp_reply'] = base64_decode(strtr($ftp_reply, '-_.', '+/='));

        // IP is sent as "unknown" - blank it out
        if (preg_match('/(unknown|nothing)/i', $data['ip']))
            $data['ip'] = '...';

        // Load views
        //-----------

        $page['type'] = MY_Page::TYPE_SPLASH;

        $this->page->view_form('web_proxy/warning', $data, lang('base_warning'), $page);
    }

    /**
     * Proxy configuration warning.
     *
     * @return view
     */

    function configuration()
    {
        // Load dependencies
        //------------------

        $this->load->library('web_proxy/Squid_Firewall');
        $this->load->library('network/Network');
        $this->lang->load('web_proxy');

        // Load view data
        //---------------

        try {
            $mode = $this->network->get_mode();
            $filter_port = $this->squid_firewall->get_proxy_filter_port();
            $is_transparent = $this->squid_firewall->get_proxy_transparent_state();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $is_standalone = ($mode == Network::MODE_STANDALONE) ? TRUE : FALSE;
        $is_trusted_standalone = ($mode == Network::MODE_TRUSTED_STANDALONE) ? TRUE : FALSE;
        $is_filter = (empty($filter_port)) ? FALSE : TRUE;

        // This algorithm mimics how the firewall behaves.
        // Check /etc/rc.d/firewall.lua for details.
        $data['port'] = '...';

        if ($is_standalone || $is_trusted_standalone) {
            if ($is_filter)
                $data['port'] = '8080';
        } else if ($is_transparent) {
            $data['port'] = 'disabled';
        } else {
            if ($is_filter)
                $data['port'] = '8080';
            else
                $data['port'] = '3128';
        }

        $data['ip'] = getenv('SERVER_ADDR');

        // Load views
        //-----------

        $page['type'] = MY_Page::TYPE_SPLASH;

        $this->page->view_form('web_proxy/configuration', $data, lang('base_warning'), $page);
    }

    /**
     * Returns connection status.
     *
     * @return JSON
     */

    function get_status()
    {
        // Load dependencies
        //------------------

        $this->load->library('web_proxy/Squid');

        // Run synchronize
        //----------------

        try {
            sleep(2);
            $data['error_code'] = 0;
            $data['status_code'] = $this->squid->get_connection_status();
            $data['status_message'] = $this->squid->get_connection_status_message();
        } catch (Exception $e) {
            $data['error_code'] = clearos_exception_code($e);
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));
    }
}
