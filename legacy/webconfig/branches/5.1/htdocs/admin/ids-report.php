<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2007 Point Clark Networks.
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");
require_once("../../gui/SnortReport.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-snort.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$max = isset($_REQUEST['max']) ? $_REQUEST['max'] : 5;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "overview";
$month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date("m");
$day = isset($_REQUEST['day']) ? $_REQUEST['day'] : 0;

$detail = isset($_REQUEST['detail']) ? $_REQUEST['detail'] : "";
$start = isset($_REQUEST['start']) ? $_REQUEST['type'] : 0;
$do_viewchart = isset($_GET['DisplayReport']) ? true : false;

$report = new SnortReport();
$params = array();

/*
if (isset($_REQUEST['DisplayReport'])) {

	// TODO: should not be necessary?
	if (isset($detail))
		unset($detail);

	if ($_REQUEST['type'] != "overview")
		$do_viewchart = true;
}

if ($do_viewchart) {

} else if (isset($_POST['ViewDetail'])) {

	$func = "ShowDetail";
	list($month,$day,$type,$detail,$subtype) = explode("-",$viewdetail);
	$month = intval($month);
	$day = intval($day);
	$type = array_keys($report->links,$type);

	if (is_array($type)) {
		$t = $type[0];
		unset($type);
		$type = $t;
	}

	$params['month'] = $month;
	$params['day'] = $day;
	$params['type'] = $type;
	$params['detail'] = $detail;
	$params['subtype'] = preg_replace("/%20/"," ",$subtype);

	if (!$max)
		$max = 10;
} else if (isset($_POST['day']) && isset($_POST['month'])) {
	if (is_array($detail)) {
		if (key($detail) != $report->links[LOCALE_LANG_DATE]) {
			$func = "ShowDetail";
			$type = array_keys($report->links,key($detail));

			if (is_array($type)) {
				$t = $type[0];
				unset($type);
				$type = $t;
			}

			$params['type'] = $type;
			$params['detail']=end($detail);

			if (!$max)
				$max = 10;
		}
	}
} elseif (isset($_POST['month'])) {
	$day = 0;

	if ($detail) {
		if ($detail[$report->links[LOCALE_LANG_DATE]]) {
			$day = $detail[$report->links[LOCALE_LANG_DATE]];
			unset($detail[$report->links[LOCALE_LANG_DATE]]);
		}

		$func = "ShowDetail";
		$type = array_keys($report->links,key($detail));

		if (is_array($type)) {
			$t = $type[0];
			unset($type);
			$type = $t;
		}

		$params['type'] = $type;
		$params['detail'] = end($detail);

		if (!$max)
			$max = 10;
	}
} else {
	$day = 0;
	$month = date("m");
}
*/

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayReportSettings($type, $max, $month, $day);

try {
	if (isset($_REQUEST['DisplayDetailsByType'])) {
		DisplayDetails($type, $month, $day, $max, $detail);
	} else if (isset($_REQUEST['DisplayDetailsByDate'])) {
		DisplayDetails($type, $month, $day, $max, $detail);
	} else if (isset($_REQUEST['DisplayReport'])) {
		if ($_REQUEST['type'] == "overview")
			DisplayOverview($type, $month, $day, $max);
		else
			DisplayReport($type, $month, $day, $max);
	} else {
		DisplayOverview($type, $month, $day, $max);
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Displays report settings.
 */

function DisplayReportSettings($type, $max, $month, $day)
{
	global $report;

	try {
		$monthlist = $report->GetMonthList();
		$monthsavailable = $report->GetMonthsAvailable();
		$typesavailable = $report->GetTypesAvailable();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Load up our drop-down arrays with values
	$number_of_results = array(5, 10, 20, 50, 100);
	$days = array_pad(array(), 32, 0);
	array_walk($days, "key2val");

	foreach ($monthlist as $monthid)
		$monthsactive[$monthid] = $monthsavailable[$monthid];

	// Add our "overview" report type and "all days"
	$typesavailable['overview'] = SNORT_LANG_TYPE_OVERVIEW;
	$days[0] = LOCALE_LANG_ALL;

	WebFormOpen();
	WebTableOpen(REPORT_LANG_OVERVIEW, "400");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . REPORT_LANG_NAME . "</td>
			<td nowrap>" . WebDropdownHash("type", $type, $typesavailable) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . REPORT_LANG_REPORT_PERIOD . "</td>
			<td nowrap>" . WebDropdownHash("month", $month, $monthsactive) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_DATE . "</td>
			<td nowrap>" . WebDropdownHash("day", $day, $days) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . REPORT_LANG_NUMBER_OF_RECORDS . "</td>
			<td nowrap>" . WebDropdownArray("max", $max, $number_of_results) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td nowrap>" . WebButtonGenerate("DisplayReport") . "</td>
		</tr>
	";
	WebTableClose("400");
	WebFormClose();
}

/**
 * Displays overview report.
 */

function DisplayOverview($type, $month, $day, $max)
{
	global $report;

	try {
		$reportdata = $report->GetData($month, $day);
		$monthname = $report->GetMonthName($month);
		$types = $report->GetTypesAvailable();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$day = ($day > 0) ? $day: '';
	$body = "";

	foreach ($types as $type => $reportname) {

		$chartinfo = CreateChartInfo($type, $reportdata, 0, $max);

		if ($chartinfo['count'] == 0)
			$charthtml = REPORT_LANG_EMPTY_REPORT;
		else
			$charthtml = GetGraphicalOutput($type, $chartinfo['chartdata']);

		$body .= "
			<tr>
				<td valign='top' class='mytableheader'><b>" . $reportname . "</td>
			</tr>
			<tr>
				<td align='center'>" . $charthtml . "</td>
			</tr>
		";
	}

	WebTableOpen(REPORT_LANG_SUMMARY . " - $monthname $day", "100%");
	WebTableBody($body);
	WebTableClose("100%");
}

/** 
 * 
 */
function DisplayReport($type, $month, $day, $max)
{
	global $report;

	try {
		$reportdata = $report->GetData($month, $day);
		$monthname = $report->GetMonthName($month);
		$types = $report->GetTypesAvailable();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Populate our chart data structure
	$chartinfo = CreateChartInfo($type, $reportdata, 0, $max);

	// Bail if no data found
	if ($chartinfo['count'] == 0) {
		WebDialogWarning(REPORT_LANG_EMPTY_REPORT);
		return;
	}

	// Get the chart graph HTML
	$charthtml = GetGraphicalOutput($type, $chartinfo['chartdata']);

	// Add some nice summary and legend information
	$summaryrows = "
		<tr>
			<td>" . REPORT_LANG_REPORT_PERIOD . "</td>
			<td>" . $monthname . (($day > 0)?" $day":'') . "</td>
		</tr>
	";

	if (! empty($data['count'])) {
		$summaryrows = "
			<tr>
				<td>" . REPORT_LANG_RESULTS . "</td>
				<td>" . $data['count'] . "</td>
			</tr>
		";
	}

	$summary = WebChartLegend(REPORT_LANG_SUMMARY, $summaryrows, "", 300);

	// HTML Output
	WebTableOpen($types[$type], "100%");
	echo "
		<tr><td valign='top' align='center'>$summary</td></tr>
		<tr><td valign='top' align='center'>" . $charthtml . "</td></tr>
		<tr><td align='center'>" . $chartinfo['legend'] . "</td></tr>
	";
	WebTableClose("100%");
}

function DisplayDetails($type, $month, $day, $max, $detail)
{
	global $report;

	try {
		$reportdata = $report->GetData($month, $day);
		$monthname = $report->GetMonthName($month);
		$types = $report->GetTypesAvailable();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$start = 0;

	switch ($type) {
		case SnortReport::TYPE_PRI:
			// TODO: document what's going on here
			$subdata = array_group_subkey($reportdata[SnortReport::TYPE_ALERT], 3);
			$subsubdata = array_group_subkey($subdata[$detail], 0, SnortReport::TYPE_ALERT);
			$chartinfo = CreateChartInfo(SnortReport::TYPE_CLASS, $subsubdata, $start, $max);
			$chart = GetGraphicalOutput(SnortReport::TYPE_CLASS, $chartinfo['chartdata']);
			$legend = $chartinfo['legend'];
			$summarylang = SNORT_LANG_TYPE_PRI;
			break;

		case SnortReport::TYPE_PROTO:
			$subdata = array_group_subkey($reportdata[SnortReport::TYPE_ALERT], 4);
			$subsubdata = array_group_subkey($subdata[$detail], 0, SnortReport::TYPE_ALERT);
			$chartinfo = CreateChartInfo(SnortReport::TYPE_CLASS, $subsubdata, $start, $max);
			$chart = GetGraphicalOutput(SnortReport::TYPE_CLASS, $chartinfo['chartdata']);
			$legend = $chartinfo['legend'];
			$summarylang = SNORT_LANG_TYPE_PROTO;
			break;

		default:
			$summarylang = SNORT_LANG_ID;

			if ($type == SnortReport::TYPE_ALERT)
				$sidname = $reportdata[SnortReport::TYPE_ALERT][$detail][1];

			// TODO: this is just a hack ... hard to follow
			if ($type == SnortReport::TYPE_CLASS) {
				$subtype = SnortReport::TYPE_ALERT;
				$summarylang = SNORT_LANG_ID;
			} else if ($type == SnortReport::TYPE_VICTIM ) {
				$summarylang = SNORT_LANG_TYPE_VICTIM;
			} else if ($type == SnortReport::TYPE_ATTACK ) {
				$summarylang = SNORT_LANG_TYPE_ATTACK;
			} else if ($type == SnortReport::TYPE_PORT) {
				$summarylang = SNORT_LANG_TYPE_PORT;
			} else {
				$summarylang = SNORT_LANG_ID;
			}

			$chartinfo = CreateDetailReport($type, $month, $day, $max, $detail, $reportdata);
			$chart = $chartinfo['chart'];
	}

	if ($subtype) {
		$title = $types[$subtype];
	} else {
		$title = $types[$type];
	}

	if (empty($chartinfo['count'])) {
		$results = "";
	} else {
		$results = "
			<tr>
				<td>" . REPORT_LANG_RESULTS . "</td>
				<td>" . $chartinfo['count'] . "</td>
			</tr>
		";
	}

	$summaryrows = "
		<tr>
			<td>" . REPORT_LANG_REPORT_PERIOD . "</td>
			<td>" . $monthname . (($day > 0)?" $day":'') . "</td>
		</tr>
		$results
		<tr>
			<td>" . $summarylang . "</td>
			<td>" . $detail . "</td>
		</tr>
	";

	if ($sidname) {
		$summaryrows .= "
			<tr>
				<td>" . LOCALE_LANG_DESCRIPTION . "</td>
				<td><a href='" . $_SESSION['system_redirect'] . "/intrusion-detection/sid/" . $detail . "' 
					target='_blank' title='" . WEB_LANG_PAGE_LOOKUP_SID . "'>" . $sidname . "</a></td>
			</tr>
		";
	}

	$summary = WebChartLegend(REPORT_LANG_SUMMARY, $summaryrows, "", 450);

	$body = $chart;

	WebTableOpen($title, "100%");
	echo "
		<tr><td valign='top' align='center'>$summary</td></tr>
		<tr><td valign='top' align='center'>$body</td></tr>
		<tr><td align='center'>$legend</td></tr>
	";
	WebTableClose("100%");
}

/**
 * @param string $type intrusion detection report type
 * @param array $reportdata global report data structure
 * @param int $start ?
 * @param int $max ?
 * @return array chart details
 */
function GetChartInfo($type, $reportdata, $start, $max)
{
	$data = CreateChartInfo($type, $reportdata, $start, $max);

	if ($data == null)
		return;

	$chart = GetGraphicalOutput($type, $data['chartdata']);

	return array(
			'chart' => $chart,
			'count' => $data['count'],
			'legend' => $data['legend']
	);
}

/**
 * @param string $type intrusion detection report type
 * @param array $reportdata global report data structure
 * @param int $start ?
 * @param int $max ?
 * @return array chart data structure
 */
function CreateChartInfo($type, $reportdata, $start, $max)
{
	global $report;

	// Extract the data that we need from our global data structure
	//--------------------------------------------------------------

	$settings = $report->GetSettings($type);
	extract($settings);
	$rawdata = $reportdata[$settings['ndx']];

	if (count($rawdata) == 0)
		return;

	// Sort the data
	//--------------

	if ($ndx == SnortReport::TYPE_DATE) {
		ksort($rawdata);
	} else {
		subkey_sort($rawdata, $hits, false);
	}

	try {
		$day = $report->GetDay();
		$month = $report->GetMonth();
		$types = $report->GetTypesAvailable();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Override max parameter for date-based reports?
	if ($type == SnortReport::TYPE_DATE)
		$max = 31;

	// TODO: document what's going on here
	if (($sum)|($labels > 0)) {
		$group = array_group_subkey($rawdata,$labels);
		$count = count($group);
		$group = array_cut($group,$start,$start+$max);
	} else {
		$count = count($rawdata);
		$group = array(array_cut($rawdata,$start,$start+$max));
	}

	$key = array_keys($group);
	$size = count($key);

	$data = array();
	$rows = '';
	$legend = '';

	// TODO: document what's going on here

	for ($i=0; $i<$size; $i++) {
		$geturl = '';
		$subrows = '';
		$key2 = array_keys($group[$key[$i]]);
		$size2 = count($key2);

		for ($j=0; $j<$size2; $j++) {
			if ($labels == 0 ) {
				if ($sum) {
					$label = $key[$i];
				} else {
					$label = $key2[$j];
				}
			} else {
				$label = $group[$key[$i]][$key2[$j]][$labels];
			}

			if ($sum) {
				if (!isset($data[$label]))
					$data[$label] = '';
				$data[$label] += $group[$key[$i]][$key2[$j]][$hits];
			} else {
				$data[$label] = $group[$key[$i]][$key2[$j]][$hits];
			}

			if (! $isthumb) {
				$header = implode("|",array_keys($legendfields));
				// $geturl .= "&type=" . $report->links[$type];
				$geturl = "";
				$geturl .= "&type=" . $type;
				$geturl .= "&detail=" . $key2[$j];
				$geturl .= "&month=$month";
				$geturl .= "&max=$max";

				if ($day > 0)
					$geturl .= "&day=" . $j;

				// UGLY/CREATIVE HACK HERE...LEAVE SUBROWS ALONE!!!
				// the subrows should be ONE line each, the preg_grep below REQUIRES this
				// to properly format the legend for pri & protocol (sum & !nested)
				$subrows .= "<tr>";

				foreach ($legendfields as $lf) {//lf=array(0:$fieldindex,1:width,2:createlink,3:explode,4:$linkname)

					if ($lf[0] == 0) {//use key

						if ($lf[2] > 0) {
							// TODO: hard to track down where to fix this.
							if ($type == SnortReport::TYPE_DATE)
								$value = $key2[$j];
							else
								$value = "<a href='?DisplayDetailsByType=yes" . $geturl . "'>" . $key2[$j] . "</a> ";
						} else {
							$value = $key2[$j];
						}

					} else {// use field
						$value = $group[$key[$i]][$key2[$j]][$lf[0]];
					}

					if ($lf[3]) {
						$subvalues = explode(" ",$value);
						natsort($subvalues);
						$value = '';
						foreach($subvalues as $val) {
							if ($lf[2] > 0) {
								$value .= "<a href='?DisplayDetailsByDate=yes" . $geturl . "&day=$val'>" . $val . "</a> ";
							} else {
								$value .= "$val ";
							}
						}
					}

					if (($sum)&(! $nested))
						$value = '%s';

					// NO \n here... see note above
					$subrows .= "<td valign='top' width='" . $lf[1] . "'>$value</td>";
				}

				$subrows .= "</tr>\n";
			}
		}

		if (! $isthumb) {
			if ($sum & (!$nested)) { //pri,proto
				$matches = preg_grep("/<tr>(.+)<\/tr>/",explode("\n",$subrows));
				$subrows = '';
				$row = end($matches);
				// $geturl = "&type=" . $report->links[$type];
				$geturl = "&type=" . $type;
				$geturl .= "&detail=" . $key[$i];
				$geturl .= "&month=$month";
				$geturl .= "&max=$max";
				$geturl = "<a href='?DisplayDetailsByType=yes" . $geturl . "'>" . $key[$i] . "</a>";
				$rows .= sprintf($row, $geturl, $data[$key[$i]]);
			}

			if (($key[$i] == 0) ||(($sum)&(! $nested))) {
				$title = $types[$type];
			} else {
				$title = $types[$key[$i]];
			}

			if ($nested) {
				$legend .= WebChartLegend(ucwords($key[$i]), $subrows, $header);
			} else {
				$rows .= $subrows;
			}
		}
	}

	if ($rows != '')
		$legend = WebChartLegend($title, $rows, $header);

	$legend = preg_replace("/width='(\d+)'/","width='$1%'",$legend);

	if ($type == SnortReport::TYPE_DATE)
		ksort($data);
	else
		arsort($data);

	$chartdata['chartdata'] = $data;
	$chartdata['count'] = $count;
	$chartdata['legend'] = $legend;

	return $chartdata;
}

/**
 * @param string $type intrusion detection report type
 * @param array $data key/value pairs for chart
 * @param string $uri optional URI to create a link
 */

function GetGraphicalOutput($type, $data, $uri = null)
{
	global $report;

	try {
		$day = $report->GetDay();
		$month = $report->GetMonth();
		$types = $report->GetTypesAvailable();
		$settings = $report->GetSettings($type);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Title
	$title = $types[$type];

	// Put chart keys in first element
	$chartdata = array();
	$chartdata[0] = array_map("ucwords", array_keys($data));

	// Convert SIDs to human-readable names?
	if ($type == SnortReport::TYPE_ALERT)
		array_map("sid2name", $chartdata[0]);

	// Truncate long keys
	array_walk($chartdata[0], 'truncate20');

	// Put chart values in second element
	$chartdata[1] = array_values($data);

	// Reverse ordering on bar charts?
	if ($settings['graphtype'] == "bar") {
		$chartdata[0] = array_reverse($chartdata[0]);
		$chartdata[1] = array_reverse($chartdata[1]);
	}

	// Add title to chart data
	array_unshift($chartdata[0], "");
	array_unshift($chartdata[1], $title);

	if ($isthumb) {
		if (is_null($uri)) {
			if ($day > 0) {
				$url = "?DisplayReport=" . preg_replace("/ /","%20", $type) . "&month=$month&day=$day";
			} else {
				$url = "?DisplayReport=" . preg_replace("/ /","%20", $type) . "&month=$month";
			}
		} else {
			$url = $uri;
		}
	} else {
		if (is_null($uri)) {
			$url ='';
		} else {
			$url = $uri;
		}
	}

	// TODO: move to settings
	$series_color = array( "4e627c", "ffdc30", "d2d23d", "3b5743", "ffc262");

	ob_start();
	WebChart($title, $settings['graphtype'], $settings['x'], $settings['y'], $chartdata, $series_color, "#fefefe", false, $url);
	$chart = ob_get_contents();
	ob_end_clean();

	return $chart;
}

function CreateDetailReport($type, $month, $day, $max, $detail, $reportdata)
{
	global $report;

	$start = 0;
	$types = $report->GetTypesAvailable();
	$data = $report->GetDetails($type, $detail);

	$key = array_keys($data);
	$size = count($key);

	$chart = '';
	$legend = '';

	for($i=0; $i<$size; $i++) {
		if ($key[$i] != $type) {
			$url = '';
			$title = $types[$key[$i]];

			switch ($key[$i]) {

			case SnortReport::TYPE_DATE :
				$title = $report->GetMonthName($month);
				$max = 31;
				$days = array_keys($data[$key[$i]]);
				$dc = count($days);
				sort($days);
				$sum = 0;

				for($j=0;$j<$dc;$j++) {
					$sum += $data[$key[$i]][$days[$j]];

					if (isset($days[$j + 1])) {
						for($k=$days[$j] + 1;$k<$days[$j+1];$k++) {
							$data[$key[$i]][$k] = 0;
						}
					}
				}

				ksort($data[$key[$i]]);
				break;

			default:
				arsort($data[$key[$i]]);

				if (count($data[$key[$i]]) > $max) {
					if (!$subtype) {
						$url = 
							"?ViewDetail=yes" .
							"&month=$month" .
							"&day=" . intval($day) .
							// "&type=" . $report->links[$type] .
							"&type=" . $type .
							"&detail=" . $detail .
							"&subtype=" . preg_replace("/ /","%20",$key[$i]);
					}
				}
			}//end switch

			if (((! empty($subtype))&($key[$i] != $subtype))
			        || (count(array_filter(array_flip($data[$key[$i]]))) == 0)) {
				continue;
			}

			$chartdata = array_cut($data[$key[$i]],$start,$start + $max);
	//		$thumb = GetChart($key[$i], $chartdata, empty($subtype),$url);
			$thumb = GetGraphicalOutput($key[$i], $chartdata, $url);

			if ((! empty($subtype))&($key[$i] == $subtype)) {
				$count = count($data[$key[$i]]);
				$headers = "|".$types[$subtype]."|".SNORT_LANG_HITS;
				$legend = "<tr><td>".WebChartLegend($types[$subtype],CreateLegendRows($chartdata),$headers)."</td></tr>";
				$legend .="<tr><td>".PageLinks($start,$max,$count)."</td></tr>";
			}

			$chart .= "
			          <table width='100%' border='0' cellspacing='0' cellpadding='3'>
			          <tr><td valign='top' class='mytableheader'><b>" . $types[$key[$i]] . " : $count</b></tr>
			          <tr><td align='center'>".$thumb."</td></tr>
			          $legend
			          </table>
			          ";

		}//end if $key[$i]!=$type
	}//end for i

	return array(
	           'chart' => $chart,
	           'count' => $sum
	       );
}

function PageLinks($start,$max,$count)
{
	$viewdetail = (($_GET['viewdetail']) ? $_GET['viewdetail'] : $_POST['viewdetail'] );

	$links = "<table width='100%'><tr>";

	if (($start - $max) >= 0 ) {
		$links .= "<td align='right' width='90%'>".WEB_PAGE_FORM;
		$links .= sprintf(WEB_PAGE_FORM_HIDDEN,"start",$start-$max);
		$links .= sprintf(WEB_PAGE_FORM_HIDDEN,"max",$max);
		$links .= sprintf(WEB_PAGE_FORM_HIDDEN,"viewdetail",$viewdetail);
		$links .= "<input type=submit name='page' value=".LOCALE_LANG_PREVIOUS." />";
		$links .= WEB_PAGE_FORM_CLOSE."</td>";
	}

	if (($start + $max) <= $count) {
		$links .= "<td align='right'>".WEB_PAGE_FORM;
		$links .= sprintf(WEB_PAGE_FORM_HIDDEN,"start",$start+$max);
		$links .= sprintf(WEB_PAGE_FORM_HIDDEN,"max",$max);
		$links .= sprintf(WEB_PAGE_FORM_HIDDEN,"viewdetail",$viewdetail);
		$links .= "<input type=submit name='page' value=".LOCALE_LANG_NEXT." /></td>";
		$links .= WEB_PAGE_FORM_CLOSE."</td>";
	}

	$links .= "</tr></table>";
	return $links;
}

function CreateLegendRows($data)
{
	$rownumber = array_pad(array(), count($data) + 1, 0);
	array_walk($rownumber,"key2row");
	unset($rownumber[0]);

	if (is_array($data)) {
		$rows = "<tr><td width='30'>###</td><td>";
		$rows .= implode("</td><td>%s</td></tr>\n<tr><td width='30'>###</td><td>",array_keys($data));
		$rows .= "</td><td>%s</td></tr>\n";
		$rows = vsprintf($rows,array_values($data));
		$rows = vsprintf(str_replace("###","%s",$rows),$rownumber);
	} else {
		$rows = "<tr><td>".LOCALE_LANG_ERRMSG_PARSE_ERROR."</td></tr>";
	}

	return $rows;
}

function subkey_sort(&$array, $subkey, $asc = true)
{
	if ($asc) {
		uasort($array, create_function('$a,$b','return strnatcasecmp($a['.$subkey.'], $b['.$subkey.']);'));
	} else {
		uasort($array, create_function('$a,$b','return strnatcasecmp($b['.$subkey.'], $a['.$subkey.']);'));
	}
}

function array_group_subkey(&$array,$subkey,$newkey='')
{
	$new = array();
	$key = array_keys($array);
	$size = count($key);

	if ($newkey !='') {
		for ($i=0; $i<$size; $i++) {
			$new[$newkey][$key[$i]]=$array[$key[$i]];
		}
	} else {
		for ($i=0; $i<$size; $i++) {
			$group = $array[$key[$i]][$subkey];
			$new[$group][$key[$i]]=$array[$key[$i]];
		}
	}

	return $new;
}

// simular to array_slice, but maintains keys
function array_cut(&$array,$start,$end)
{
	$slice = array();
	$key = array_keys($array);
	$size = count($key);

	if ($end > $size)
		$end = $size;

	for ($i=$start; $i<$end; $i++) {
		$slice[$key[$i]] = $array[$key[$i]];
	}

	return $slice;
}

// callback functions
function truncate11(&$v,$k=null)
{
	if (!(is_numeric($v[0])))
		$v = substr($v,0,11);
}

function truncate20(&$v,$k=null)
{
	if (!(is_numeric($v[0])))
		$v = substr($v,0,20);
}

function key2val (&$v,$k)
{
	if (isset($GLOBALS['start']))
		$start = $GLOBALS['start'];
	else
		$start = 0;
	
	$v = $k + $start;
}

function key2row (&$v,$k)
{
	$start = $GLOBALS['start'];
	$v = $k + $start;
}

function sid2name(&$v,$k=null)
{
	global $report;

	$day = $report->GetDay();
	$month = $report->GetMonth();

	if (is_numeric($v)) {
		$name = $report->logs[$month][$day][SnortReport::TYPE_ALERT][$v][1];
		$v = ucwords($name);
	}
}

?>
