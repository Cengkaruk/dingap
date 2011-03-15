<?php

/**
 * Head tags handler for the ClearOS Enterprise mobile theme.
 *
 * @category  Theme
 * @package   ClearOS_Enterprise_Mobile
 * @author    ClearFoundation <developer@clearfoundation.com>
 * @copyright 2011 ClearFoundation
 * @license   http://www.gnu.org/copyleft/lgpl.html GNU General Public License version 3 or later
 * @link      http://www.clearfoundation.com/docs/developer/theming/
 */

//////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Returns additional <head> data required for the theme.
 *
 * @param string $theme_path them path
 *
 * @return string HTML output
 */

function theme_page_head($theme_path)
{
    return "
<!-- Theme Favicon -->
<link href='$theme_path/images/favicon.ico' rel='shortcut icon' >

<!-- Theme Style Sheets -->
<link type='text/css' href='$theme_path/css/jquery-ui-1.8.5.custom.css' rel='stylesheet'>
<link type='text/css' href='$theme_path/css/jquery.jqplot.css'>
<link type='text/css' href='$theme_path/css/theme.css' rel='stylesheet'>
<link type='text/css' href='$theme_path/css/jquery.mobile-1.0a3.min.css' rel='stylesheet'>

<!-- Theme Javascript -->
<script type='text/javascript' src='$theme_path/js/jquery-ui-1.8.5.custom.min.js'></script>
<!--[if lt IE 9]><script type='text/javascript' src='$theme_path/js/jqplot/excanvas.js'></script><![endif]-->
<script type='text/javascript' src='$theme_path/js/jqplot/jquery.jqplot.min.js'></script>
<script type='text/javascript' src='$theme_path/js/jqplot/plugins/jqplot.barRenderer.min.js'></script>
<script type='text/javascript' src='$theme_path/js/jqplot/plugins/jqplot.pieRenderer.min.js'></script>
<script type='text/javascript' src='$theme_path/js/jquery.mobile-1.0a3.min.js'></script>
<script type='text/javascript' src='$theme_path/js/theme.js'></script>

";
}
