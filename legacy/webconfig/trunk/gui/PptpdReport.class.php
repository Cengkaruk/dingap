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
 * PPTP VPN reports.
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
require_once(COMMON_CORE_DIR . '/api/Pptpd.class.php');
require_once(COMMON_CORE_DIR . '/api/Iface.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * PPTP VPN reports.
 *
 * @package Reports
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class PptpdReport extends Gui implements Report
{
	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * PptpdReport constructor.
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

		$this->DisplayActiveList(true);
	}

	function GetDashboardSummary()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->DisplayActiveList(false);
	}

	function DisplayActiveList($showempty)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$pptpd = new Pptpd();
		$iface = new Iface("notused"); // Just for locale

		$ethlist = array();

		try {
			$ethlist = $pptpd->GetActiveList();
			$stats = $pptpd->GetInterfaceStatistics();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		$connected = "";

		foreach ($ethlist as $eth) {
			$name = $eth['name'];

			if (isset($stats[$name]['sent'])) {
				if ($stats[$name]['sent'] > (1024*1024))
					$sent_out = round($stats[$name]['sent']/(1024*1024)) . " " . LOCALE_LANG_MEGABYTES;
				else if ($stats[$name]['sent'] > (1024))
					$sent_out = round($stats[$name]['sent']/(1024)) . " " . LOCALE_LANG_KILOBYTES;
				else
					$sent_out = $stats[$name]['sent'] . " " . LOCALE_LANG_BYTES;
			} else {
				$sent_out = 0;
			}

			if (isset($stats[$name]['received'])) {
				if ($stats[$name]['received'] > (1024*1024))
					$received_out = round($stats[$name]['received']/(1024*1024)) . " " . LOCALE_LANG_MEGABYTES;
				else if ($stats[$name]['received'] > (1024))
					$received_out = round($stats[$name]['received']/(1024)) . " " . LOCALE_LANG_KILOBYTES;
				else
					$received_out = $stats[$name]['received'] . " " . LOCALE_LANG_BYTES;
			} else {
				$received_out = 0;
			}

			$connected .= "
			  <tr>
				<td>" . $eth['name'] . "</td>
				<td>" . $eth['address'] . "</td>
				<td>" . $sent_out . "</td>
				<td>" . $received_out . "</td>
				<td><a href='network-report.php?stat=net_" . $eth['name'] . "'>" . LOCALE_LANG_GO . "</a></td>
			  </tr>
			";
		}

		if (! $connected) {
			if ($showempty)
				$connected = "<tr><td colspan='5' align='center'>" . REPORT_LANG_EMPTY_REPORT . "</td>";
			else
				return;
		}

		WebTableOpen(PPTPD_LANG_PPTP_VPN . " - " . PPTPD_LANG_ACTIVE_CONNECTIONS, "100%");
		WebTableHeader(IFACE_LANG_INTERFACE . "|" . NETWORK_LANG_IP . "|" . 
			PPTPD_LANG_SENT . "|" . PPTPD_LANG_RECEIVED . "|" . PPTPD_LANG_NETWORK_STATISTICS);
		echo $connected;
		WebTableClose("100%");
	}
}

// vim: syntax=php ts=4
?>
