<?php

/**
 * Directory server controller.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
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
 * Directory server controller.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class OpenLDAP_Directory extends ClearOS_Controller
{
    /**
     * Directory server summary view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('openldap_directory');

        // Load views
        //-----------

        $views = array('openldap_directory/settings', 'openldap_directory/security', 'openldap_directory/information');

        $this->page->view_forms($views, lang('openldap_directory_app_name'));
    }
}
