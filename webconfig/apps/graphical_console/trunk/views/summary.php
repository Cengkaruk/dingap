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

$ip = $lan_ips['0']; // FIXME: handle more scenarios

echo "
<div align='left'>
<p>Welcome to ClearOS Enterprise 6.1.0 Beta 1!  ClearOS Enterprise is configured over the network 
via a web-browser.  Here's what you need to do:</p>

<p><b>Step 1. Check Your Network Settings</b></p>

<p>The IP address of this system is <b>$ip</b>.  If you need to change your
network settings, you can <b><a style='background: transparent; border: none; float: none; padding: 0; margin: 0; color: #8BB60' href='/app/network'>login to access the Network Console</a></b>.</p>

<p><b>Step 2. Use Your Web Browser</b></p>

<p>Start your web browser and type the following for the web address:</p>

<p>https://$ip:81/</p>

</div>

<img src='/clearos/approot/graphical_console/trunk/htdocs/example.png'>
";

/*
<p>Aaron, put the images in the htdocs directory and use the following URL</p>
<img src='/clearos/approot/graphical_console/trunk/htdocs/example.png'>
*/
