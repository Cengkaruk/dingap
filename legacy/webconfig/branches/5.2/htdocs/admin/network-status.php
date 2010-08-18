<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2006 Point Clark Networks.
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
require_once("../../api/Network.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/IfaceManager.class.php");
require_once("../../api/Iface.class.php");
require_once("../../api/NetStat.class.php");
require_once("../../api/Routes.class.php");
require_once("../../api/ConnectionTracking.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

$colors['inet']   = "#FF0000"; // red
$colors['extif']  = "#FFA500"; // orange
$colors['dmzif']  = "#FFD700"; // gold
$colors['lanif']  = "#9ACD32"; // yellowgreen
$colors['lo']	  = "#000000"; // black
$colors['table1'] = "#F0F0F0"; // light grey 1
$colors['table2'] = "#E0E0E0"; // light grey 2
$colors['table3'] = "#FFF8DC"; // cornsilk
$colors['font1']  = "#000000"; // black
$colors['font2']  = "#FFFFFF"; // white

$customhead = CustomStyle($colors);

$refresh = (isset($_REQUEST['refresh']) && is_numeric($_REQUEST['refresh'])) ? $_REQUEST['refresh'] : 0;

if (is_numeric($refresh) && ($refresh > 0)) {
	$customhead .= '<meta http-equiv="refresh" content="' . $refresh . ';url=' .
		$_SERVER['PHP_SELF'] . '?cmon&amp;refresh=' . $refresh . '">';
}

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $customhead);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-nettools.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

// get routes and roles for the ipcolor function
// we use these globally so they don't have to
// re-read everytime we access the function

try {
	$firewall = new Firewall();
	$ifman = new IfaceManager();

	$devices = $ifman->GetInterfaces(false, false);

	foreach($devices as $device) {
		$iface = new Iface($device);
		$ip = $iface->GetLiveIp(&$errors[]);

		if (empty($ip))
			continue;

		if (count(array_filter($errors)) > 0)
			break;

		$mask = $iface->GetLiveNetmask(&$errors[]);

		if (count(array_filter($errors)) > 0)
			break;

		$network = long2ip(ip2long($ip) & ip2long($mask));
		$routes[$device]= "$network,$mask";
		$roles[$device] = strtolower($firewall->GetInterfaceRole($device, &$errors[]));
		unset($iface); // release unneed object
	}

	$roles['lo'] = 'lo';
} catch (Exception $e) {
	// do nothing?
}

$option = isset($_REQUEST['option']) ? $_REQUEST['option'] : "cmon";

DisplayMenu($option);

if (isset($_POST['DisplayConnectionTracking'])) {
	ShowConntrack($refresh);
} else if ($option == "cmon") {
	ShowConntrack($refresh);
} else if ($option == "route") {
	ShowNetstat(WEB_LANG_PAGE_ROUTE, "r");
} else if ($option == "proto") {
	ShowNetstat(WEB_LANG_PAGE_PROTOCOL_STATS, "s");
} else {
	ShowConntrack($refresh);
}

WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

function ShowNetstat($title, $switches)
{
	$netstat = new Netstat();

	$output = $netstat->Execute($switches, true);

	$headers = preg_grep("/^([\w]+):$/", $output);
	$body = '';

   if (empty($headers)) {
		$body = "<tr><td><pre>";
		$body .= implode("\n", $output);
		$body .= "\n</pre></td></tr>";
	} else {
		foreach($output as $line) {
			if (in_array($line, $headers)) {
				$line = preg_replace("/:/", "", $line);
				$body .= "<tr><td colspan='2' class='mytableheader'>$line</td><tr><td>&#160;</td><td valign='top' nowrap>\n";
			} else {
				$body .= $line."<br/>";
			}
		}

		$body .= "</tr>";
	}

	WebFormOpen();
	WebTableOpen($title, "100%");
	WebTableBody($body);
	WebTableClose("100%");
	WebFormClose();
}

function ShowConntrack($refresh)
{
	try {
		$conntrack = new ConnectionTracking();
		$listdata = $conntrack->GetList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$body ='';
	unset($listdata[0]);

	foreach($listdata as $data) {
		switch ($data[0]) {

		case "UDP":
			$proto = 'net2';
			$data[6] = $data[5];
			$data[5] = $data[4];
			$data[4] = "&#160;";
			break;

		case "GRE":
			$proto = 'net3';
			$data[4] = "&#160;";
			$data[5] = "&#160;";
			$data[6] = "PPTP";
			break;

		default:
			$proto = 'net1';
		}

		if (empty($data[6]))
			$data[6] = "";

		$src = ipcolor($data[2]);
		$dst = ipcolor($data[3]);
		$dataline = implode("</td><td nowrap class='%s'>", $data);
		$lineout = sprintf(preg_replace("/<td>$/", "", $dataline), $proto, $src, $dst, $proto, $proto, $proto);

		$body .= "<tr><td nowrap class='$proto'>$lineout</td></tr>";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_CMON, "350");
	echo "
		<tr>
		  <td class='mytablesubheader'>" .  LOCALE_LANG_REFRESH . "</td>
		  <td>
			<input type='text' size=3 maxlength=3 name='refresh' value='$refresh' />
			<input type='hidden' name='option' value='cmon' />" .
			WebButtonUpdate('DisplayConnectionTracking') . "
		  </td>
		</tr>
	";
	WebTableClose("350");
	WebFormClose();

	if ($body) {
		$legend = "
			<tr>
				<td colspan='7'>
					<table width='100%' cellpadding='3' cellspacing='0' border='0'>
						<tr>
							<td align='center' width='20%' class='inet'>" . FIREWALL_LANG_INTERNET . "</td>
							<td align='center' width='20%' class='extif'>" . FIREWALL_LANG_EXTERNAL . "</td>
							<td align='center' width='20%' class='dmzif'>" . FIREWALL_LANG_DMZ . "</td>
							<td align='center' width='20%' class='lanif'>" . FIREWALL_LANG_LAN . "</td>
							<td align='center' width='20%' class='lo'>" . FIREWALL_LANG_LOOPBACK . "</td>
						</tr>
					</table>
				</td>
			</tr>
		";

		$network = new Network(); // Locale tags
		$firewall = new Firewall(); // Locale tags
		WebFormOpen();
		WebTableOpen(WEB_LANG_PAGE_CONNTRACK_COUNT . " - " . count($listdata), "100%");
		WebTableHeader(
			FIREWALL_LANG_PROTOCOL . "|" .
			WEB_LANG_EXPIRES . "|" .
			NETWORK_LANG_SOURCE . "|" .
			NETWORK_LANG_DESTINATION . "|" .
			LOCALE_LANG_STATUS . "|" .
			FIREWALL_LANG_PORT . "|" .
			FIREWALL_LANG_SERVICE
		);
		WebTableBody($body);
		echo $legend;
		WebTableClose("100%");
		WebFormClose();
	}
}

function ParseNetstat($output, $colorize)
{

	ob_start();
	foreach ($output as $line) {
		$temp = preg_split('[\s]',$line,-1,PREG_SPLIT_NO_EMPTY);
		$tc=count($temp);

		if ($temp[0] == "Active") {
			echo "<table align='center' summary='Netstat info ".$temp[1]."'>";
			echo "<tr><th align='left' class='net2' colspan='20'>".$line."</th></tr>\n";
		}

		elseif ($temp[0] == "Proto") {
			$TableHeader ='';
			foreach( $temp as $headerdata )
			$TableHeader .= $headerdata ."|";

			if  (strpos($TableHeader,"Timer") === false)
				$TableHeader = preg_replace("/\|$/","",$TableHeader);

			$TableHeader = preg_replace("/Local\|Address/","Local Address",$TableHeader);

			$TableHeader = preg_replace("/Foreign\|Address/","Foreign Address",$TableHeader);

			$TableHeader = preg_replace("/Program\|name/","Program name",$TableHeader);

			$max_cols=count(explode("|",$TableHeader));

			WebTableHeader($TableHeader);
		}

		elseif ($temp[0] == "udp") {
			echo "<tr class='net1'>";
			$cols=0;

			if ($tc > 4) {
				$skipstate=0;
				foreach ($temp as $tabledata) {
					$ip_data = explode(":",$tabledata);
					// check for IPv6 address
					$c = count($ip_data);

					if ( $c > 2 ) {
						$x = 0;
						$c--;

						while ($x < $c) {
							if (empty($ip_data[$x])) {
								$ip_data[0] .= ":";
							}

							elseif ($x < ($c - 1)) {
								$ip_data[0] .= $ip_data[$x] . ":";
							}
							else {
								$ip_data[0] .= $ip_data[$x];
							}

							$x++;
						}
					}

					if ((count($ip_data) > 1) and $colorize) {
						$style = ipcolor($ip_data[0]);
					} else {
						$style = 'net1';
					}

					echo "<td class='$style'>$tabledata</td>";
					$skipstate++;

					if ($skipstate == 5) {
						echo "<td></td>";
						$cols++;
					}

					$cols++;
				}
			} else {
				foreach ($temp as $tabledata) {
					$ip_data = explode(":",$tabledata);
					// check for IPv6 address
					$c = count($ip_data);

					if ( $c > 2 ) {
						$x = 0;
						$c--;

						while ($x < $c) {
							if (empty($ip_data[$x])) {
								$ip_data[0] .= ":";
							}

							elseif ($x < ($c - 1)) {
								$ip_data[0] .= $ip_data[$x] . ":";
							}
							else {
								$ip_data[0] .= $ip_data[$x];
							}

							$x++;
						}
					}

					if ((count($ip_data) > 1) and $colorize) {
						$style = ipcolor($ip_data[0]);
					} else {
						$style = 'net1';
					}

					$cols++;
				}
			}

			while ($cols < $max_cols) {
				echo "<td></td>";
				$cols++;
			}

			echo "</tr>\n";
		}
		else {
			echo "<tr class='net1'>\n";
			$cols = 0;
			$xx = 0;

			while ($xx < $tc ) {
				$tabledata = $temp[$xx];

				if ( $tabledata == "[") {
					$tabledata = "";

					while ($temp[$xx]<>"]") {
						$tabledata .= " ".$temp[$xx];
						$xx++;
					}

					$tabledata .= " ".$temp[$xx];
				}

				$ip_data = explode(":",$tabledata);
				// check for IPv6 address
				$c = count($ip_data);

				if ( $c > 2 ) {
					$x = 0;
					$c--;

					while ($x < $c) {
						if (empty($ip_data[$x])) {
							$ip_data[0] .= ":";
						}

						elseif ($x < ($c - 1)) {
							$ip_data[0] .= $ip_data[$x] . ":";
						}
						else {
							$ip_data[0] .= $ip_data[$x];
						}

						$x++;
					}
				}

				if ((count($ip_data) > 1) and $colorize) {
					$style = ipcolor($ip_data[0]);
				} else {
					$style = 'net1';
				}

				if (! (preg_match("/\[[0-9.]+/",$tabledata)) or ( $temp[0]) == "unix") {
					echo "<td class='$style'>$tabledata</td>";
				}

				if ( $tabledata == "DGRAM") {
					echo "<td></td>";
					$cols++;
				}

				$xx++;
				$cols++;
			}

			while ($cols < $max_cols) {
				echo "<td></td>";
				$cols++;
			}

			echo "\n</tr>\n";
		}
	}

	echo "</table></td></tr>";
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

function ipcolor($ip)
{
	global $routes, $roles;

	$ip = trim($ip);

	switch ($ip) {
		case "::1":
			return 'lo';

		case ":::":
			return 'inet';

		default:
			$ip = preg_replace("/::ffff:/","",$ip);
	}

	// TODO: grab EXTRALANS information in /etc/system/network -- very kludgy for now
	$extralans = array();
	try {
		$sysroutes = new Routes();
		$extralans = $sysroutes->GetExtraLans();
	} catch (Exception $e) {
	}

	foreach ($extralans as $route) {
		list($network, $mask) = explode("/", $route);

		if (! preg_match("/\./", $mask)) {
			$netclass = new Network();
			$mask = $netclass->GetNetmask($mask);
		}

		if ((ip2long($ip) & ip2long($mask)) == ip2long($network)) {
			if ($network == "0.0.0.0")
				return 'inet';
			else
				return 'lanif';
		}
	}

	// Determine color based on routes
	if (! is_array($routes))
		return 'inet';

	foreach ($routes as $device => $data) {
		list($network, $mask) = explode(",", $data);

		if ((ip2long($ip) & ip2long($mask)) == ip2long($network)) {
			if ($network == "0.0.0.0")
				return 'inet';
			else
				return $roles[$device];
		}
	}

	return 'inet';
}

function CustomStyle($colors)
{
	$font = "";
	$style  = "
		<style type='text/css'>
		<!--
		switch   {font:8pt courier;line-height:8pt}
		.lo	  {color: " . $colors['font2'] . "; background: " .$colors['lo'] . "; $font}
		a.lo	 {color: " . $colors['font2'] . "; $font}
		.extif   {color: " . $colors['font1'] . "; background: " . $colors['extif'] . "; $font}
		a.extif  {color: " . $colors['font1'] . "; $font}
		.inet	{color: " . $colors['font1'] . "; background: " . $colors['inet'] . "; $font}
		a.inet   {color: " . $colors['font1'] . "; $font}
		.dmzif   {color: " . $colors['font1'] . "; background: " . $colors['dmzif'] . "; $font}
		a.dmzif  {color: " . $colors['font1'] . "; $font}
		.lanif   {color: " . $colors['font1'] . "; background: " . $colors['lanif'] . "; $font}
		a.lanif  {color: " . $colors['font1'] . "; $font}
		.net1	{color: " . $colors['font1'] . "; background:".$colors['table1'] . "; $font}
		.net2	{color: " . $colors['font1'] . "; background:".$colors['table2'] . "; $font}
		.net3	{color: " . $colors['font1'] . "; background:".$colors['table3'] . "; $font}
		a.hover  {color: " . $colors['font2'] . ";}
		-->
		</style>
	";

	return $style;
}

function DisplayMenu($option)
{
	$reportlist = array(
		"cmon" => WEB_LANG_PAGE_CMON,
		"route" => WEB_LANG_PAGE_ROUTE,
		"proto" => WEB_LANG_PAGE_PROTOCOL_STATS,
	);

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "100%");
	echo "
		<tr>
			<td align='center'>" . 
				WebDropDownHash("option", $option, $reportlist) . "&#160; " .
				WebButtonGenerate("GenerateReport") . "&#160; " .
				WEB_LANG_MENU_TIPS . "
			</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

?>
