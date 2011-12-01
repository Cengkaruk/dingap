<?php

/**
 * NTP settings view.
 *
 * @category   ClearOS
 * @package    NTP
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ntp/
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
$this->lang->load('ntp');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

if ($thanks) {
    // FIXME: translate
    echo infobox_highlight('ClearCenter',
        "<p>Special thanks to ClearCenter for providing time servers.</p>"
    );
}

echo form_open('ntp');
echo form_header(lang('ntp_time_servers'));

$inx = 1;

foreach ($servers as $server) {
    echo field_input('server' . $inx, $server, '# ' . $inx, TRUE);
    $inx++;
}

echo form_footer();
echo form_close();
