<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2006 Point Clark Networks.
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
 * Snort intrusion detection report.
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
require_once(COMMON_CORE_DIR . '/api/File.class.php');
require_once(COMMON_CORE_DIR . '/api/Folder.class.php');
require_once(COMMON_CORE_DIR . '/api/Snort.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Snort intrusion detection report.
 *
 * @package Reports
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class SnortReport extends Gui implements Report
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 *@var  array  parsed log data
	 */

	public $logs;

	/**
	 *@var  array  allowed report types
	 */

	protected $types;

	/**
	 *@var  array  3 char month names
	 */

	protected $months;

	/**
	 *@var  integer  the month used for the report
	 */

	protected $month;

	/**
	 *@var  integer  the day used for the report
	 */

	protected $day;

	/**
	 *@var  array  links to valid report types
	 */

	public $links;

	/**
	 *@var  string directory for the current reports
	 */

	protected $dir;

	/**
	 *@var  boolean Spyware Report?
	 */

	protected $spyware;

	const PATH_DATA_INTRUSION = "/var/webconfig/reports/snort/";
	const PATH_DATA_SPYWARE = "/var/webconfig/reports/spyware/";
	const TYPE_ALERT = "alerts";
	const TYPE_ATTACK = "attackers";
	const TYPE_CLASS = "classifications";
	const TYPE_DATE = "Date";
	const TYPE_PORT = "port";
	const TYPE_PRI = "priority";
	const TYPE_PROTO = "protocol";
	const TYPE_VICTIM = "victims";
	const DATE_MONTH_1 = "01";
	const DATE_MONTH_2 = "02";
	const DATE_MONTH_3 = "03";
	const DATE_MONTH_4 = "04";
	const DATE_MONTH_5 = "05";
	const DATE_MONTH_6 = "06";
	const DATE_MONTH_7 = "07";
	const DATE_MONTH_8 = "08";
	const DATE_MONTH_9 = "09";
	const DATE_MONTH_10 = "10";
	const DATE_MONTH_11 = "11";
	const DATE_MONTH_12 = "12";
	const SPYWARESID = 500000000;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Snort report constructor.
	 */

	function __construct($spyware = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		require_once(GlobalGetLanguageTemplate(COMMON_CORE_DIR . '/api/Snort.class.php'));

		if ($spyware)
			$this->dir = SnortReport::PATH_DATA_SPYWARE;
		else
			$this->dir = SnortReport::PATH_DATA_INTRUSION;

		$this->logs = array();
		$this->links = array(
		                   SnortReport::TYPE_ALERT =>'sid',
		                   SnortReport::TYPE_CLASS =>'sid',
		                   SnortReport::TYPE_DATE => 'day',
		                   SnortReport::TYPE_ATTACK =>'src',
		                   SnortReport::TYPE_VICTIM =>'dst',
		                   SnortReport::TYPE_PRI =>'pri',
		                   SnortReport::TYPE_PROTO =>'proto',
		                   SnortReport::TYPE_PORT =>'port'
		               );
		$this->types = array(
		                   SnortReport::TYPE_ALERT => SNORT_LANG_TYPE_ALERT,
		                   SnortReport::TYPE_CLASS => SNORT_LANG_TYPE_CLASS,
		                   SnortReport::TYPE_DATE => LOCALE_LANG_DATE,
		                   SnortReport::TYPE_ATTACK => SNORT_LANG_TYPE_ATTACK,
		                   SnortReport::TYPE_VICTIM => SNORT_LANG_TYPE_VICTIM,
		                   SnortReport::TYPE_PRI => SNORT_LANG_TYPE_PRI,
		                   SnortReport::TYPE_PROTO => SNORT_LANG_TYPE_PROTO,
		                   SnortReport::TYPE_PORT => SNORT_LANG_TYPE_PORT
		               );
		$this->months = array(
		                    null,
		                    SnortReport::DATE_MONTH_1 => LOCALE_LANG_MONTH_1,
		                    SnortReport::DATE_MONTH_2 => LOCALE_LANG_MONTH_2,
		                    SnortReport::DATE_MONTH_3 => LOCALE_LANG_MONTH_3,
		                    SnortReport::DATE_MONTH_4 => LOCALE_LANG_MONTH_4,
		                    SnortReport::DATE_MONTH_5 => LOCALE_LANG_MONTH_5,
		                    SnortReport::DATE_MONTH_6 => LOCALE_LANG_MONTH_6,
		                    SnortReport::DATE_MONTH_7 => LOCALE_LANG_MONTH_7,
		                    SnortReport::DATE_MONTH_8 => LOCALE_LANG_MONTH_8,
		                    SnortReport::DATE_MONTH_9 => LOCALE_LANG_MONTH_9,
		                    SnortReport::DATE_MONTH_10 => LOCALE_LANG_MONTH_10,
		                    SnortReport::DATE_MONTH_11 => LOCALE_LANG_MONTH_11,
		                    SnortReport::DATE_MONTH_12 => LOCALE_LANG_MONTH_12
		                );
		$this->month = date("m");
		$this->day = 0;
		$this->spyware = $spyware;
	}

	function GetFullReport($showactions)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	}

	function GetDashboardSummary()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	}


	/**
	 * Retrieve the report month.
	 *
	 * @return integer numeric month
	 */

	function GetMonth()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->month;
	}

	/**
	 * Retrieve the report month name.
	 *
	 * @return string    the month name
	 */

	function GetMonthName($month)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->months[$month];
	}

	/**
	 * Retrieve the report day.
	 *
	 * @return integer    numeric day
	 */

	function GetDay()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->day;
	}

	/**
	 * Get list of report days.
	 *
	 * @return 	mixed	array list of report day, false on error
	 */

	function GetDayList()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$month = $this->month;
		$list = array();
		$folder = new Folder($this->dir . $month);
		$filelist = $folder->GetListing();

		foreach ($filelist as $filename) {
			$file = new File($this->dir . $month . "/" . $filename);

			if ($file->IsDirectory()) {
				array_push($list, $filename);
			}
		}

		return $list;
	}

	/**
	 * Get list of report months
	 *
	 * @return array list of report months
	 */

	function GetMonthList()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();
		$folder = new Folder($this->dir);
		$filelist = $folder->GetListing();

		foreach ($filelist as $filename) {
			$file = new File($this->dir.$filename);

			if ($file->IsDirectory()) {
				array_push($list, $filename);
			}
		}

		if (count($list) == 0)
            throw new EngineException(REPORT_LANG_NO_STATS, COMMON_WARNING);

		return $list;
	}

	/**
	 * Get months in localized form.
	 *
	 * @return  array  hash of report months
	 */

	function GetMonthsAvailable()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->months;
	}

	/**
	 * Get report types in localized form.
	 *
	 * @return  array  hash of report months
	 */

	function GetTypesAvailable()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->types;
	}

	/**
	 * Retrieve the report data.
	 *
	 * @param integer $month numeric entry for month
	 * @param integer $day numeric entry for day, 0=entire month
	 * @return array data for the requested month/day
	 */

	function GetData($month, $day)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// ensure we have a two digit month
		$month = str_pad($month,2,"0",STR_PAD_LEFT);

		if (($day == 0)|(checkdate($month, $day, date("Y")))) {
			if ( count($this->logs[$month][$day]) > 0 ) {
				return $this->logs[$month][$day];
			} else {
				$this->month = $month;
				$this->day = $day;
				try {
					$this->_Load();
				} catch (Exception $e) {
					// TODO: document why this is so
					return array(null);
				}

				return $this->logs[$month][$day];
			}
		} else {
			throw new EngineException(LOCALE_LANG_DATE . " - " . LOCALE_LANG_INVALID, COMMON_WARNING);
		}
	}

	/**
	 * Returns report details.
	 *
	 * @param string $type the type of detail to return
	 * @param string $detail value of specific detail (eg 443 for TYPE_PORT)
	 * @return array data for the requested detail
	 */

	function GetDetails($type, $detail)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ( count($this->logs['details'][$type][$detail]) > 0 ) {
			return $this->logs['details'][$type][$detail];
		} else {
			$day = $this->day;
			$month = $this->month;

			switch($type) {

			case SnortReport::TYPE_ALERT:
				$regex = ":$detail:[0-9+]";
				$ndx = $type;
				$dayfield = 6;
				break;

			case SnortReport::TYPE_CLASS:
				$regex = ":$detail:[0-9+]";
				$ndx = SnortReport::TYPE_ALERT;
				$dayfield = 6;
				break;

			case SnortReport::TYPE_DATE:
				$regex = "^$detail|";
				$ndx = $type;
				$dayfield = 1;
				break;

			case SnortReport::TYPE_ATTACK:
				$regex = $detail.".*:s";
				$ndx = $type;
				$dayfield = 2;
				break;

			case SnortReport::TYPE_VICTIM:
				$regex = "d:".$detail;
				$ndx = $type;
				$dayfield = 2;
				break;

			case SnortReport::TYPE_PRI:
				$regex = "|$detail|";
				$ndx = SnortReport::TYPE_ALERT;
				$dayfield = 6;
				break;

			case SnortReport::TYPE_PROTO:
				$regex = "|$detail|";
				$ndx = SnortReport::TYPE_ALERT;
				$dayfield = 6;
				break;

			case SnortReport::TYPE_PORT:
				$regex = "d:.*:$detail";
				$ndx = $type;
				$dayfield = 2;
			}

			$regex = escapeshellarg($regex);

			if ($day == 0) {
				$detaildays = explode(" ",$this->logs[$month][$day][$ndx][$detail][$dayfield]);
				natsort($detaildays);
			} else {
				$detaildays = array($day);
			}

			try {
				$days = $this->GetDayList();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			foreach ($detaildays as $dday) {
				if (in_array($dday,$days)) {
					$filename = $this->dir . $month . "/" . $dday . "/details.gz";
					$file = new File($filename);

					if ($file->Exists()) {
						exec("zgrep -hE $regex $filename  2>&1",$contents,$retval);
					} else {
						// TODO: locale fix
						throw new EngineException(FILE_LANG_ERRMSG_NOTEXIST . " - " . $filename, COMMON_WARNING);
					}
				} else {
					// Very odd, did the user delete some files from the report directory?
					// TODO: locale fix
					throw new EngineException(FILE_LANG_ERRMSG_NOTEXIST, COMMON_WARNING);
				}
			}

			$details = array();
			foreach ($contents as $line) {
				$part = explode("|",$line);
				$sid = explode(":",$part[1]);
				$src = explode(":",$part[2]);
				$dst = explode(":",$part[3]);
				$details[SnortReport::TYPE_ALERT][$sid[1]]++;
				$details[SnortReport::TYPE_DATE][$part[0]]++;
				$details[SnortReport::TYPE_ATTACK][$src[0]]++;
				$details[SnortReport::TYPE_VICTIM][$dst[1]]++;
				$details[SnortReport::TYPE_PORT][$dst[2]]++;
			}

			$this->logs['details'][$type][$detail] = $details;

			return $this->logs['details'][$type][$detail];
		}
	}

	/**
	 * Get type specific settings used to process report data
	 *
	 * @access public
	 *
	 * @param   string  $type the type of report for which to get settings
	 * @param   boolean  $isthumb is the report a thumbnail?
	 *
	 * @return array type specific settings
	 */

	function GetSettings($type, $isthumb=false)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// default settings
		$ndx = $type;
		$labels = 0;
		$hits = 1;
		$sum = false;
		$nested = false;
		$graphtype = "bar";
		$x = 550;
		$y = 0;

		// type specific settings

		switch($type) {

		case SnortReport::TYPE_ALERT:
			$labels = 1;
			$hits = 5;
			//column_name => array($fieldindex,width%,createlink,explode,$linkname)
			$legendfields = array(
			                    SNORT_LANG_ID => array(0,10,1,0,"detail[".$this->links[$type]."]"),
			                    SNORT_LANG_TYPE_ALERT => array(1,60,0,0),
			                    SNORT_LANG_HITS => array(5,15,0,0),
			                    LOCALE_LANG_DATE => array(6,15,1,1,$this->links[LOCALE_LANG_DATE])
			                );
			break;

		case SnortReport::TYPE_CLASS:
			$ndx = SnortReport::TYPE_ALERT;
			$labels = 2;
			$hits = 5;
			$sum = true;
			$nested = true;
			$legendfields = array(
			                    SNORT_LANG_ID => array(0,10,1,0,"detail[".$this->links[$type]."]"),
			                    SNORT_LANG_TYPE_ALERT => array(1,60,0,0),
			                    SNORT_LANG_HITS => array(5,15,0,0),
			                    LOCALE_LANG_DATE => array(6,15,1,1,$this->links[LOCALE_LANG_DATE])
			                );
			break;

		case SnortReport::TYPE_PRI:
			$ndx = SnortReport::TYPE_ALERT;
			$labels = 3;
			$hits = 5;
			$sum = true;
			$legendfields = array(
			                    SNORT_LANG_TYPE_PRI => array(3,50,1,0,"detail[".$this->links[$type]."]"),
			                    SNORT_LANG_HITS => array(5,50,0,0)
			                );
			$graphtype = "pie";
			$y = 200;
			break;

		case SnortReport::TYPE_PROTO:
			$ndx = SnortReport::TYPE_ALERT;
			$labels = 4;
			$hits = 5;
			$legendfields = array(
			                    SNORT_LANG_TYPE_PROTO => array(4,50,1,0,"detail[".$this->links[$type]."]"),
			                    SNORT_LANG_HITS => array(5,50,0,0)
			                );
			$sum = true;
			$graphtype = "pie";
			$y = 200;
			break;

		case SnortReport::TYPE_DATE:
			$legendfields = array(
			                    LOCALE_LANG_DATE => array(0,25,1,0,$this->links[$type]),
			                    SNORT_LANG_HITS => array(1,25,0,0)
			                );
			$graphtype = "column";
			break;

		case SnortReport::TYPE_ATTACK:
			$legendfields = array(
			                    SNORT_LANG_TYPE_ATTACK => array(0,10,1,0,"detail[".$this->links[$type]."]"),
			                    SNORT_LANG_HITS => array(1,10,0,0),
			                    LOCALE_LANG_DATE => array(2,80,1,1,$this->links[LOCALE_LANG_DATE])
			                );
			break;

		case SnortReport::TYPE_VICTIM:
			$legendfields = array(
			                    SNORT_LANG_TYPE_VICTIM => array(0,10,1,0,"detail[".$this->links[$type]."]"),
			                    SNORT_LANG_HITS => array(1,10,0,0),
			                    LOCALE_LANG_DATE => array(2,80,1,1,$this->links[LOCALE_LANG_DATE])
			                );
			break;

		case SnortReport::TYPE_PORT:
			$legendfields = array(
			                    SNORT_LANG_TYPE_PORT => array(0,10,1,0,"detail[".$this->links[$type]."]"),
			                    SNORT_LANG_HITS => array(1,10,0,0),
			                    LOCALE_LANG_DATE => array(2,80,1,1,"detail[".$this->links[LOCALE_LANG_DATE]."]")
			                );
			$graphtype = "pie";
			$y = 200;
		}

		return array(
		           'ndx' => $ndx,
		           'labels' => $labels,
		           'hits' => $hits,
		           'legendfields' => $legendfields,
		           'sum' => $sum,
		           'nested' => $nested,
		           'graphtype' => $graphtype,
		           'x' => $x,
		           'y' => $y
		       );
	}


	/**
	 * Load report data files.
	 *
	 * @access private
	 * @return boolean   false on error;otherwise true
	 */

	function _Load()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$spyware = $this->spyware;
		$month = $this->month;
		$day = $this->day;

		if ($day == 0)
			$ndxs = array("sids.ndx", "src.ndx", "dst.ndx", "ports.ndx", "dates.ndx");
		else
			$ndxs = array("$day/sids", "$day/src", "$day/dst", "$day/ports", "$day/count");

		$now = strtotime("now");
		$year = date("Y");

		foreach ($ndxs as $key => $ndx) {

			$filename = $this->dir . $month . "/" . $ndx;

			try {
				$file = new File($filename);
				if (! $file->Exists()) {
					// TODO: make this a special exception?
					throw new EngineException(REPORT_LANG_NO_STATS, COMMON_WARNING);
				}

				$contents = $file->GetContentsAsArray();
				unset($file);
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			$sids = false;
			$count = 0;

			foreach ($contents as $line) {
				$data = explode("|",$line);
				$data = array_map("trim",$data);

				switch ($key) {

				case 0:
					// id|alert|class|pri|count|days
					$parts = explode(":",$data[0]);
					$sid = $parts[1];
					$isvalid = false;

					switch ($spyware) {

					case true:
						if ($sid > self::SPYWARESID)
							$isvalid = true;

					default:
						if ($sid < self::SPYWARESID)
							$isvalid = true;
					}

					unset($data[0]);

					if ($isvalid)
						$this->logs[$month][$day][SnortReport::TYPE_ALERT][$sid]=$data;

					break;

				case 1:
					$ip = $data[0];
					unset($data[0]);
					$this->logs[$month][$day][SnortReport::TYPE_ATTACK][$ip]=$data;
					break;

				case 2:
					$ip = $data[0];
					$this->logs[$month][$day][SnortReport::TYPE_VICTIM][$ip]=$data;
					break;

				case 3:
					$port = $data[0];
					unset($data[0]);
					$this->logs[$month][$day][SnortReport::TYPE_PORT][$port]=$data;
					break;

				case 4:
					$reportday = $data[0];
					$logdate = strtotime("$reportday ".substr($month, 0, -1)." $year");

					if ($logdate > $now) {
						$year--;
						$logdate = strtotime("$reportday ".substr($month, 0, -1)." $year");
					}

					$data[] = $logdate;
					unset($data[0]);
					$count += $data[1];
					$this->logs[$month][$day][SnortReport::TYPE_DATE][$reportday]=$data;
					break;
				}
			}
		}

		ksort($this->logs[$month][$day][SnortReport::TYPE_DATE]);

		if ($count > 0) {
			return true;
		} else {
			if ($day == 0 )
				$day ='';

			// TODO: make this a special exception
			throw new EngineException(REPORT_LANG_NO_STATS, COMMON_WARNING);
		}
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
