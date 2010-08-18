<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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

/**
 * SnortSam intrusion prevention reports.
 *
 * @package Reports
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once(COMMON_CORE_DIR . '/gui/Gui.class.php');
require_once(COMMON_CORE_DIR . '/gui/Report.class.php');
require_once(COMMON_CORE_DIR . '/api/SnortSam.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * SnortSam intrusion prevention reports.
 *
 * @package Reports
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class SnortSamReport extends Gui implements Report
{
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * SnortSam Report constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	}

	function GetFullReport($showactions)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetBlockList(1000, $showactions, true);
	}

	function GetDashboardSummary()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$snortsam = new SnortSam();
			$isrunning = $snortsam->GetRunningState();

			if (!$isrunning)
				return;
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		$this->GetBlockList(5, false, false);
	}

	/**
	 * Displays block list table.
	 *
	 * @access private
	 */

	function GetBlockList($maxrecords, $showactions, $showempty)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$snortsam = new SnortSam();
			$blocklist = $snortsam->GetBlockList();
		} catch (Exception $e) {
			// TODO: create CommonException 
		}

		$list = "";

		if ($blocklist) {
			$count = 0;
			foreach ($blocklist as $block) {
				// TODO: the DNS lookups can take too long (Ajax-ize)
				// $hostname = gethostbyaddr($block[blockedip]);
				$hostname = "";
				$d = ""; $h = 0; $m = 0;
				$s = $block['timestamp'] + $block['duration'] - time();

				if($s >= 86400) {
					$d = floor($s / 86400) . "d ";
					$s -= 86400;
				}

				if($s >= 3600) {
					$h = floor($s / 3600);
					$s -= $h * 3600;
				}

				if($s >= 60) {
					$m = floor($s / 60);
					$s -= $m * 60;
				}

				if ($_SESSION['system_online_help']) {
					$sid = "<a target='blank' href='" .  $_SESSION['system_online_help'] . 
						"/redirect/snort/sid=$block[sid]'>$block[sid]</a>";
				} else {
					$sid = $block['sid'];
				}

				$list .= "
				  <tr>
					<td>$sid</td>
					<td>" . $block['blockedip'] . "</td>
					<td>$hostname</td>
					<td>" . strftime("%x", $block['timestamp']) . "</td>
					<td>" . strftime("%X", $block['timestamp']) . "</td>
					<td>" . sprintf("%s%02d:%02d:%02d", $d, $h, $m, $s) . "</td>
				";

				if ($showactions) {
					$list .= "
					<td nowrap>" .
						WebButton("AddWhitelist[$block[blockedip]|$block[crc]]",
							SNORTSAM_LANG_WHITELIST, WEBCONFIG_ICON_ADD) .
						WebButtonDelete("DeleteBlock[$block[crc]]") . "</td>
					";
				}

				$list .= "</tr>";
				$count++;
				if ($count == $maxrecords) {
					$list .= "<tr><td align='center' colspan='7'>" . WebUrlJump("/admin/intrusion-prevention.php", LOCALE_LANG_CONTINUE) . "</td></tr>";
					break;
				}
			}
		}

		$reset = "";

		if (count($blocklist) == 0) {
			if ($showempty)
				$list = "<tr><td colspan='7' align='center'>" . REPORT_LANG_EMPTY_REPORT . "</td></tr>";
			else
				return;
		} else if ((count($blocklist) >= 2) && ($showactions)) {
			$reset = "
				<tr>
				  <td colspan='7' align='center' class='mytableheader'>" . SNORTSAM_LANG_CLEAR_BLOCK_LIST . " &#160; " .
					 WebButton("ResetBlockList", LOCALE_LANG_RESET, WEBCONFIG_ICON_CONTINUE) . "</td>
				</tr>
			";
		}

		if ($showactions)
			$extracolumn = "|" . LOCALE_LANG_ACTION;
		else
			$extracolumn = "";

		WebFormOpen($_SERVER['PHP_SELF'], "post");
		WebTableOpen(SNORTSAM_LANG_INTRUSION_PREVENTION . " - " . SNORTSAM_LANG_BLOCKLIST_ACTIVE, "100%");
		echo $reset;
		WebTableHeader(SNORTSAM_LANG_BLOCKLIST_SID . "|" . SNORTSAM_LANG_BLOCKLIST_IP . "||" .
			LOCALE_LANG_DATE . "|" . LOCALE_LANG_TIME . "|" . SNORTSAM_LANG_TIME_REMAINING . $extracolumn);
		echo $list;
		WebTableClose("100%");
		WebFormClose();
	}
}

// vim: syntax=php ts=4
?>
