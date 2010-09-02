<?php

// vim: syntax=php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2004 Point Clark Networks.
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

require_once(dirname(__FILE__) . '/../common/Globals.inc.php');

///////////////////////////////////////////////////////////////////////////////
//
// WebSmallChartHorizontalBar()
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Displays a horizontal bar chart.
 * Requires JQPlot (www.jqplot.com/)
 *
 * @param array $data   chart information
   [height] => ###px
   [showlegend] => 1
   [series] => Array
      [0] => Array
         [x] => S1-x 
         [color] => COLOR1
         [description] => Series 1
         [y] => S1-y
      [1] => Array
         [x] => S2-x
         [color] => COLOR2
         [description] => Series 2
         [y] => S1-y
    [width] => ###px
    [chartid] => chart1
    [xaxis] => Array
       [max] => ##
       [label] => Label 1
       [min] => #
       [format] => %d

 * @return string
 */

function WebSmallChartHorizontalBar($data)
{
	$html = "<div id='" . $data['chartid'] . "' style='height:" . $data['height']. "; width:" . $data['width'] . ";'></div>";
	$seriesdata = "";
	$index = 1;
	$isfirst = true;
	$legendinfo = "";
	foreach ($data['series'] as $series) {
		if ($data['showlegend']) {
			// Legend is reversed so it is ordered with bars from top to bottom
			$legendinfo = "<div" . ($index == count($data['series']) ? " style='margin: 3 0 1 0'" : "") . ">" .
				"<span style='background-color:" . $series['color'] . "'>&#8195;</span>&#8195;" . $series['description'] . "</div>" . $legendinfo;
		}
		$seriesdata .= "line$index = [[" . $series['x'] . "," . $series['y'] . "]];\n"; 
		$seriesvar .= ($index > 1 ? "," : "") . "line$index";
		$seriescolor .= "{color:'" . $series['color'] . "'}" . ($index == 1 ? ",\n" : "\n");
		$index++;
	}
	if ($data['showlegend'])
		$html .= "<div id='$legend' style='font-size: 7pt; padding-left: 5'>$legendinfo</div>";
	$html .= "
	<script type='text/javascript'>
	  $(document).ready(function() {\n" . 
            $seriesdata . "
	    plot1 = $.jqplot('" . $data['chartid'] . "', [$seriesvar], {
	      title:{show:false},
	      legend:{show:false},
	      seriesDefaults:{
		renderer:$.jqplot.BarRenderer, 
		rendererOptions:{barDirection:'horizontal', barPadding: 6, barMargin:15,shadowOffset: 1, shadowDepth: 4}, 
		shadowAngle:45},
	      series:[" . 
		$seriescolor . "
	      ],
	      grid:{borderWidth: 0.5,shawdowWidth: 2, shadowDepth:2},
	      axes:{
		xaxis:{
		  min:" . $data['xaxis']['min'] . ",
		  max:" . $data['xaxis']['max'] . ",
		  label:'" . $data['xaxis']['label'] . "',
		  tickOptions:{formatString:'" . $data['xaxis']['format'] . "'}
		},
		yaxis:{
		    tickOptions:{show:false},
		    showLabel: false,
		    showTicks: false,
		    angle: 45
		}
	      }
	    });
	  });
	</script>
	";
	return $html;
}

///////////////////////////////////////////////////////////////////////////////
//
// WebSmallChartPie()
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Displays a pie chart.
 * Requires JQPlot (www.jqplot.com/)
 *
 * @param array $data   chart information
   [height] => ###px
   [width] => ###px
   [showlegend] => 1
   [series] => Array
      [0] => Array
         [value] => ### 
         [color] => COLOR1
         [description] => Slice 1
      [1] => Array
         [value] => ###
         [color] => COLOR2
         [description] => Slice 2
    [chartid] => chart1

 * @return string
 */

function WebSmallChartPie($data)
{
	$html = "<div id='" . $data['chartid'] . "' style='height:" . $data['height'] . "; width:" . $data['width'] . ";'></div>";
	$seriesdata = "";
	$index = 1;
	$isfirst = true;
	$legendinfo = "";
	foreach ($data['series'] as $series) {
		if ($data['showlegend']) {
			$legendinfo .= "<div style='margin: 1 0 1 0;'>" .
				"<span style='background-color:" . $series['color'] . "'>&#8195;</span>&#8195;" . $series['description'] . "</div>";
		}
		$seriesdata .= "['" . $series['description'] . "'," . $series['value'] . "]" . ($index < count($data['series']) ? "," : ""); 
		$seriescolor .= "'" . $series['color'] . "'" . ($index < count($data['series']) ? "," : "");
		$index++;
	}
	if ($data['showlegend'])
		$html .= "<div id='$legend' style='font-size: 7pt; width: " . $data['width'] . "; padding-left: 2'>$legendinfo</div>";
	$html .= "
	<script type='text/javascript'>
	  $(document).ready(function() {
            line1 = [" . $seriesdata . "];
	    plot1 = $.jqplot('" . $data['chartid'] . "', [line1], {
	      title:{show:true},
	      legend:{show:false},
	      seriesDefaults:{
		renderer:$.jqplot.PieRenderer, 
		rendererOptions:{shadowDepth: 3, diameter:" . ereg_replace("px", "", $data['height']) . " * 0.6, sliceMargin:1}, 
		shadowAngle:45},
	      seriesColors:[" . $seriescolor . "],
	    });
	  });
	</script>
	";
	return $html;
}
// vim: syntax=php ts=4
?>
