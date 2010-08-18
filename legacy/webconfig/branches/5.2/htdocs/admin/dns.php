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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Hosts.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-dns.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$edit_ip = "";
$hosts = new Hosts();

if (isset($_POST['AddHost'])) {
	try {
		$hosts->AddHost($_POST['new_ip'], $_POST['new_hostname']);
		$edit_ip = $_POST['new_ip'];
		ResetDnsmasq();		
	} catch (Exception $e) {
        WebDialogWarning($e->GetMessage());
    }
} elseif (isset($_POST['DeleteHost'])) {
	try {
		$hosts->DeleteHost(key($_POST['DeleteHost']));
		ResetDnsmasq();		
	} catch (Exception $e) {
        WebDialogWarning($e->GetMessage());
    }
} elseif (isset($_POST['UpdateHost'])) {
	try {
		$edit_ip = key($_POST['UpdateHost']);
		$hosts->UpdateHost(key($_POST['UpdateHost']), array_filter($_POST['update_hostname']));
		ResetDnsmasq();		
	} catch (Exception $e) {
        WebDialogWarning($e->GetMessage());
    }
} elseif (isset($_POST['EditHost'])) {
	$edit_ip = key($_POST['EditHost']);
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if ($edit_ip)
	EditHost($edit_ip);
else
	DisplayHosts();

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayHosts()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayHosts()
{
	global $hosts;

	try {
		$host_array = $hosts->GetHosts();
	} catch (Exception $e) {
        WebDialogWarning($e->GetMessage());
    }

	$hostrow = "";

	foreach ($host_array as $ip => $hostnames) {
		$numalias = count(array_filter($hostnames));
		$count = 1;
		$alias ='';

		while ($count < $numalias) {
			$alias .= " " . $hostnames[$count];
			$count++;
		}

		if (strlen($alias) > 20)
			$alias = substr($alias, 0, 20)."...";

		if ($ip == "127.0.0.1") {
			$hostrow .= "
			            <tr>
							<td nowrap>$ip</td>
							<td nowrap>" . $hostnames[0] . "</td>
							<td nowrap>" . $alias . "</td>
							<td>&#160; </td>
			            </tr>
			            ";
		} else {
			$id = $ip ."!".$hostnames[0];
			$hostrow .= "
			            <tr>
							<td nowrap>" . $ip . "</td>
							<td nowrap>" . $hostnames[0] . "</td>
							<td nowrap>" . $alias . "</td>
							<td nowrap>" . 
								WebButtonEdit("EditHost[$ip]") . 
								WebButtonDelete("DeleteHost[$ip]") . "
							</td>
			            </tr>
			            ";
		}
	}

	WebFormOpen();
	WebTableOpen(HOSTS_LANG_HOST, "100%");
	WebTableHeader(HOSTS_LANG_IP . "|" . HOSTS_LANG_HOSTNAME . "|" . HOSTS_LANG_ALIAS . "|");
	echo "
		$hostrow
		<tr>
			<td nowrap><input style='width:110px' type='text' name='new_ip' value='' /></td>
			<td nowrap><input style='width:150px' type='text' name='new_hostname' value='' /></td>
			<td>&#160;</td>
			<td nowrap>" . WebButtonAdd("AddHost") . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// EditHost()
//
///////////////////////////////////////////////////////////////////////////////

function EditHost($ip)
{
	global $hosts;

	try {
		$host_array = $hosts->GetHostnamesByIp($ip);
	} catch (Exception $e) {
        WebDialogWarning($e->GetMessage());
		return;
    }

	$index = 0;

	if (is_array($host_array)) {
		$hostrow = "";
		foreach ($host_array as $hostname) {
			$hostrow .= "
	            <tr>
	              <td>
                    <input style='width:260px' type='text' name='update_hostname[$index]' value='$hostname' />
                  </td>
	            </tr>
            ";
			$index++;
		}
	}

	WebFormOpen();
	WebTableOpen(LOCALE_LANG_EDIT . " " . HOSTS_LANG_HOSTNAME, "300px");
	WebTableHeader($ip);
	echo "
		$hostrow
		<tr>
		  <td><input style='width:260px' type='text' name='update_hostname[$index]'  value='' /></td>
		</tr>
		<tr>
		  <td>" . WebButtonUpdate("UpdateHost[$ip]") . WebButtonBack("Cancel") . "</td>
		</tr>
	";
	WebTableClose("300");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// ResetDnsmasq()
//
///////////////////////////////////////////////////////////////////////////////

function ResetDnsmasq()
{
	if (file_exists("../../api/DnsMasq.class.php")) {
		require_once("../../api/DnsMasq.class.php");

		try {
			$dnsmasq = new Dnsmasq();
			$dnsmasq->Reset();
		} catch (Exception $e) {
			// Not fatal
		}
	}
}

?>
