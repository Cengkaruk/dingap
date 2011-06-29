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
<p>We want users to start accessing ClearOS from a remote web browser right away.
The only thing that will be done from the console is network configuration.  So 
what do we need to show here?  A helpful blurb and graphic showing the user
the URL to login to webconfig (e.g. https://192.168.3.11:81).  If they need
to configure their network, a link to login should be shown.
</p>
<p>Note: the page resolution be less than 800x600.</p>
";
