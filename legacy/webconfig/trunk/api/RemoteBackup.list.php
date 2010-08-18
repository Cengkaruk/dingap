<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2008 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Remote backup 'quick-pick' filesystem list.
 *
 * TODO: This is mostly a duplicate of the Antivirus.list.php file.
 * This should really be consolidated in to some generic array to at least
 * spare our translators from doing redundant work.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http: *www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2008, Point Clark Networks
 */

$RBS_QUICK_PICKS = array();
$RBS_QUICK_PICKS['rbs_qp_home'] = array(
	'path' => '/home',
	'text' => REMOTEBACKUP_LANG_QP_HOME,
	'type' => RBS_TYPE_FILEDIR,
	'enabled' => false
);
$RBS_QUICK_PICKS['rbs_qp_flexshare'] = array(
	'path' => '/var/flexshare',
	'text' => REMOTEBACKUP_LANG_QP_FLEXSHARE,
	'type' => RBS_TYPE_FILEDIR,
	'enabled' => false
);
$RBS_QUICK_PICKS['rbs_qp_www'] = array(
	'path' => '/var/www',
	'text' => REMOTEBACKUP_LANG_QP_WWW,
	'type' => RBS_TYPE_FILEDIR,
	'enabled' => false
);
$RBS_QUICK_PICKS['rbs_qp_ftp'] = array(
	'path' => '/var/ftp',
	'text' => REMOTEBACKUP_LANG_QP_FTP,
	'type' => RBS_TYPE_FILEDIR,
	'enabled' => false
);
$RBS_QUICK_PICKS['rbs_qp_shared'] = array(
	'path' => '/var/shared',
	'text' => REMOTEBACKUP_LANG_QP_SHARED,
	'type' => RBS_TYPE_FILEDIR,
	'enabled' => false
);
$RBS_QUICK_PICKS['rbs_qp_cyrus'] = array(
	'path' => null,
	'text' => REMOTEBACKUP_LANG_QP_CYRUS,
	'type' => RBS_TYPE_MAIL,
	'sub-type' => RBS_SUBTYPE_MAIL_CYRUSIMAP,
	'enabled' => false
);
$RBS_QUICK_PICKS['rbs_qp_mysql'] = array(
	'path' => null,
	'text' => REMOTEBACKUP_LANG_QP_MYSQL,
	'type' => RBS_TYPE_DATABASE,
	'sub-type' => RBS_SUBTYPE_DATABASE_MYSQL,
	'enabled' => false
);

require_once('BackupRestore.class.php');

$RBS_QUICK_PICKS['rbs_qp_sysconfig'] = array(
	'path' => BackupRestore::PATH_ARCHIVE,
	'text' => REMOTEBACKUP_LANG_QP_SYSCONFIG,
	'type' => RBS_TYPE_FILEDIR,
	'enabled' => false
);

// vi: syntax=php ts=4
?>
