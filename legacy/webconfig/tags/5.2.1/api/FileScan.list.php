<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * FileScan directory list.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

$AVDIRS = array();
$AVDIRS['/home'] = ANTIVIRUS_LANG_AVD_HOME;
$AVDIRS['/var/flexshare'] = ANTIVIRUS_LANG_AVD_FLEXSHARE;
$AVDIRS['/var/www'] = ANTIVIRUS_LANG_AVD_WWW;
$AVDIRS['/var/ftp'] = ANTIVIRUS_LANG_AVD_FTP;
$AVDIRS['/var/shared'] = ANTIVIRUS_LANG_AVD_SHARED;
$AVDIRS['/var/spool/squid'] = ANTIVIRUS_LANG_AVD_SQUID;
// TODO: Mail spool needs special handling
// $AVDIRS['/var/spool/imap'] = ANTIVIRUS_LANG_AVD_IMAP;

// vi: syntax=php ts=4
?>
