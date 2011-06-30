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

echo "
<p>Welcome to ClearOS Enterprise 6!  ClearOS Enterprise is configured over the network 
via a web-browser.  Here's what you need to do:</p>

<p><b>Step 1. Check Network Settings</b></p>

<p>The IP address of this system is 1.2.3.4.  If you need to change this, please login 
<a href='blah''>here</a> to access the Network Console.</p>

<p><b>Step 2. Use Your Web Browser</b></p>

<p>Start your web browser and type the following for the web address:</p>


<p>Aaron, put the images in the htdocs directory and use the following URL</p>
<img src='/clearos/approot/graphical_console/trunk/htdocs/example.png'>

";
