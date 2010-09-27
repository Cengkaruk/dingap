<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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
require_once("../../gui/ProxyReport.class.php");
require_once("../../api/Squid.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

$customheader = "\n";
if (file_exists(COMMON_CORE_DIR.'/htdocs/include/sorttable.js'))
    $customheader .= '<script type="text/javascript" src="/include/sorttable.js"></script>';

// always add the inline-style for "sortable" tables
$customheader .= '
<style type="text/css">
<!--
/* Sortable tables */
table.sortable td{
font-size: 8pt;
}
a.sortheader, a.sortheader:visited {
	color:#006699;
	font-size: 10pt;
	font-weight: bold;
	text-decoration: none;
	}
a.sortheader:hover {
	color:#7081B1;
	font-size: 10pt;
	font-weight: bold;
		text-decoration: none;
}
th.nosort, th.sort {
	background-color:#EEEEEE;
	color:#000;
	font-size: 10pt;
	font-weight: bold;
	text-decoration: none;
	white-space: nowrap
	}
table.sortable span.sortarrow {
	color: black;
	font-size: 10pt;
	font-weight: bold;
	text-decoration: none;
	}
-->
</style>
';

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $customheader);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-proxyreport.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

try{
    list($type, $showactions) = ProcessVars($_POST);

    $report = new ProxyReport();

    if ($report->IsAvailable()) {
        $report->DisplayReportType($type);
        $report->GetFullReport($showactions);
    } else {
        WebDialogWarning(REPORT_LANG_NO_STATS);
    }
} catch (Exception $e) {
    WebDialogWarning($e->getMessage());
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

function ProcessVars($vars)
{
    if (isset($vars['groupby'])) {
        $groupby = intval($vars['groupby']);
    } else {
        $groupby = ProxyReport::GetDefaultGrouping();
    }

    $start = (isset($vars['start'])) ? intval($vars['start']) : 0 ;
    if (isset($vars['first']))
        $start = 0;

    $type  = (isset($vars['type'])) ? $vars['type'] : 'summary';
    if (! array_key_exists($type,ProxyReport::GetReportTypes()))
        throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID.':'.REPORT_LANG_REPORT_TYPE);

    if (isset($vars['search']))
        $type = 'search';

    if ($type=='search') {
        $limit = (isset($vars['limit'])) ? intval($vars['limit']) : 300 ;
    } else {
        $limit = (isset($vars['limit'])) ? intval($vars['limit']) : 30 ;
        if ($limit == 300)
            $limit = 30;
    }

    $max = (isset($vars['max'])) ? intval($vars['max']) : $limit ;
    $prev = $start - $limit;
    $next = $start + $limit;

    if (isset($vars['prev'])) {
        $start = ($prev > 0) ? $prev : 0;
    } else {
        if (isset($vars['next']))
            $start = $next;
    }

    if (isset($vars['end'])) {
        $start = intval($max/$limit)*$limit;
        if ($start == $max)
            $start -= $limit;
    }

    if (isset($vars['period'])) {
        $period = intval($vars['period']);
    } else {
        $period = ($type == "summary") ? 0 : 2;
    }

    switch ($type) {
    case 'search':
        $search = $vars['search'];
        $startdate = date("Y-m-d");
        $enddate = $startdate;
        $status = 0;
        $filter_code = ProxyReport::CONSTANT_FILTER_CODE_ALL;
        $md5 = 0;
        if (isset($search['startdate'])) {
            if (is_array($search['startdate'])) {
                $startdate = implode('-',$search['startdate']);
            }
            unset($search['startdate']);
        }
        if (isset($search['enddate'])) {
            if (is_array($search['enddate'])) {
                $enddate = implode('-',$search['enddate']);
            }
            unset($search['enddate']);
        }
        if (isset($search['status'])) {
            $status = intval($search['status']);
            unset($search['status']);
        }
        if (isset($search['filter_code'])) {
            $filter_code= $search['filter_code'];
            unset($search['filter_code']);
        }
        if (isset($vars['md5'])) {
            if (ctype_xdigit($vars['md5']))
                $md5 = $vars['md5'];
        }
        $showactions = array(
                           'type' => $type,
                           $type => array(
                                      'filter' => $search,
                                      'filter_code' => $filter_code,
                                      'daterange' => array($startdate, $enddate),
                                      'status' => $status,
                                      'start' => $start,
                                      'md5' => $md5
                                  )
                       );
        break;
    default:
        $showactions = array(
                           'type' => $type,
                           $type => array(
                                      'groupby' => $groupby,
                                      'period' => $period,
                                  )
                       );
    }

    return array($type, $showactions);
}
// vim: syntax=php ts=4
?>
