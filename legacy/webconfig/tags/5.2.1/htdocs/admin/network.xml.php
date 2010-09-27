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
// TODO: avoid duplicating code in network.php

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/IfaceManager.class.php");
require_once("../../api/Resolver.class.php");
require_once("../../api/Syswatch.class.php");

WebAuthenticate();

try {
	$interfaces = new IfaceManager();
	$ethlist = $interfaces->GetInterfaceDetails();
} catch (Exception $e) {
	$ethlist = array();
}

$ifacelist = "";
sleep(2);

foreach ($ethlist as $eth => $info) {
	// Skip interfaces used 'indirectly' (e.g. PPPoE, bonded interfaces)
	if (isset($info['master']))
		continue;

	// Skip 1-to-1 NAT interfaces
	if (isset($info['one-to-one-nat']) && $info['one-to-one-nat'])
		continue;

	// Skip non-configurable interfaces
	if (! $info['configurable'])
		continue;

	// Create summary
	//---------------

	$ip = isset($info['address']) ? $info['address'] : "";
	$speed = (isset($info['speed']) && $info['speed'] > 0) ? $info['speed'] . " " . LOCALE_LANG_MEGABITS : "";
	$roletext = isset($info['roletext']) ? $info['roletext'] : "";
	$typetext = isset($info['typetext']) ? $info['typetext'] : "";
	$bootproto = isset($info['ifcfg']['bootprototext']) ? $info['ifcfg']['bootprototext'] : "";
	$configured = (isset($info['configured']) && $info['configured']) ? 1 : 0;

	if (isset($info['link'])) {
		if ($info['link'] == -1)
			$link = "";
		else if ($info['link'] == 0)
			$link = LOCALE_LANG_NO;
		else
			$link = LOCALE_LANG_YES;
	} else {
		$link = "";
	}

	if ($ip || !$configured) {
		$iplog = "";
	} else {
		$iface = new Iface($eth);
		$iplog = $iface->GetIpConnectionLog();
	}

	$ifacelist .= "
		<iface>
			<name>$eth</name>
			<ip>$ip</ip>
			<iplog>$iplog</iplog>
			<speed>$speed</speed>
			<role>$roletext</role>
			<type>$typetext</type>
			<bootproto>$bootproto</bootproto>
			<link>$link</link>
			<configured>$configured</configured>
		</iface>
	";
}

/* TODO: finish ajax implementation
try {
	$syswatch = new Syswatch();
	$working_wif = $syswatch->GetWorkingExternalInterfaces();
} catch (Exception $e) {
}

$nslist = "";

if (count($working_wif)) {
	$resolver = new Resolver();
	$nstest = $resolver->TestNameservers("sdn1.pointclark.com", 3);
	foreach ($nstest as $ip => $result)
		$nslist .= "<ns_$ip>" . $result['success'] . "</ns_$ip>";
}
*/
 
header('Content-Type: application/xml');
echo "<network>
$ifacelist
</network>
";

?>
