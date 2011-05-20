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
     * Flexshare FTP overview.
     */

    function index($share)
    {
        $this->_add_edit_view($share, 'view');
    }

    /**
     * Flexshare edit view.
     *
     * @param string $share share
     *
     * @return view
     */

    function edit($share)
    {
        $this->_add_edit_view($share, 'edit');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Flexshare FTP common edit/view form handler.
     *
     * @param string $share     share
     * @param string $form_type form type
     *
     * @return view
     */

    function _add_edit_view($share, $form_type)
    {
        // Load libraries
        //---------------

        $this->lang->load('flexshare');

        // Load the view data 
        //------------------- 

        try {
            $data['form_type'] = $form_type;
            $data['share'] = $share;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('flexshare/ftp', $data, lang('flexshare_ftp'));
    }
}
