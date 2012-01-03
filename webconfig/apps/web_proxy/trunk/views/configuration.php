<?php

/**
 * Configuration warning view.
 *
 * @category   ClearOS
 * @package    Web_Proxy
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/base/
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

$this->lang->load('web_proxy');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form 
///////////////////////////////////////////////////////////////////////////////

// FIXME: this looks like crap

if ($port == 'disabled') {
    // FIXME: translations
    echo infobox_warning(lang('base_warning'), 'Please disable proxy settings in your web browser');
} else {
    echo infobox_warning(lang('base_warning'), lang('web_proxy_configuration_settings_warning:') . 
        "<br><br>" .
        lang('network_ip') . ' - ' . $ip . '<br>' .
        lang('network_port') . ' - ' . $port . '<br>'
    );
}

/*
echo form_open('web_proxy/warning');
echo form_header(lang('web_proxy_web_proxy'));

echo field_view(lang('network_ip'), $ip, 'ip');
echo field_view(lang('network_port'), $port, 'port');

echo form_footer();
echo form_close();
*/
