<?php

/**
 * Graphical console shutdown view.
 *
 * @category   ClearOS
 * @package    Graphical_Console
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/graphical_console/
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

$ip = $lan_ips['0']; // TODO: handle more scenarios

$url_ip = (empty($ip)) ? 'w.x.y.z' : $ip;

echo "
<div align='left' class='graphical-console-content'>

<h2 style='float:left;'>" . $os_name . " " . $os_version . "</h2>
<br style='clear:both;' />
<div style='float:left; width:215px; font-size: 10px;'>

Welcome!  This console is used to configure the network settings
on this system.  Once you have your network up and running, you can
install, configure and manage apps using a standard web browser.
Here's what you need to do next:
</div> 

<img style='float:left; margin-left:15px;' src='" . clearos_app_htdocs('graphical_console') . "/browsers.png' alt=''>

<br style='clear:both;' />
<h2 style='float:left;'>Step 1. Configure Your Network Settings</h2>

<div style='float:left;'>
";

if (empty($ip)) {
    echo "
It looks like you do not have an IP address available for remote connections.

<p align='center'>
" . anchor_custom('/app/network', 'Configure Network Now') . "
</p>
";

} else {
    echo "
The IP address of this system is: <b>$ip</b>. <br/> If you need to change your
network settings, you can <a style='background: transparent; border: none; float: none; padding: 0; margin: 0; color: #e1852e;' href='/app/network'>login to access the Network Console</a>.
";
}

echo "
</div>

<br style='clear:both;' />
<h2 style='float:left;'>Step 2. Use Your Web Browser</h2>

<div style='float:left; width:330px;'>
<div style='float:left;'>
";

if (empty($ip)) {
    echo "Once your network is configured, type the following address:";
} else {
    echo "Start your web browser and type the following web address:";
}

echo "
</div> <br/>
<div style='margin-top: 30px; margin-left: auto; margin-right: auto; width:162px;'><h2>https://$url_ip:81/</h2></div>
</div>

<img style='float:left; margin-left:15px;' src='" . clearos_app_htdocs('graphical_console') . "/webconfig.png'  alt=''>

</div>
";
