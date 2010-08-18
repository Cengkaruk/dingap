<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2004-2006 Point Clark Networks.
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

///////////////////////////////////////////////////////////////////////////////
//
// A simple tool to grab images outside the htdocs root.  To keep things 
// secure, the directory must be set via the getimage_path session variable.
// The image can bet set through GET or POST.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

// Bail on missing directory or source
if (! (isset($_SESSION['getimage_path']) && isset($_REQUEST['source'])))
	exit;

// Bail on filenames with a slash
if (preg_match("/\//", $_REQUEST['source']))
	exit;

$filename = $_SESSION['getimage_path'] . "/" . $_REQUEST['source'];

// Bail on unsupported extension
$allowed_extensions['gif'] = true;
$allowed_extensions['png'] = true;
$allowed_extensions['jpg'] = true;
$extension = preg_replace("/.*\./", "", $filename);

if (! in_array($extension, $allowed_extensions))
	exit();

if (!file_exists($filename))
	exit();

// Show picture
header("Content-type: image/$extension");
header("Content-Disposition: inline; filename=\"{$filename}\"");
header("Content-length: ".(string)(filesize($filename)));
@readfile($filename);

?>
