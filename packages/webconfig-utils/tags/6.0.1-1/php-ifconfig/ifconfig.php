<html>
<head>
<title>ifconfig PHP Extension Test Routines</title>
</head>
<body>
<?
echo "<pre>";

if(!extension_loaded('ifconfig')) dl('ifconfig.' . PHP_SHLIB_SUFFIX);

$module = 'ifconfig';
$functions = get_extension_funcs($module);

echo "IFconfig extension:\n<hr>";

foreach($functions as $func) echo $func . "()\n";

echo "<hr>\n";

$handle = ifconfig_init();
ifconfig_debug($handle, false);

$interface = ifconfig_list($handle);

foreach($interface as $device)
{
/*
eth0      Link encap:Ethernet  HWaddr 00:02:B3:02:99:F7  
          inet addr:192.168.2.104  Bcast:192.168.2.255  Mask:255.255.255.0
          UP BROADCAST RUNNING MULTICAST  MTU:1500  Metric:1
          RX packets:8053 errors:0 dropped:0 overruns:0 frame:0
          TX packets:5768 errors:0 dropped:0 overruns:0 carrier:0
          collisions:18 txqueuelen:1000 
          RX bytes:669073 (653.3 Kb)  TX bytes:1122994 (1.0 Mb)
          Interrupt:14 Base address:0xecc0 Memory:f9fff000-f9fff038 
*/
	$ip = ifconfig_address($handle, $device);
	$hwaddress = ifconfig_hwaddress($handle, $device);
	$mtu = ifconfig_mtu($handle, $device);
	$metric = ifconfig_metric($handle, $device) + 1;
	$netmask = ifconfig_netmask($handle, $device);
	$broadcast = ifconfig_broadcast($handle, $device);
	$flags = ifconfig_flags($handle, $device);
	$speed = ifconfig_speed($handle, $device);

	if(($flags & IFF_LOOPBACK)) $encap = "Local Loopback";
	else if(($flags & IFF_POINTOPOINT)) $encap = "Point-to-Point Protocol";
	else if(($flags & IFF_NOARP) && !strlen($hwaddress)) $encap = "UNSPEC";
	else $encap = "Ethernet";

	printf("\n%-9s Link encap:%s  ", $device, $encap);

	if(strlen($hwaddress)) echo "HWaddr $hwaddress";

	printf("\n%-9s inet addr:%s", " ", $ip);

	if(strlen($broadcast)) echo "  Bcast:$broadcast";

	echo "  Mask:$netmask";
	
	printf("\n%-9s Flags:0x%08x  MTU:%d  Metric:%d\n", " ", $flags, $mtu, $metric);

	echo "  Speed:$speed";
/*
	if(!($flags & IFF_LOOPBACK))
	{
		$link = ifconfig_link($handle, $device);

		if($link)
		{
			$speed = ifconfig_speed($handle, $device);
			echo " speed $speed";
			echo " link $link";
		}
	}
*/
}

echo "</pre>";
?>
</body>
</html>
