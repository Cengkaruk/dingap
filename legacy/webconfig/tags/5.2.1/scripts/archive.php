#!/usr/webconfig/bin/php -q
<?
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks
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

require_once(dirname($_SERVER['argv'][0]) . '/archive.inc.php');
require_once("/var/webconfig/api/Archive.class.php");
require_once("/var/webconfig/api/File.class.php");
require_once("/var/webconfig/common/Logger.class.php");

// Fatal error
function fatal_error($code, $error, $filename = null, $line = 0)
{
	if ($filename != null) printf('%s:%d: ', basename($filename), $line);
	echo("$error\n");
	Logger::Syslog("archive.php (Script)", $error . ' (' . $code .')');
	if ($fh)
		fclose($fh);
	exit($code);
}

set_time_limit(0);

if (isset($_SERVER['argv'][1]))
	$filename = $_SERVER['argv'][1];

# Tar or Untar
if (isset($_SERVER['argv'][2]))
	$option = $_SERVER['argv'][2];

# Encrypt password
if (isset($_SERVER['argv'][3]) && $_SERVER['argv'][3] != '')
	$encrypt = $_SERVER['argv'][3];
else
	$encrypt = null;

# Purge database
if (isset($_SERVER['argv'][4]) && $_SERVER['argv'][4] == 'true')
	$purge = true;
else
	$purge = false;

// Register custom error handler
set_error_handler('fatal_error');

// Debug mode?
$debug = false;
$ph = popen('/usr/bin/tty', 'r');
list($tty) = chop(fgets($ph, 4096));
pclose($ph);
if ($tty != 'not a tty') $debug = true;

if (!isset($_SERVER['argv'][1]) || !isset($_SERVER['argv'][2]))
	fatal_error(1, 'Invalid arguments - useage: archive.php <filename> <c,x> <optional encryption password>');

// Must be run as root
if (posix_getuid() != 0) {
	fatal_error(1, 'Must be run as superuser (root)');
}

// Ensure we are the only instance running
if (file_exists(ARCHIVE_LOCKFILE)) {
	if ( ($fh = @fopen(ARCHIVE_LOCKFILE, 'r')) === false)
		fatal_error(1, 'Reading lock file ' . ${php_errormsg});
	list($pid) = fscanf($fh, '%d');

	// Perhaps this is a stale lock file?
	if (!file_exists("/proc/$pid")) {
		// Yes, the process 'appears' to no longer be running...
		@unlink(ARCHIVE_LOCKFILE);
		ResetState($archive_state);
		$fh = @fopen(ARCHIVE_STATE, 'a+');
		if (SerializeState($fh, $archive_state) == false)
			fatal_error(1, 'State serialization failure');
	} else {
		// Only one instance can run at a time
		fatal_error(1, 'Archive is already in progress');
	}

	fclose($fh);
} else {
	ResetState($archive_state);
	$fh = @fopen(ARCHIVE_STATE, 'a+');
	if (SerializeState($fh, $archive_state) == false)
		fatal_error(1, 'State serialization failure');
	fclose($fh);
	// Grab the lock ASAP...
	touch(ARCHIVE_LOCKFILE);
}

// Register a shutdown function so we can do some clean-up
function archive_shutdown()
{
	@unlink(ARCHIVE_LOCKFILE);
}

register_shutdown_function('archive_shutdown');

// Lock state file, write serialized state
function serialize_state($fh)
{
	global $archive_state;

	if (SerializeState($fh, $archive_state) == false)
		fatal_error(1, 'State serialization failure');
}

// Lock state file, read and unserialized status
function unserialize_state($fh)
{
	global $archive_state;

	if (UnserializeState($fh, $archive_state) === false)
		fatal_error(1, 'State unserialization failure');
}

// Save our PID to the lock file
$fh = @fopen(ARCHIVE_LOCKFILE, 'w');

fprintf($fh, "%d\n", posix_getpid());
fclose($fh);


// Open state file.  This is where we dump database and tar status
$fh = @fopen(ARCHIVE_STATE, 'a+');
chown(ARCHIVE_STATE, 'webconfig');
chgrp(ARCHIVE_STATE, 'webconfig');
chmod(ARCHIVE_STATE, 0750);
unserialize_state($fh);
$archive_state['timestamp'] = time();
serialize_state($fh);

$safe_dir = escapeshellarg(ARCHIVE_CURRENT);

$archive_state['count'] = 0;

$archive = new Archive();
$db_pass = $archive->GetDatabasePassword();

if ($debug) echo("Running archive script: $filename $option '$encrypt' " . ($purge ? 'purge' : 'no purge') . "\n");
if ($option == 'c') {
	# Dump database
	if ( ($ph = popen(ARCHIVE_MYSQLDUMP . " -uarchive -p$db_pass -t archive_current -r " . ARCHIVE_CURRENT . "/archive.dmp 2>&1", 'r')) === false)
		fatal_error(1, "MySQL dump failed:" . ${php_errormsg});
	$buffer = '';
	while (!feof ($ph)) 
   		$buffer .= fgets ($ph, 1024);
	$rc = pclose($ph);
	if ($rc) fatal_error($rc, "MySQL dump failed:" . $buffer);

	// First, find out how many files we'll be scanning so we can have a
	// nifty progress bar in webconfig
	if ($debug) echo("Counting files: $safe_dir\n");
	$ph = popen("find $safe_dir -type f | wc -l", 'r');
	$archive_state['total'] = chop(fgets($ph));

	if (pclose($ph)) fatal_error(1, "Unable to determine file count in: $safe_dir");
	if ($encrypt)
		$archive_state['total'] = $archive_state['total'] + 10; // Add arbirtary 10 to files...when encryption req'd
	if ($debug) echo("File count: " . $archive_state['total'] . "\n");
} else {
	// Check file extension, and decrypt if necessary
	if ($encrypt) {
		$archive_state['total'] = 10; // Add arbirtary 10 to files...when decryption req'd
		if ($debug) echo("Decrypting $filename\n");
		// Remove .enc
		$filename = eregi_replace(".enc$", "", $filename);
		if ( ($ph = popen(ARCHIVE_OPENSSL . " enc -d -aes-256-cbc -in  " . ARCHIVE_FLEXSHARE . "/$filename.enc -out " . ARCHIVE_FLEXSHARE . "/$filename -pass pass:" . $encrypt . " 2>/dev/null", 'r')) === false)
			fatal_error(1, "Decrypt tar failed:" . ${php_errormsg});
		$buffer = '';
		while (!feof ($ph)) 
			$buffer .= fgets ($ph, 1024);
		$rc = pclose($ph);
		if ($rc) fatal_error($rc, "Decrypting tar file failed:" . $buffer);
		// Add 10 'count' points
		$archive_state['count'] = $archive_state['count'] + 10;
		$ph = popen(ARCHIVE_LN . " -s " . ARCHIVE_FLEXSHARE . "/$filename " . ARCHIVE_LINKS . "/ 2>/dev/null", 'r');
		$rc = pclose($ph);
	}
	if ($debug) echo("Counting files in tar file...\n");
	$ph = popen(ARCHIVE_TAR . " -tzvf " . ARCHIVE_LINKS . "/$filename | wc -l 2>/dev/null", 'r');
	$archive_state['total'] = $archive_state['total'] + chop(fgets($ph));
	$archive_state['total'] = $archive_state['total'] + 10; // Add arbirtary 10 for mysql import
	if ($debug) echo("File count: " . $archive_state['total'] . "\n");
	$rc = pclose($ph);
}

serialize_state($fh);

if ($option == 'c') {
	// Run tar...
	if ($debug) echo("Tar'ing directory: $safe_dir\n");
	if ( ($ph = popen("cd $safe_dir && find ./ -type f > /tmp/archive.list 2>&1", 'r')) === false)
		fatal_error(1, "Create file list failed:" . ${php_errormsg});
	 pclose($ph);
	echo "cd $safe_dir && " . ARCHIVE_TAR . " -czvf " . ARCHIVE_FLEXSHARE . "/$filename -T /tmp/archive.list";
	if ( ($ph = popen("cd $safe_dir && " . ARCHIVE_TAR . " -czvf " . ARCHIVE_FLEXSHARE . "/$filename -T /tmp/archive.list 2>&1", 'r')) === false)
		fatal_error(1, "Tar failed:" . ${php_errormsg});
} else {
	// Run tar...
	if ($debug) echo("UnTar'ing file $filename\n");
	if ( ($ph = popen("cd " . ARCHIVE_SEARCH . " && " . ARCHIVE_TAR . " -xzvf " . ARCHIVE_LINKS . "/$filename 2>&1", 'r')) === false)
		fatal_error(1, "UnTar failed:" . ${php_errormsg});
}

while (!feof($ph)) {
	$buffer = chop(fgets($ph, 4096));
	if (strlen($buffer) == 0) break;

	// Sync our state file, may have been modified externally...
	unserialize_state($fh);

	$archive_state['count']++;

	serialize_state($fh);

	if ($debug) printf("%.02f: %s\n", $archive_state['count'] * 100 / $archive_state['total'], $buffer);
}

pclose($ph);

if ($option == 'c') {
	if ($encrypt) {
		//Arbitrary addition of count
		$archive_state['count'] = $archive_state['count'] + 10;
		if ( ($ph = popen(ARCHIVE_OPENSSL . " enc -aes-256-cbc -in  " . ARCHIVE_FLEXSHARE . "/$filename -out " . ARCHIVE_FLEXSHARE . "/$filename.enc -pass pass:" . $encrypt . " 2>&1", 'r')) === false)
			fatal_error(1, "Encrypt tar failed:" . ${php_errormsg});
		$filename_orig = $filename;
		$filename = $filename . ".enc";
		$buffer = '';
		while (!feof ($ph)) 
			$buffer .= fgets ($ph, 1024);
		$rc = pclose($ph);
		if ($rc) fatal_error($rc, "Encrypting tar file failed:" . $buffer);
	}
	// Create sym link
	$ph = popen(ARCHIVE_LN . " -s " . ARCHIVE_FLEXSHARE . "/$filename " . ARCHIVE_LINKS . "/ 2>/dev/null", 'r');
	pclose($ph);
	if ($purge) {
		if ($debug) echo("Deleting archive.dmp\n");
		if ( ($ph = popen("/bin/rm " . ARCHIVE_CURRENT . "/archive.dmp 2>&1", 'r')) === false)
			fatal_error(1, "Deleting archive.dmp failed:" . ${php_errormsg});
		$buffer = '';
		while (!feof ($ph)) 
			$buffer .= fgets ($ph, 1024);
		$rc = pclose($ph);
		if ($rc) fatal_error($rc, "Deleting archive.dmp file failed:" . $buffer);
		if ($encrypt) {
			if ($debug) echo("Deleting non-encrypted tar\n");
			$ph = popen("/bin/rm " . ARCHIVE_FLEXSHARE . "/$filename_orig 2>&1", 'r');
			$buffer = '';
			while (!feof ($ph)) 
				$buffer .= fgets ($ph, 1024);
			$rc = pclose($ph);
			if ($rc) fatal_error($rc, "Deleting non-encrypted tar file failed:" . $buffer);
		}
		clearstatcache();
		chmod(ARCHIVE_FLEXSHARE . "/$filename", 0640);
		$archive->ResetCurrent();
		$archive->SetLastSuccessfulArchive();
	}
} else if ($option == 'x') {
	if ( ($ph = popen(ARCHIVE_MYSQL . " -uarchive -p$db_pass archive_search < " . ARCHIVE_SEARCH . "/archive.dmp 2>&1", 'r')) === false)
		fatal_error(1, "Importing archive.dmp failed:" . ${php_errormsg});
	$buffer = '';
	while (!feof ($ph)) 
		$buffer .= fgets ($ph, 1024);
	$rc = pclose($ph);
	if ($rc) fatal_error($rc, "Importing archive failed:" . $buffer);
	//Arbitrary addition of count
	$archive_state['count'] = $archive_state['count'] + 10;
	if ($debug) printf("%.02f: %s\n", $archive_state['count'] * 100 / $archive_state['total'], $buffer);
	if ($debug) echo("Deleting archive.dmp\n");
	$ph = popen("/bin/rm " . ARCHIVE_SEARCH . "/archive.dmp 2>/dev/null", 'r');
	$rc = pclose($ph);
	if ($encrypt) {
		if ($debug) echo("Deleting link to non-encrypted tar\n");
		if ( ($ph = popen("/bin/rm " . ARCHIVE_LINKS . "/$filename 2>&1", 'r')) === false)
			fatal_error(1, "Deleting link to non-encrypted tar failed:" . ${php_errormsg});
		$buffer = '';
		while (!feof ($ph)) 
			$buffer .= fgets ($ph, 1024);
		$rc = pclose($ph);
		if ($rc) fatal_error($rc, "Deleting link to non-encrypted tar failed:" . $buffer);
		if ($debug) echo("Deleting non-encrypted tar\n");
		$ph = popen("/bin/rm " . ARCHIVE_FLEXSHARE . "/$filename 2>&1", 'r');
		$buffer = '';
		while (!feof ($ph)) 
			$buffer .= fgets ($ph, 1024);
		$rc = pclose($ph);
		if ($rc) fatal_error($rc, "Deleting non-encrypted tar failed:" . $buffer);
	}
}


serialize_state($fh);

sleep(5);

fclose($fh);

// vi: ts=4 syntax=php
?>
