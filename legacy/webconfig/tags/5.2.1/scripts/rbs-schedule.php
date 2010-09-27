#!/usr/webconfig/bin/php -q
<?php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2008 Point Clark Networks
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

// Require service class
require_once(dirname($_SERVER['argv'][0]) . '/rbs.inc.php');
require_once(dirname($_SERVER['argv'][0]) . '/../api/Mailer.class.php');
require_once(dirname($_SERVER['argv'][0]) . '/../api/Hostname.class.php');
require_once(dirname($_SERVER['argv'][0]) . '/../api/RemoteBackup.class.php');
require_once(GlobalGetLanguageTemplate(dirname($_SERVER['argv'][0]) .
	'/../htdocs/admin/remote-server-backup.php'));

set_time_limit(0);

// Set our time zone
$tz = 'UTC';
$ph = popen('/bin/date \'+%Z\'', 'r');
if (is_resource($ph)) {
	$buffer = chop(fgets($ph, 4096));
	if (pclose($ph) == 0) $tz = $buffer;
}
date_default_timezone_set($tz);

// Create an RBS client
$rbs = new RemoteBackupService(basename($_SERVER['argv'][0]), false, false, false);

// Set logging options
$log_options['stderr'] = false;
$log_options['syslog'] =
	array('facility' => RemoteBackupService::SYSLOG_FACILITY);
$rbs->LogOptions($log_options);
$rbs->SetDebug(true);

// Register custom error handler
set_error_handler('ErrorHandler');

// Register custom exception handler
set_exception_handler('ExceptionHandler');

try {
	// Lock instance
	$rbs->LockInstance();

	// Ensure backup client program exists
	$rbs_client = dirname($_SERVER['argv'][0]) . '/rbs-client.php';
	if (!file_exists($rbs_client)) {
		$rbs->LogMessage('RBS client script missing: ' . $rbs_client, LOG_ERR);
		exit(1);
	}

	// Load and validate configuration
	$config = $rbs->LoadConfiguration();

	$rbs_client_flags = '';
	if (array_key_exists('mode', $config) && isset($config['mode'])) {
		if ($config['mode']) $rbs_client_flags .= ' -T';
	}

	if (!array_key_exists('auto-backup', $config) || !$config['auto-backup']) {
		$rbs->LogMessage('Auto-backup schedule is not enabled.', LOG_WARNING);
		exit(0);
	}

	if (!array_key_exists('auto-backup-schedule', $config) || !isset($config['auto-backup-schedule'])) {
		$rbs->LogMessage('Auto-backup schedule is not set.', LOG_WARNING);
		exit(0);
	}

	$schedule = unserialize(base64_decode($config['auto-backup-schedule']));
	if ($schedule === false) {
		$rbs->LogMessage('Corrupt auto-backup schedule.', LOG_ERR);
		$rbs->LogMessage($config['auto-backup-schedule'], LOG_DEBUG);
		exit(1);
	}

	// Check if we're within the set 'start window'...
	$hour = strftime('%H');
	if ($hour < $schedule['window'] * 2 || $hour > $schedule['window'] * 2 + 1) {
		$rbs->LogMessage('Nothing to do at this time: ' .
			sprintf('%d < %d or %d > %d', $hour, $schedule['window'] * 2,
			$hour, $schedule['window'] * 2 + 1), LOG_DEBUG);
		exit(0);
	}

	// Pick a random minute to start within the remaining window
	$fh = fopen('/dev/urandom', 'r');
	if (is_resource($fh)) {
		$seed = unpack('iint', fread($fh, 4));
		srand($seed['int']);
		fclose($fh);
	}
	$minutes = (($schedule['window'] * 2 + 2) - $hour) * 60 - strftime('%M');
	$sleep_time = rand() % $minutes;
	$rbs->LogMessage(sprintf('Remaining window: %d minutes, random start @ %d minutes.',
		$minutes, $sleep_time), LOG_DEBUG);

	$rbs->LogMessage("Sleeping for $sleep_time minutes...");
	sleep($sleep_time * 60);

	$rbs->LogMessage('Starting scheduled backup.');

	// Run delete snapshot first, flush the queue
	passthru($rbs_client . $rbs_client_flags . ' -D', $rc);

	// Run backup script...
	$rc = -1;
	$result = null;
	$ph = popen($rbs_client . $rbs_client_flags . ' 2>&1', 'r');
	if (is_resource($ph)) {
		$result = stream_get_contents($ph);
		$rc = pclose($ph);
	}
	$rbs->LogMessage(sprintf('Backup %s (exit: %d)',
		$rc == 0 ? 'successful' : 'failed', $rc));
	switch ($rc) {
	case -1:
		SendEmailOnError(WEB_LANG_EMAIL_BODY_ERROR . '.');
		break;
	case 0:
		SendEmailSummary();
		break;
	default:
		SendEmailOnError($result);
		break;
	}
	exit($rc);
} catch (Exception $e) {
	$rbs->LogMessage(sprintf('[%s] %s',
		$e->getCode(), $e->getMessage()), LOG_ERR);
	$body = WEB_LANG_EMAIL_BODY_ERROR .
		".\n\nException occured:";
	$body .= sprintf("\nCode: %s, Message: %s",
		$e->getCode(), $e->getMessage());
	SendEmailOnError($body);
	exit(1);
}

function GetRecipient()
{
	global $config;

	if (!array_key_exists('email-notify-address', $config)) return false;
	$email = trim($config['email-notify-address'], " \t\n\r\0\x0B'\"");
	if (!strlen($email)) return false;
	return $email;
}

function SendEmailOnError($body)
{
	global $config;

	if (!is_array($config)) return;
	if (!array_key_exists('email-notify-error', $config) ||
		$config['email-notify-error'] != '1') return;
	if (GetRecipient() === false) return;

	$rbs = new RemoteBackup();
	$error_code = $rbs->GetError();
	$error_text = $rbs->TranslateExceptionId($error_code);

	SendEmailNotification(WEB_LANG_EMAIL_SUBJECT_ERROR,
		"Last Error: $error_text [$error_code]\n\n$body");
}

function TimeDuration($tm_begin, $tm_end)
{
	$seconds = $tm_end - $tm_begin;
	if ($seconds <= 0) return '(00:00)';
	$hours = floor($seconds / (60 * 60));
	if ($hours > 0) $seconds -= ($hours * (60 * 60));
	$minutes = floor($seconds / 60);
	return sprintf('(%02u:%02u)', $hours, $minutes);
}

function SendEmailSummary()
{
	global $config;

	if (!array_key_exists('email-notify-summary', $config) ||
		$config['email-notify-summary'] != '1') return;
	if (GetRecipient() === false) return;

	$rbs = new RemoteBackup();
	$history = $rbs->GetSessionHistory();
	$last_backup = WEB_LANG_NO_HISTORY;
	$last_backup_result = REMOTEBACKUP_LANG_STATUS_UNKNOWN;
	$storage_used = 0;
	$storage_capacity = 0;

	if (count($history)) {
		$last_backup_entry = null;
		foreach ($history as $state) {
			if ($state['mode'] != RBS_MODE_BACKUP) continue;
			if ($last_backup_entry == null)
				$last_backup_entry = $state;
			if ($storage_capacity == 0 &&
				strpos($state['usage_stats'], ':') !== false)
				list($storage_used, $storage_capacity) = explode(':',
					$state['usage_stats']);
			if ($last_backup_entry != null && $storage_capacity != 0)
				break;
		}
		if ($last_backup_entry != null) {
			$last_backup = sprintf('%s %s',
				$rbs->LocaleTime($last_backup_entry['tm_started']),
				!strcmp($last_backup_entry['error_code'], '0') ?
					TimeDuration($last_backup_entry['tm_started'],
					$last_backup_entry['tm_completed']) : '; ' .  WEB_LANG_FAILED . '!');
			$last_backup_result = $rbs->TranslateStatusCode($last_backup_entry['status_code']);
			if (strcmp($last_backup_entry['error_code'], '0'))
				$last_backup_result .= '; ' . $rbs->TranslateExceptionId($last_backup_entry['error_code']);
		}
	}
	
	if ($storage_used == 0)
		$storage_used_text = '0 ' . LOCALE_LANG_MEGABYTES;
	else if($storage_capacity != 0)
		$storage_used_text = sprintf('%u %s (%.02f%%)',
			$storage_used / 1024, LOCALE_LANG_MEGABYTES,
			$storage_used * 100 / $storage_capacity);
	if ($storage_capacity == 0) $storage_remaining = WEB_LANG_NONE;
	else $storage_remaining = sprintf('%u %s (%u %s %s)',
		($storage_capacity - $storage_used) / 1024, LOCALE_LANG_MEGABYTES,
		$storage_capacity / 1024, LOCALE_LANG_MEGABYTES, WEB_LANG_TOTAL);

	$body = sprintf("%s: %s\n%s: %s\n%s: %s\n%s: %s\n\n",
		WEB_LANG_LAST_BACKUP, $last_backup,
		WEB_LANG_LAST_BACKUP_RESULT, $last_backup_result,
		WEB_LANG_STORAGE_USED, $storage_used_text,
		WEB_LANG_STORAGE_CAPACITY, $storage_remaining);

	$snapshots = $rbs->GetBackupHistory(true);

	foreach ($snapshots as $subdir => $entries) {
		if (!count($entries)) continue;
		$name = WEB_LANG_LEGACY;
		switch ($subdir) {
		case 'daily':
			$name = LOCALE_LANG_DAILY;
			break;
		case 'weekly':
			$name = LOCALE_LANG_WEEKLY;
			break;
		case 'monthly':
			$name = LOCALE_LANG_MONTHLY;
			break;
		case 'yearly':
			$name = LOCALE_LANG_YEARLY;
			break;
		}
		$body .= "\n$name\n";
		foreach ($entries as $snapshot => $size_kb) {
			if (strcmp($subdir, 'legacy'))
				$path = "$subdir/$snapshot";
			else
				$path = $snapshot;

			$delta = 0;
			if ($size_kb['delta'] > 0) $delta = $size_kb['delta'] / 1024;
			$links = 0;
			if ($size_kb['links'] > 0) $links = $size_kb['links'] / 1024;

			$body .= sprintf("%s: %s: %.02f %s, %s: %.02f %s\n",
				$rbs->LocaleTime($snapshot),
				WEB_LANG_DELTA, $delta, LOCALE_LANG_MEGABYTES,
				WEB_LANG_LINKS, $links, LOCALE_LANG_MEGABYTES);
		}
	}

	SendEmailNotification(WEB_LANG_EMAIL_SUBJECT_SUMMARY, $body);
}

function SendEmailNotification($subject, $body)
{
	global $rbs;
	global $config;

	$mailer = new Mailer();
	$hostname = new Hostname();
	$mailer->SetSubject($subject . ' - ' . $hostname->Get());
	$mailer->SetBody($body);
	$mailer->AddRecipient(GetRecipient());
	$sender = $mailer->GetSender();
	if (!strlen($sender)) $mailer->SetSender('root@' . $hostname->Get());
	try {
		$mailer->Send();
	} catch (Exception $e) {
		$rbs->LogMessage(sprintf('[%s] %s',
			$e->getCode(), $e->getMessage()), LOG_ERR);
	}
}

// vi: ts=4
?>

