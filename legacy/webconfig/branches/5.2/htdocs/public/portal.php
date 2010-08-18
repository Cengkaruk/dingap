<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/Network.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

WebHeader(WEB_LANG_PAGE_TITLE, "splash");
WebDialogInfo(WEB_LANG_PAGE_INTRO);
DisplayConfig();
WebFooter("splash");

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig() {

	if (!file_exists("../../api/FirewallRedirect.class.php"))
		return;

	require_once("../../api/FirewallRedirect.class.php");

	$network = new Network(); // locale tags
	$firewall = new Firewall();
	$firewallredirect = new FirewallRedirect();

	try {
		$mode = $firewall->GetMode();
		$filter_port = $firewallredirect->GetProxyFilterPort();
		$is_transparent = $firewallredirect->GetProxyTransparentState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$is_standalone = ($mode == Firewall::CONSTANT_STANDALONE) ? true : false;
	$is_trusted_standalone = ($mode == Firewall::CONSTANT_TRUSTEDSTANDALONE) ? true : false;
	$is_filter = (empty($filter_port)) ? false : true;
	$ipaddr = getenv("SERVER_ADDR");

	// This algorithm mimics how the firewall behaves.
	// Check /etc/rc.d/firewall.lua for details.

	if ($is_standalone || $is_trusted_standalone) {
		if ($is_filter)
			$port = "8080";
	} else if ($is_transparent) {
		$port = "disabled";
	} else {
		if ($is_filter)
			$port = "8080";
		else
			$port = "3128";
	}

	if (! empty($port) && ($port != "disabled")) {
		WebTableOpen(WEB_LANG_DETAILS, "600");
		echo "
			<tr>
				<td class='mytablesubheader'>&nbsp; </td>
				<td>" . WEB_LANG_CONFIGURE_PROXY_SERVER . "</td>
			</tr>
			<tr>
				<td width='150' nowrap align='right' class='mytablesubheader'>" . NETWORK_LANG_IP . "</td>
				<td>$ipaddr</td>
			</tr>
			<tr>
				<td nowrap align='right' class='mytablesubheader'>" . NETWORK_LANG_PORT . "</td>
				<td>$port</td>
			</tr>
		";
		WebTableClose("600");
	}
}

// vim: syntax=php ts=4
?>
