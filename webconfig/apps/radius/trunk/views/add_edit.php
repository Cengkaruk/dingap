<?php

/**
 * RADIUS Server add/edit view.
 *
 * @category   ClearOS
 * @package    RADISU
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/radius/
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

$this->load->language('base');
$this->load->language('network');
$this->load->language('radius');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = TRUE;
    $form_path = '/radius/settings/edit/' . $ip;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/radius/settings'),
        anchor_delete('/app/radius/settings/delete/' . $ip)
    );
} else {
    $read_only = FALSE;
    $form_path = '/radius/settings/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/radius/settings')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('radius_client'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo field_input('ip', $ip, lang('network_ip'), $read_only);
echo field_input('nickname', $nickname, lang('radius_nickname'));
echo field_input('password', $password, lang('base_password'));

echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
