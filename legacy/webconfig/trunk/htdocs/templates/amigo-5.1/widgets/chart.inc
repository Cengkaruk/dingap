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

// Variables
//
// $title - chart title
// $type - chart type: bar, stacked bar, column
// $width - width
// $height - height
// $data
// $series_color
// $bgcolor
// $explode
// $url - URL for linking chart

// TODO: $id should be passed into this function 
if (empty($id))
	$id = "chart_" . rand();

// The variable for generating charts must be unique

echo "<script type='text/javascript' src='/templates/amigo-5.1/widgets/charts/FusionCharts/FusionCharts.js'></script>";

// Include FusionCharts PHP Class
require_once('charts/Class/FusionCharts_Gen.php');

// Set the chart type, width and height
if ($type == "bar")
	$fctype = "Bar2D";
else if ($type == "stacked bar")
	$fctype = "StackedBar2D";
else if ($type == "column")
	$fctype = "Column3D";
else
	$fctype = "Column3D";

// TODO: add an ID to WebChart()
// TODO: height must be specified in the PHP code.  Hack a default for now
$height = (empty($height)) ? "250" : $height;
$FC = new FusionCharts($fctype, $width, $height, $id, true);

// Set the path of the swf file
$FC->setSWFPath("/templates/amigo-5.1/widgets/charts/FusionCharts/");

// Set chart attributes
$FC->setChartParam("caption", $title);
$FC->setChartParam("bgAlpha", 0);

// Add chart data
// TODO: the $data array is a bit odd... clean it up
if ($type == "bar") {
	$labels = $data[0];
	$values = $data[1];

	for ($i = 1; $i < sizeof($values); $i++)
		$FC->addChartData($values[$i], "name=" . $labels[$i]);
} else {
	$FC->setChartParam("showValues", 0);
	$categories = $data[0];

	for ($i = 1; $i < sizeof($categories); $i++)
		$FC->addCategory($categories[$i]);

	for ($i = 1; $i < sizeof($data); $i++) {
		$dataset = $data[$i];
		for ($j = 0; $j < sizeof($dataset); $j++) {
			if ($j == 0) {
				$FC->addDataset($dataset[$j]);
			} else {
				$FC->addChartData($dataset[$j], "name=" . $categories[$j]);
			}
		}
	}
}

// Render chart
$FC->renderChart();
