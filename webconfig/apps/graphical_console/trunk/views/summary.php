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

/* FIXME: wait until page is styled
if (! is_console())
	redirect('/base/session/login');
*/

$ip = $lan_ips['0']; // TODO: handle more scenarios

echo "
<div align='left' class='graphical-console-content'>

<div style='float:left; width:215px; font-size: 10px;'>
Welcome to ClearOS Enterprise 6.1.0 Beta 1!  ClearOS Enterprise is configured over the network via a web-browser.
Here's what you need to do:
</div> 

<img style='float:left; margin-left:15px;' src='" . clearos_app_htdocs('graphical_console') . "/browsers.png'>

<h2 style='float:left;'>Step 1. Check Your Network Settings</h2>

<div style='float:left;'>
The IP address of this system is <b>$ip</b>. <br/> If you need to change your
network settings, you can <a style='background: transparent; border: none; float: none; padding: 0; margin: 0; color: #e1852e;' href='/app/network'>login to access the Network Console</a>.
</div>

<h2 style='float:left;'>Step 2. Use Your Web Browser</h2>

<div style='float:left; width:330px;'>
<div style='float:left;'>Start your web browser and type the following for the web address:</div> <br/>
<div style='margin-top: 30px; margin-left: auto; margin-right: auto; width:162px;'><h2>https://$ip:81/</h2></div>
</div>

<img style='float:left; margin-left:15px;' src='" . clearos_app_htdocs('graphical_console') . "/webconfig.png'>

</div>
";
