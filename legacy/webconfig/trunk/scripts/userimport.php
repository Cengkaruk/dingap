#!/usr/webconfig/bin/php -q
<?
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks
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

require_once(dirname($_SERVER['argv'][0]) . '/userimport.inc.php');
require_once("/var/webconfig/api/UserImport.class.php");
require_once("/var/webconfig/api/Group.class.php");
require_once("/var/webconfig/api/File.class.php");
require_once "File/CSV/DataSource.php";

set_time_limit(0);

// Debug mode?
$debug = false;
$ph = popen('/usr/bin/tty', 'r');
list($tty) = chop(fgets($ph, 4096));
pclose($ph);
if ($tty != 'not a tty') $debug = true;

// Must be run as root
if (posix_getuid() != 0) {
	echo 'Must be run as superuser (root)';
	exit;
}


// Ensure we are the only instance running
if (file_exists(IMPORT_LOCKFILE)) {
	$fh = @fopen(IMPORT_LOCKFILE, 'r');
	list($pid) = fscanf($fh, '%d');

	// Perhaps this is a stale lock file?
	if (!file_exists("/proc/$pid")) {
		// Yes, the process 'appears' to no longer be running...
		@unlink(IMPORT_LOCKFILE);
		ResetState($import_state);
		$fh = @fopen(IMPORT_STATE, 'a+');
		if (SerializeState($fh, $import_state) == false) {
			echo 'State serialization failure';
			exit;
		}
		fclose($fh);
	} else {
		// Only one instance can run at a time
		echo 'Import is already in progress';
		exit;
	}

	fclose($fh);
} else {
	ResetState($import_state);
	$fh = @fopen(IMPORT_STATE, 'a+');
	if (SerializeState($fh, $import_state) == false) {
		echo 'State serialization failure';
		exit;
	}
	fclose($fh);
	// Grab the lock ASAP...
	touch(IMPORT_LOCKFILE);
}

// Format a log/error entry
function add_log($type, $msg)
{
	global $import_state;
	if (strlen($msg) > 55)
		$msg = substr($msg, 0, strpos($msg, " ", 55)) . "<br>" . str_pad('', 12) . substr($msg, strpos($msg, " ", 55, strlen($msg) - 1));
	if ($type == IMPORT_LOG_INFO) {
		$import_state['logs'][] = str_pad(USERIMPORT_LANG_INFO . ':', 12) . $msg;
	} elseif ($type == IMPORT_LOG_WARNING) {
		$import_state['logs'][] = str_pad(USERIMPORT_LANG_WARNING . ':', 12) . $msg;
	} elseif ($type == IMPORT_LOG_ERROR) {
		$import_state['errors'][] = str_pad(USERIMPORT_LANG_ERROR . ':', 12) . $msg;
	}
}

// Register a shutdown function so we can do some clean-up
function import_shutdown()
{
	@unlink(IMPORT_LOCKFILE);
}

register_shutdown_function('import_shutdown');

// Lock state file, write serialized state
function serialize_state($fh)
{
	global $import_state;

	if (SerializeState($fh, $import_state) == false) {
		echo 'State serialization failure';
		exit;
	}
}

// Lock state file, read and unserialized status
function unserialize_state($fh)
{
	global $import_state;

	if (UnserializeState($fh, $import_state) === false) {
		echo 'State unserialization failure';
		exit;
	}
}

// Save our PID to the lock file
$fh = @fopen(IMPORT_LOCKFILE, 'w');

fprintf($fh, "%d\n", posix_getpid());
fclose($fh);


// Open state file.  This is where we import count and status
$fh = @fopen(IMPORT_STATE, 'a+');
chown(IMPORT_STATE, 'webconfig');
chgrp(IMPORT_STATE, 'webconfig');
chmod(IMPORT_STATE, 0750);
unserialize_state($fh);
$import_state['timestamp'] = time();
serialize_state($fh);

$import_state['count'] = 0;

// Give browser some time to load
sleep(3);

$userimport = new UserImport();
try {
	$userimport->SetCsvFile(FILE_IMPORT);
	$total_users = $userimport->GetNumberOfRecords();

	$import_state['total'] = $total_users;

	serialize_state($fh);

	$csv = new File_CSV_DataSource();
	# Define our delimiter and escape types
	$delimiters = Array(",",";",":"," ","|","\t");
	$escapes = Array("\"","'");
	$settings = array(
		'delimiter' => ',',
		'eol' => "\n",
		'length' => 999999,
		'escape' => '"'
	);

	$parsed = false;
	foreach ($delimiters as $delimiter) {
		if ($parsed)
			break;
		foreach ($escapes as $escape) {
			$settings['delimiter'] = $delimiter;
			$settings['escape'] = $escape;
			$csv->settings($settings);
			$csv->load(FILE_IMPORT);
			if ($csv->hasColumn('username')) {
				$headers = $csv->getHeaders();
				if (eregi("^'username.*", $headers[0])) {
					continue;
				} else {
					$parsed = true;
					break;
				}
			}
		}
	}

	for ($counter = 0; $counter < $csv->countRows(); $counter++) {

		// Sync our state file, may have been modified externally...
		unserialize_state($fh);

		try {
			$userinfo = array_combine($csv->getHeaders(), $csv->GetRow($counter));

			// Get group information and then unset in userinfo array
			$groupfield = $userinfo['groups'];
			unset($userinfo['groups']);

			// Validate password here because user class allows null
			if (!isset($userinfo['password']) || $userinfo['password'] == '') {
				add_log(IMPORT_LOG_ERROR, $userinfo['username'] . ' - ' . USERIMPORT_LANG_ERRMSG_PASSWORD);
				continue;
			}
				
			$userimport->AddUser($userinfo);
			add_log(IMPORT_LOG_INFO, $userinfo['username'] . ' ' . USERIMPORT_LANG_ADDED . '...');

			$groups = explode(',', $groupfield);

			// Check for empty group
			if(!isset($groups[0]) || $groups[0] == '')
				$groups = Array();

			// Tack on 'all users' group and Windows 'Domain Users'
			$groups[] = Group::CONSTANT_ALL_USERS_GROUP;
			$groups[] = Group::CONSTANT_ALL_WINDOWS_USERS_GROUP;

			foreach ($groups as $group) {
				try {
					$userimport->AddUserToGroup($userinfo['username'], trim(strtolower($group)));
				} catch (GroupNotFoundException $e) {
					add_log(IMPORT_LOG_WARNING, $userinfo['username'] . ' - ' . $e->GetMessage());
					serialize_state($fh);
					continue;
				} catch (Exception $e) {
					add_log(IMPORT_LOG_ERROR, $userinfo['username'] . ' - ' . $e->GetMessage());
					serialize_state($fh);
					continue;
				}
			}
	
		} catch (UserAlreadyExistsException $e) {
			add_log(IMPORT_LOG_WARNING, $userinfo['username'] . ' - ' . USERIMPORT_LANG_DUPLICATE_USER);
		} catch (Exception $e) {
			add_log(IMPORT_LOG_ERROR, $userinfo['username'] . ' - ' . $e->GetMessage());
		}

		$import_state['count']++;

		serialize_state($fh);

		if ($debug) printf("%.00f: %s\n", $import_state['count'] * 100 / $import_state['total'], ($counter + 1));
	}
} catch (Exception $e) {
	add_log(IMPORT_LOG_ERROR, $e->GetMessage());
}

clearstatcache();


serialize_state($fh);

fclose($fh);

// vi: ts=4 syntax=php
?>
