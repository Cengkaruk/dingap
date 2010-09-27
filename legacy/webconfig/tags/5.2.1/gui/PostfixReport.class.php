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

/**
 * Postfix reports.
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
require_once(COMMON_CORE_DIR . '/api/Postfix.class.php');
require_once(COMMON_CORE_DIR . '/api/File.class.php');

/**
 * Postfix reports.
 *
 * @package Reports
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

class PostfixReport extends Gui implements Report
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $loaded = false;
	protected $data = "";
	protected $datetype;
	protected $nodata = true;

	const TIME_YESTERDAY = 'yesterday';
	const TIME_TODAY = 'today';
	const TIME_MONTH = 'month';
	const TYPE_OTHER = 'other';
	const TYPE_DOMAIN_SUMMARY_DELIVERED = 'domain_delivered';
	const TYPE_DOMAIN_SUMMARY_RECEIVED = 'domain_received';
	const TYPE_SENDERS = 'senders';
	const TYPE_RECIPIENTS = 'recipients';
	const TYPE_SENDERS_BY_SIZE = 'senders_by_size';
	const TYPE_RECIPIENTS_BY_SIZE = 'recipients_by_size';
	const TYPE_BOUNCED = 'bounced';
	const TYPE_REJECTED = 'rejected';
	const TYPE_DISCARDED = 'discarded';
	const TYPE_DELIVERY_FAILURES = 'delivery_failures';
	const TYPE_WARNING = 'warning';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	function __construct($datetype)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->datetype = $datetype;
	}

	/**
	 * Loads report data.
	 *
	 * @access private
	 * @return void
	 */

	function _GetData()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(COMMON_CORE_DIR . "/reports/postfix/data-" . $this->datetype . ".out");
			$lines = $file->GetContentsAsArray();
		} catch (FileNotFoundException $e) {
			return;
		} catch (Exception $e) {
			throw new Exception($e->GetMessage(), COMMON_WARNING);
		}

		$daily_data = array();	 // Daily statistics
		$summary_data = array();   // Message statistics
		$postfix = new Postfix();  // For locale data

		$linecount = 0;
		$section = "messages";

		foreach ($lines as $line) {
			if (preg_match("/^message deferral detail/", $line)) {
				$section = self::TYPE_OTHER;
			} else if (preg_match("/bytes received/", $line)) {
				$section = "todo";
			} else if (preg_match("/Per-Day Traffic Summary/", $line)) {
				$section = "daily";
			} else if (preg_match("/Per-Hour Traffic Daily Average/", $line)) {
				$section = "todo";
			} else if (preg_match("/Host\/Domain Summary: Message Delivery/", $line)) {
				$section = self::TYPE_DOMAIN_SUMMARY_DELIVERED;
				continue;
			} else if (preg_match("/Host\/Domain Summary: Messages Received/", $line)) {
				$section = self::TYPE_DOMAIN_SUMMARY_RECEIVED;
				continue;
			} else if (preg_match("/Senders by message count/", $line)) {
				$section = self::TYPE_SENDERS;
				continue;
			} else if (preg_match("/Recipients by message count/", $line)) {
				$section = self::TYPE_RECIPIENTS;
				continue;
			} else if (preg_match("/Senders by message size/", $line)) {
				$section = self::TYPE_SENDERS_BY_SIZE;
				continue;
			} else if (preg_match("/Recipients by message size/", $line)) {
				$section = self::TYPE_RECIPIENTS_BY_SIZE;
				continue;
			} else if (preg_match("/message bounce detail/", $line)) {
				$section = self::TYPE_BOUNCED;
				continue;
			} else if (preg_match("/message reject detail/", $line)) {
				$section = self::TYPE_REJECTED;
				continue;
			} else if (preg_match("/message discard detail/", $line)) {
				$section = self::TYPE_DISCARDED;
				continue;
			} else if (preg_match("/smtp delivery failures/", $line)) {
				$section = self::TYPE_DELIVERY_FAILURES;
				continue;
			} else if (preg_match("/Warnings/", $line)) {
				$section = self::TYPE_WARNING;
				continue;
			}

			// Daily report data
			//------------------

			if ($section == "daily") {
				$line = preg_replace("/\s+/", " ", $line);
				$lineparts = explode(" ", $line);
				if (!preg_match("/^\d+/", $lineparts[2]))
					continue;
				
				$unixtime = strtotime($lineparts[0] . " " . $lineparts[1] . " " . $lineparts[2]);
				$thedate = strftime("%b %e %Y", $unixtime);

				$daily_data[$thedate][POSTFIX_LANG_RECEIVED] = $lineparts[4];
				$daily_data[$thedate][POSTFIX_LANG_DELIVERED] = $lineparts[5];
				$daily_data[$thedate][POSTFIX_LANG_DEFERRED] = $lineparts[6];
				$daily_data[$thedate][POSTFIX_LANG_BOUNCED] = $lineparts[7];
				$daily_data[$thedate][POSTFIX_LANG_REJECTED] = $lineparts[8];

			// Grand totals
			//-------------

			} else if ($section == "messages") {
				if (preg_match("/received/", $line)) {
					$summary_data[POSTFIX_LANG_RECEIVED] = trim(preg_replace("/received.*/", "", $line));
					if ($summary_data[POSTFIX_LANG_RECEIVED] != 0)
						$this->nodata = false;
				} else if (preg_match("/delivered/", $line)) {
					$summary_data[POSTFIX_LANG_DELIVERED] = trim(preg_replace("/delivered.*/", "", $line));
				} else if (preg_match("/forwarded/", $line)) {
					$summary_data[POSTFIX_LANG_FORWARDED] = trim(preg_replace("/forwarded.*/", "", $line));
				} else if (preg_match("/deferred/", $line)) {
					$summary_data[POSTFIX_LANG_DEFERRED] = trim(preg_replace("/deferred.*/", "", $line));
				} else if (preg_match("/bounced/", $line)) {
					$summary_data[POSTFIX_LANG_BOUNCED] = trim(preg_replace("/bounced.*/", "", $line));
				} else if (preg_match("/rejected/", $line)) {
					$summary_data[POSTFIX_LANG_REJECTED] = trim(preg_replace("/rejected.*/", "", $line));
				} else if (preg_match("/reject warnings/", $line)) {
					$summary_data[POSTFIX_LANG_REJECT_WARNING] = trim(preg_replace("/reject warnings.*/", "", $line));
				} else if (preg_match("/held/", $line)) {
					$summary_data[POSTFIX_LANG_HELD] = trim(preg_replace("/held.*/", "", $line));
				} else if (preg_match("/discarded/", $line)) {
					$summary_data[POSTFIX_LANG_DISCARDED] = trim(preg_replace("/discarded.*/", "", $line));
				}

			// Summary data
			//-------------

			} else if ($section != "todo") {
				if (! preg_match("/-------/", $line)) {
					$linecount++;
					$data[$section][$linecount] = $line;
				}
			}
		}

		$data["daily"] = $daily_data;
		$data["summary"] = $summary_data;

		$this->loaded = true;
		$this->data = $data;
	}


	/**
	 * Returns dashboard summary report.
	 *
	 * @return string dashboard summary in HTML
	 */

	function GetDashboardSummary()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->loaded)
			$this->_GetData();

		if ($this->nodata)
			return;

		$chartheader = array();
		$chartheader[] = "";
		$chartdata = array();
		$chartdata[] = POSTFIX_LANG_MESSAGES;
		$htmlrows = "";

		foreach ($this->data["summary"] as $label => $value) {
			$htmlrows .= "<tr><td class='chartlegendkey'>$label</td><td>$value</td></tr>";
			if (($label == POSTFIX_LANG_RECEIVED) || ($label == POSTFIX_LANG_DELIVERED))
				continue;
			$chartheader[] = $label;
			$chartdata[] = $value;
		}

		// HTML Output
		//------------

		$legend = WebChartLegend(REPORT_LANG_SUMMARY, $htmlrows);
		WebTableOpen(POSTFIX_LANG_MESSAGES, "100%");
		echo "
		  <tr>
			<td valign='top'>$legend</td>
			<td valign='top' align='center' width='350'>";
			WebChart(
				POSTFIX_LANG_MESSAGES,
				"bar", 
				350,
				250,
				array($chartheader, $chartdata),
				0,
				0,
				0,
				"/admin/postfixreport.php"
			);
		echo "
			</td>
		  </tr>
		";
		WebTableClose("100%");
	}


	/**
	 * Returns monthly summary report.
	 *
	 * @return void
	 */

	function GetFullReport($notimplemented)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->loaded)
			$this->_GetData();

		if ($this->nodata)
			return;

		$htmlrows = "";
		$chartheader = array();
		$chartheader[] = "";
		$chartdata_delivered = array();
		$chartdata_delivered[] = POSTFIX_LANG_DELIVERED;
		$chartdata_deferred = array();
		$chartdata_deferred[] = POSTFIX_LANG_DEFERRED;
		$chartdata_bounced = array();
		$chartdata_bounced[] = POSTFIX_LANG_BOUNCED;
		$chartdata_rejected = array();
		$chartdata_rejected[] = POSTFIX_LANG_REJECTED;

		foreach ($this->data["daily"] as $day => $keys) {
			$htmlrows .= "<tr><td class='chartlegendkey'>$day</td>";
			$chartheader[] = $day;
			foreach ($keys as $key => $value) {
				if (!$value)
					$value = 0;
				else if ($key == POSTFIX_LANG_DELIVERED)
					$chartdata_delivered[] = $value;
				else if ($key == POSTFIX_LANG_DEFERRED)
					$chartdata_deferred[] = $value;
				else if ($key == POSTFIX_LANG_BOUNCED)
					$chartdata_bounced[] = $value;
				else if ($key == POSTFIX_LANG_REJECTED)
					$chartdata_rejected[] = $value;

				// Table output formatting
				if (!$value)
					$value = "&#160;";
				$htmlrows .= "<td>$value</td>";
			}
			$htmlrows .= "</tr>";
		}

		if (! $htmlrows)
			return;

		$htmlrows = "
		  <tr>
			<td class='chartlegendtitle'>" . LOCALE_LANG_DATE . "</td>
			<td class='chartlegendtitle'>" . POSTFIX_LANG_RECEIVED . "</td>
			<td class='chartlegendtitle'>" . POSTFIX_LANG_DELIVERED . "</td>
			<td class='chartlegendtitle'>" . POSTFIX_LANG_DEFERRED . "</td>
			<td class='chartlegendtitle'>" . POSTFIX_LANG_BOUNCED . "</td>
			<td class='chartlegendtitle'>" . POSTFIX_LANG_REJECTED . "</td>
		  </tr>
		" . $htmlrows;
		$textsummary = WebChartLegend(POSTFIX_LANG_REPORT_DAILY, $htmlrows);

		WebTableOpen(POSTFIX_LANG_REPORT_DAILY, "100%");
		echo "
		  <tr>
			<td align='center'>";
			WebChart(
				POSTFIX_LANG_REPORT_DAILY, 
				"stacked bar", 
				550,
				550, 
				array ($chartheader, $chartdata_delivered, $chartdata_deferred, $chartdata_bounced, $chartdata_rejected),
				array (CHART_COLOR_OK1, CHART_COLOR_OK2, CHART_COLOR_WARNING, CHART_COLOR_ALERT),
				0,
				false
			);
		echo "
			</td>
		  </tr>
		  <tr>
			<td>$textsummary</td>
		  </tr>
		";
		WebTableClose("100%");
	}


	/**
	 * Returns report detail.
	 *
	 * @param string $title report title
	 * @param string $type report type
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetReportDetail($title, $type, $maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->loaded)
			$this->_GetData();

		if ($this->nodata)
			return;

		$tablerows = "";
		$lastsection = "";
		$linecount = 0;

		foreach ($this->data[$type] as $ignore => $line) {
			$line = preg_replace("/</", "&lt;", $line);
			$line = preg_replace("/>/", "&gt;", $line);
			if (strlen($line) > 80)
				$line = substr($line, 0, 80);

			if (preg_match("/^\s*$/", $line)) {
				// Skip blank lines
			} else if (preg_match("/^\s*[0-9]/", $line)) {
				$tablerows .= "<tr>";
				$tablerows .= "<td><pre style='margin: 0px'>$line</pre></td>";
				$tablerows .= "</tr>";
			} else {
				// Table header
				$tablerows .= "<tr>";
				$tablerows .= "<td class='mytableheader'><pre style='margin: 0px'>$line</pre></td>";
				$tablerows .= "</tr>";
			}
			
			$linecount++;
			if ($linecount > $maxrecords)
				break;
		}

		WebFormOpen($_SERVER['PHP_SELF'], "post");
		WebTableOpen($title, "100%");
		echo $tablerows;
		if ($linecount <= 1)
			echo "<tr><td align='center'>" . REPORT_LANG_EMPTY_REPORT . "</td></tr>";
		else if ($linecount > $maxrecords)
			echo "<tr><td align='center'>" . WebButtonShowFullReport($type) . "</td></tr>";
		WebTableClose("100%");
		WebFormClose();
	}


	/**
	 * Returns domain summary delivered report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetDomainSummaryDelivered($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_REPORT_DOMAIN_SUMMARY_DELIVERED, self::TYPE_DOMAIN_SUMMARY_DELIVERED, $maxrecords);
	}


	/**
	 * Returns domain summary received report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetDomainSummaryReceived($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_REPORT_DOMAIN_SUMMARY_RECEIVED, self::TYPE_DOMAIN_SUMMARY_RECEIVED, $maxrecords);
	}


	/**
	 * Returns recipients by size report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetRecipientsBySize($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_RECIPIENTS . " - " . POSTFIX_LANG_SIZE, self::TYPE_RECIPIENTS_BY_SIZE, $maxrecords);
	}


	/**
	 * Returns recipients report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetRecipients($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_RECIPIENTS, self::TYPE_RECIPIENTS, $maxrecords);
	}


	/**
	 * Returns senders report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetSenders($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_SENDERS, self::TYPE_SENDERS, $maxrecords);
	}


	/**
	 * Returns senders by size report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetSendersBySize($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_SENDERS . " - " . POSTFIX_LANG_SIZE, self::TYPE_SENDERS_BY_SIZE, $maxrecords);
	}


	/**
	 * Returns message bounce detail.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetMessageBounceDetail($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_BOUNCED, self::TYPE_BOUNCED, $maxrecords);
	}


	/**
	 * Returns message reject report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetMessageRejectDetail($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_REJECTED, self::TYPE_REJECTED, $maxrecords);
	}


	/**
	 * Returns message discard detail report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetMessageDiscardDetail($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_DISCARDED, self::TYPE_DISCARDED, $maxrecords);
	}


	/**
	 * Returns smtp delivery failures report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetSmtpDeliveryFailures($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(POSTFIX_LANG_DELIVERY_FAILURES, self::TYPE_DELIVERY_FAILURES, $maxrecords);
	}

	/**
	 * Returns the available report types.
	 *
	 * @return array list of report types
	 */

	function GetTypes()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$types = array();

		$types[self::TIME_MONTH] = POSTFIX_LANG_MONTH;
		$types[self::TIME_YESTERDAY] = POSTFIX_LANG_YESTERDAY;
		$types[self::TIME_TODAY] = POSTFIX_LANG_TODAY;

		return $types;
	}

	/**
	 * Returns warnings report.
	 *
	 * @param int $maxrecords maximum number of records to return
	 * @return  void
	 */

	function GetWarnings($maxrecords)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->GetReportDetail(LOCALE_LANG_WARNING, self::TYPE_WARNING, $maxrecords);
	}

	/**
	 * Returns state of data availability.
	 *
	 * @return boolean true if data is available
	 * @throws EngineException
	 */

	function IsDataAvailable()
	{
		if (!$this->loaded)
			$this->_GetData();

		if ($this->nodata)
			return false;
		else
			return true;
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
