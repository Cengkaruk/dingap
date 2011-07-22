<?php

/**
 * System time manager view.
 *
 * @category   ClearOS
 * @package    IMAP
 * @subpackage Views
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('imap');

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('imap');
echo form_header(lang('imap_imap_and_pop_server'));

///////////////////////////////////////////////////////////////////////////////
// Form Fields and Buttons
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('base_settings'));
echo field_toggle_enable_disable('imaps', $imaps, lang('imap_imaps'));
echo field_toggle_enable_disable('pop3s', $pop3s, lang('imap_pop3s'));
echo field_toggle_enable_disable('imap', $imap, lang('imap_imap'));
echo field_toggle_enable_disable('pop3', $pop3, lang('imap_pop3'));
echo fieldset_footer();

echo fieldset_header(lang('imap_advanced_settings'));
echo field_toggle_enable_disable('idled', $idled, lang('imap_push_email'));
echo fieldset_footer();

echo field_button_set(array(
    form_submit_update('submit', 'high')
));

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
