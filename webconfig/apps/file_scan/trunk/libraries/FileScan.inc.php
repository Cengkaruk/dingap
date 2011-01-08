<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * FileScan preset directory list.
 *
 * @package ClearOS
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2006-2010 ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$AVDIRS = array();
$AVDIRS['/home'] = lang('file_scan_home');
$AVDIRS['/var/flexshare'] = lang('file_scan_flexshare');
$AVDIRS['/var/www'] = lang('file_scan_web');
$AVDIRS['/var/ftp'] = lang('file_scan_ftp');
$AVDIRS['/var/spool/squid'] = lang('file_scan_web_proxy_cache');
// TODO: Mail spool needs special handling
// $AVDIRS['/var/spool/imap'] = ...;

// vi: syntax=php ts=4
?>
