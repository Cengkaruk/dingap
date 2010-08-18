<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008 Point Clark Networks.
// Created by: Michel Scherhage [techlab@dhd4all.com]
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Postfix.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

define('MAILQ_MSG_LIMIT', 25); // Number of email messages shown on each page

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-queue.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$postfix = new Postfix();

if (isset($_POST['Delete'])) {
	$id = isset($_POST['id']) ? $_POST['id'] : array();

	if (! is_array($id))
		$id = array($id);
	
	try {
		$postfix->DeleteQueuedMessages($id);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['FlushQueue'])) {
	try {
		$postfix->FlushQueue();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayQueue();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

function CalculateFileSize($bytes)
{
	$decimals = 1;
	  
	$units = array('1152921504606846976' => 'EB', // Exa Byte  10^18
				   '1125899906842624'	=> 'PB', // Peta Byte 10^15 
				   '1099511627776'	   => 'TB',
				   '1073741824'		  => 'GB',
				   '1048576'			 => 'MB',
				   '1024'				=> 'KB'
				   );
	  
	if($bytes <= 1024)
		return $bytes . " Bytes";
		  
	foreach($units as $base => $title)
		if(floor($bytes / $base) != 0)
			return number_format($bytes / $base, $decimals, ".", "'") . ' ' . $title;
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayQueue
//
///////////////////////////////////////////////////////////////////////////////

function DisplayQueue()
{
	global $id;
	global $postfix;

	try {
		$queue = $postfix->GetMailQueue();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	if ( !is_array($queue) || (strcmp($queue[0],"") == 0) || (strcmp($queue[0],"Mail queue is empty") == 0)) {
		WebDialogInfo(WEB_LANG_MAIL_QUEUE_EMPTY);
		return;
	}

	$c = 0;
	$trail = "";
	$content = "";

	foreach ($queue as $item) {
		$c++;

		if (strncmp($item,"--",2) == 0) {
			$item = str_replace("-", "", $item);
			$trail .= "<tr><td class='mytableheader' colspan='6'>$item</td>\n";
		} else {
			if (! isset($body[$c]))
				$body[$c] = "";

			$body[$c] .= "<tr>\n";
		
			$from   = array();
			$to	 = array();
			$date   = array();
			$size   = array();
			$queue  = array();
			$reason = array();
			
			$item = explode(" ", $item);
			$id = each($item);

			foreach($item as $part)
			{
				$part = explode("=", $part);
				switch ($part[0])
				{
				case 'from':   $from[]   = $part[1]; break;
				case 'to':	   $to[]	 = $part[1]; break;
				case 'date':   $date[]   = $part[1]; break;
				case 'size':   $size[]   = $part[1]; break;
				case 'queue':  $queue[]  = $part[1]; break;
				case 'reason': $reason[] = $part[1]; break;
				}
			}
		
			$reason = str_replace("_", " ", $reason);

			// ID + Date + Time
			$body[$c] .= "<td valign='top' nowrap><input id='id$c' type='checkbox' name='id[]' value='$id[1]'></td>\n";
			$body[$c] .= "<td valign='top' nowrap><b>$id[1]</b></td>\n";
			$body[$c] .= "<td valign='top' nowrap>";
			$body[$c] .= "&nbsp;";

			foreach($date as $s)
			{
				$s = str_replace("T", " / ", $s);
				$s = str_replace("Z", "",  $s);
				$body[$c] .= "$s<br>";
			}
			$body[$c] .= "</td>\n";

			//From
			$body[$c] .= "<td valign='top'>";
			foreach($from as $s) $body[$c] .= "&nbsp;$s<br>";
			$body[$c] .= "</td>\n";
			
			//To
			$body[$c] .= "<td valign='top'>";
			foreach($to as $s) $body[$c] .= "&nbsp;$s<br>";
			$body[$c] .= "</td>\n";

			//Size
			$body[$c] .= "<td valign='top' nowrap align='right'>";
			foreach($size as $s) $body[$c] .= "&nbsp;".CalculateFileSize($s)."&nbsp;<br>";
			$body[$c] .= "</td>\n";

			//Status
			$body[$c] .= "<td valign='top'>";
			if ($reason)
			{
				foreach($reason as $s) $body[$c] .= "&nbsp;$s<br>";
			}
			else $body[$c] .= WEB_LANG_PROCESSING."<br>";
			$body[$c] .= "</td>\n";
	
			$body[$c] .= "</tr>\n";
		}
	}

	// Get the pager input values
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
	$limit = MAILQ_MSG_LIMIT;
	$total = $c-1; 
	
	// Output messages for current page
	for($c=$page*$limit-$limit+1; $c < $page*$limit+1; $c++) {
		if (! isset($body[$c]))
			$body[$c] = "";

		$content .= $body[$c];
	}
  
	// Create pager
	if (ceil($total/$limit) > 1) {
		$nav = "";

		if ($page != 1)
			$nav .= "<a href='mail-queue.php?page=" . ($page - 1) . "'>" . WEBCONFIG_ICON_PREVIOUS . "</a> "; 
		  
		for ($i = 1; $i <= ceil($total/$limit); $i++) { 
			$nav .= " | "; 

			if ($i == $page)
				$nav .= "$i"; 
			else
				$nav .= "<a href='mail-queue.php?page=$i'>$i</a>"; 
		} 

		$nav .= " | "; 
	
		if ($page == ceil($total/$limit))
			$nav .= ""; 
		else
			$nav .= "<a href='mail-queue.php?page=" . ($page + 1) . "'>" . WEBCONFIG_ICON_NEXT . "</a>";
	
		$trail .= "<td class='mytableheader' align='right' nowrap>".$nav."</td></tr>\n";
	} else {
		$trail .= "<td class='mytableheader' align='right' nowrap>&nbsp;</td></tr>\n";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "100%");
	WebTableHeader(
			"|" . 
			POSTFIX_LANG_MAIL_ID . "|" .
			LOCALE_LANG_DATE . "|" .
			LOCALE_LANG_FROM . "|" .
			LOCALE_LANG_TO . "|" .
			POSTFIX_LANG_SIZE . "|".
			LOCALE_LANG_STATUS
	);
	echo $content;
	echo "
		<tr>
			<td colspan='7' align='center'>" .
				 WebButton('SelectAllButton', LOCALE_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK, 
					array('type' => 'button', 'onclick' => 'SelectAll()')) . 
				 WebButton('SelectNoneButton', LOCALE_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK, 
					array('type' => 'button', 'onclick' => 'SelectNone()')) . 
				 WebButton('FlushQueue', WEB_LANG_FLUSH, WEBCONFIG_ICON_CHECKMARK) .
				 WebButtonDelete("Delete") . "
			</td>
		</tr>
	";
	echo $trail;
	WebTableClose("100%");
	WebFormClose();
}
