<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/JNetTop.class.php");
require_once("../../api/File.class.php");
require_once(GlobalGetLanguageTemplate('jnettop.php'));

WebAuthenticate();

header('Content-Type: application/xml');

$thedate = strftime("%b %e %Y");
$thetime = strftime("%T %Z");

$entries = ParseToXML();

echo "<?xml version='1.0'?>
<networkactivity>
  <timestamp>$thedate $thetime</timestamp>\n$entries
</networkactivity>
";

function ParseToXML()
{
	$jnettop = new JNetTop();
	try {
		$activity = '';
		$fields = $jnettop->GetFields();
		$file = new File(JNetTop::FILE_DUMP);	
		if (!$file->Exists())
			return '';
		$lines = $file->GetContentsAsArray();
		foreach ($lines as $line) {
			if (eregi("Could not", $line))
				continue;
			$activity .= '<entry>';
			$parts = explode(',', $line);
			foreach ($parts as $id => $part)
				$activity .= '  <' . $fields[$id] . '>' . $part . '</' . $fields[$id] . '>';
			$activity .= '</entry>';
		}
		# Reset if activity has been logged
		if (strlen($activity))
			$jnettop->Init($_POST['interface'], $_POST['interval']);
		return $activity;
	} catch (Exception $e) {
		return "<error>" . $e->GetMessage() . "</error";
	}
}
// vim: ts=4
?>
