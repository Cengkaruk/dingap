<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../api/Locale.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayModeWarning()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayModeWarning()
{
	WebTableOpen(FIREWALL_LANG_MODE, "100%");
	echo "
	  <tr>
		<td width='200' align='center'>" . WebUrlJump("network.php", WEB_LANG_CONFIGURE_MODE) . "</td>
	    <td class='help'>" . WEB_LANG_FIREWALL_MODE_WARNING . "<td>
	  </tr>
	";
	WebTableClose("100%");
}

?>
