<?php

/**
 * PPTP server view.
 *
 * @category   ClearOS
 * @package    PPTPd
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pptpd/
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('directory_server');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('directory_server');
echo form_header(lang('base_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form Fields and Buttons
///////////////////////////////////////////////////////////////////////////////

echo field_input('domain', $domain, lang('directory_server_domain'));
echo field_toggle_enable_disable('publish', $publish, lang('directory_server_publish_policy'));

echo form_submit_update('submit', 'high');

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();

echo form_open('directory_server');
echo form_header(lang('directory_server_connection_information'));
echo field_view(lang('directory_server_base_dn'), '', array('id' => 'base_dn'));
echo field_view(lang('directory_server_bind_dn'), '', array('id' => 'bind_dn'));
echo field_view(lang('directory_server_bind_password'), '', array('id' => 'bind_password'));
echo form_footer();
echo form_close();

echo "<div id='result'></div>";
