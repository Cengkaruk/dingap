<?php

$info = '0|0|0';
try {
	require_once('/var/webconfig/api/DynamicDns.class.php');
	$dynamic_dns = new DynamicDns();
	$info = $dynamic_dns->GetInfo();
	echo sprintf("%s|%s|%s\n", $info['ip'], $info['lastupdate'], $info['domain']);
	exit;
} catch (Exception $e) { }
echo "$info\n";
exit;

// vi: ts=4
?>
