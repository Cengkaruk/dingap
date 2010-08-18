#!/usr/webconfig/bin/php -q
<?
require_once('/var/webconfig/api/RemoteBackup.class.php');

$rbs = new RemoteBackup();
$schedule = $rbs->GetBackupSchedule();
$rbs->SetBackupSchedule($schedule['window'],
	$schedule['daily'], $schedule['weekly'],
	$schedule['monthly'], $schedule['yearly']);

// vi: ts=4
?>
