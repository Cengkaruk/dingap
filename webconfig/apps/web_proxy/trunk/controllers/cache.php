<?php

/**
 * Web proxy cache controller.
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
 * Web proxy cache controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Cache extends ClearOS_Controller
{
    /**
     * Web proxy cache overview.
     *
     * @return view
     */

    function index()
    {
        $this->_form('view');
    }

    /**
     * Web proxy cache edit.
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
        $this->lang->load('base');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                // Update
                $this->squid->set_cache_size($this->input->post('cache'));
                $this->squid->set_maximum_file_download_size($this->input->post('download'));
                $this->squid->set_maximum_object_size($this->input->post('object'));
                $this->squid->reset(TRUE);

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

            $data['cache'] = $this->squid->get_cache_size();
            $data['object'] = $this->squid->get_maximum_object_size();
            $data['download'] = $this->squid->get_maximum_file_download_size();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $lang_megabytes = lang('base_megabytes');
        $lang_gigabytes = lang('base_gigabytes');

        $size_options = array(
            '1048576' => '1 ' . $lang_megabytes,
            '2097152' => '2 ' . $lang_megabytes,
            '3145728' => '3 ' . $lang_megabytes,
            '4194304' => '4 ' . $lang_megabytes,
            '5242880' => '5 ' . $lang_megabytes,
            '6291456' => '6 ' . $lang_megabytes,
            '7340032' => '7 ' . $lang_megabytes,
            '8388608' => '8 ' . $lang_megabytes,
            '9437184' => '9 ' . $lang_megabytes,
            '10485760' => '10 ' . $lang_megabytes,
            '20971520' => '20 ' . $lang_megabytes,
            '31457280' => '30 ' . $lang_megabytes,
            '41943040' => '40 ' . $lang_megabytes,
            '52428800' => '50 ' . $lang_megabytes,
            '62914560' => '60 ' . $lang_megabytes,
            '73400320' => '70 ' . $lang_megabytes,
            '83886080' => '80 ' . $lang_megabytes,
            '94371840' => '90 ' . $lang_megabytes,
            '104857600' => '100 ' . $lang_megabytes,
            '209715200' => '200 ' . $lang_megabytes,
            '314572800' => '300 ' . $lang_megabytes,
            '419430400' => '400 ' . $lang_megabytes,
            '524288000' => '500 ' . $lang_megabytes,
            '629145600' => '600 ' . $lang_megabytes,
            '734003200' => '700 ' . $lang_megabytes,
            '838860800' => '800 ' . $lang_megabytes,
            '943718400' => '900 ' . $lang_megabytes,
            '1073741824' => '1 ' . $lang_gigabytes,
            '2147483648' => '2 ' . $lang_gigabytes,
            '3221225472' => '3 ' . $lang_gigabytes,
            '4294967296' => '4 ' . $lang_gigabytes,
            '5368709120' => '5 ' . $lang_gigabytes,
            '6442450944' => '6 ' . $lang_gigabytes,
            '7516192768' => '7 ' . $lang_gigabytes,
            '8589934592' => '8 ' . $lang_gigabytes,
            '9663676416' => '9 ' . $lang_gigabytes,
            '10737418240' => '10 ' . $lang_gigabytes,
            '21474836480' => '20 ' . $lang_gigabytes,
            '32212254720' => '30 ' . $lang_gigabytes,
            '42949672960' => '40 ' . $lang_gigabytes,
            '53687091200' => '50 ' . $lang_gigabytes,
            '64424509440' => '60 ' . $lang_gigabytes,
            '75161927680' => '70 ' . $lang_gigabytes,
            '85899345920' => '80 ' . $lang_gigabytes,
            '96636764160' => '90 ' . $lang_gigabytes,
            '107374182400' => '100 ' . $lang_gigabytes,
            '214748364800' => '200 ' . $lang_gigabytes,
            '322122547200' => '300 ' . $lang_gigabytes,
            '429496729600' => '400 ' . $lang_gigabytes,
            '536870912000' => '500 ' . $lang_gigabytes,
            '644245094400' => '600 ' . $lang_gigabytes,
            '751619276800' => '700 ' . $lang_gigabytes,
            '858993459200' => '800 ' . $lang_gigabytes,
            '966367641600' => '900 ' . $lang_gigabytes,
        );

        $data['cache_options'] = $size_options;
        $data['object_options'] = $size_options;
        $data['download_options'] = $size_options;
        $data['download_options']['none'] = lang('base_unlimited');
 
        // Load views
        //-----------

        $this->page->view_form('web_proxy/cache/form', $data, lang('web_proxy_cache'));
    }

    /**
     * Resets the cache.
     *
     * @return JSON
     */

    function reset()
    {
        // Load dependencies
        //------------------

        $this->load->library('web_proxy/Squid');

        // Run synchronize
        //----------------

        try {
            $data['error_code'] = 0;
            $this->squid->clear_cache();
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
