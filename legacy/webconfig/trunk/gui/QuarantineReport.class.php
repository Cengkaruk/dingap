<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008-2009 Point Clark Networks
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 3
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
// Nov 2008 : Original work submitted to Point Clark Networks (W.H.Welch)
///////////////////////////////////////////////////////////////////////////////

/**
 * Quarantine Class
 *
 * @package Reports
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once(COMMON_CORE_DIR . '/gui/Gui.class.php');
require_once(COMMON_CORE_DIR . '/gui/Report.class.php');
require_once(COMMON_CORE_DIR . '/api/Mailzu.class.php');

// TODO: generates an error somewhere if system database is not initialized
try {
	$workaround_mailzu = new Mailzu();
	$workaround_dbpass = $workaround_mailzu->GetPassword();
	if (!empty($workaround_dbpass))
		require_once('/var/webconfig/htdocs/mailzu/lib/DBEngine.class.php');
} catch (Exception $e) {
	//
}

/**
 * Quarantine reports.
 *
 * @package Reports
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

class QuarantineReport extends Gui implements Report
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $loaded = false;
	protected $data = null;
	protected $headers = array();
	protected $chartcolors = array( "4e627c", "ffdc30", "ffccff", "0080ff", "ffc262");

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->headers = array(
			'date' => LOCALE_LANG_DATE,
			Mailzu::TYPE_SPAM => MAILZU_LANG_SPAM,
			Mailzu::TYPE_VIRUS => MAILZU_LANG_VIRUS,
			Mailzu::TYPE_BANNED => MAILZU_LANG_BANNED_FILES,
			Mailzu::TYPE_HEADER => MAILZU_LANG_BAD_HEADERS,
			Mailzu::TYPE_TOTAL => MAILZU_LANG_TOTAL
		);
	}

	/**
	 * Outputs dashboard summary report.
	 *
	 * @return string dashboard summary in HTML
	 */

	function GetDashboardSummary()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->loaded)
			$this->_GetData();

		$chartheader = array();
		$chartdata = array();

		$htmlrows = "";

		foreach ($this->data["Total"] as $label => $value) {
			if ($label == Mailzu::TYPE_PENDING){
				continue;
			}

			$thetotal = 1;

			// show total in legend, but not chartdata
			if ($label == Mailzu::TYPE_TOTAL){
				$thetotal = $value;
				$htmlrows .= "<tr class='mytableheader'><td>{$this->headers[$label]}</td><td>$value</td></tr>";
				continue;
			}else{
				$htmlrows .= "<tr><td class='chartlegendkey'>{$this->headers[$label]}</td><td>$value</td></tr>";
			}
			// reverse sort "bar" chart workaround
			array_unshift($chartheader,$this->headers[$label]);
			array_unshift($chartdata,$value);
		}

		array_unshift($chartheader,'');
		array_unshift($chartdata,MAILZU_LANG_MESSAGES);

		// HTML Output
		//------------

		$legend = WebChartLegend(REPORT_LANG_SUMMARY, $htmlrows);
		$chartinfo = ($thetotal == 0) ? array() : array($chartheader, $chartdata);

		WebTableOpen(MAILZU_LANG_STATE, "100%");
		echo "
		  <tr>
			<td valign='top'>$legend</td>
			<td valign='top' align='center' width='350'>";
			WebChart(
				MAILZU_LANG_MESSAGES,
				"bar",
				350,
				0,
				$chartinfo,
				0,
				0,
				0
			);
		echo "
			</td>
		  </tr>
		";
		WebTableClose("100%");
	}

	/**
	 * Outputs full summary report.
	 *
	 * @return void
	 */

	function GetFullReport($notimplemented)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->loaded)
			$this->_GetData();

		$chartheader = array();
		$chartdata = array();

		$html = array();
		$i = 0;
		foreach ($this->data as $key => $val) {
			$row = "<td>$key</td> \n";
			if ($key != 'Total'){
				$chartheader[]= $key;
			}
			foreach ($val as $label => $subval) {
				if ($label == Mailzu::TYPE_PENDING){
					continue;
				}
				if ($key != 'Total'){
					// show total in legend, but not chartdata
					if ($label != Mailzu::TYPE_TOTAL){
						if (array_key_exists($label,$chartdata)){

							$chartdata[$label][]=$subval;
						}else{
							$chartdata[$label] = array($this->headers[$label],$subval);
						}
					}
				}
				$row .= "<td align=\"center\">$subval</td>\n";
			}
			// reverse sort "bar" chart workaround
			// but keep 'Total' at bottom of legend
			if ($key == 'Total'){
				$html[]='<tr class="mytableheader">'.$row.'</tr>';
			}else{
				array_unshift($html,'<tr class="' . 'mytable' . (($i++%2) ? '':'alt') . '">'.$row).'</tr>';
			}
		}
		array_unshift($chartheader,'');
		array_unshift($chartdata,$chartheader);
		$chartdata = array_values($chartdata);

		// HTML Output
		//------------
		$htmlrows = "
	   	<tr class=\"mytableheader\"><td>".
	   	implode("</td><td align=\"center\">",$this->headers).
	   	"</td></tr>".
	   	implode("\n",$html);

		$legend = WebChartLegend(REPORT_LANG_SUMMARY, $htmlrows);
		WebTableOpen(MAILZU_LANG_STATE, "100%");
		echo "
		  <tr>
			<td valign='top' align='center' width='600'>";
			WebChart(
				MAILZU_LANG_MESSAGES,
				"stacked bar",
				600,
				0,
				$chartdata,
				$this->chartcolors,
				0,
				0
			);
		echo "
			</td>
		  </tr>
		  <tr>
			<td valign='top'>$legend</td>
			</td>
		  </tr>
		";
		WebTableClose("100%");
	}

	/**
	 * @access private
	 */

	public function _GetData()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$db = new DBEngine();
		$this->data = $db->get_site_summary();
		$this->loaded = true;
		unset($db);
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
