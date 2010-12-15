<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
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
 * Head handler for the theme.
 * 
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

/**
 * Returns required <head> contents for the theme.
 * 
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

function page_head($theme_path)
{
	return "
<!-- Theme Favicon -->
<link href='$theme_path/images/favicon.ico' rel='shortcut icon' >

<!-- Theme Style Sheets -->
<link type='text/css' href='$theme_path/css/theme.css' rel='stylesheet'>
<link type='text/css' href='$theme_path/css/jquery.mobile-1.0a2.min.css' rel='stylesheet'>

<!-- Theme Javascript -->
<script type='text/javascript' src='$theme_path/js/jquery.mobile-1.0a1.min.js'></script>
<!-- script type='text/javascript' src='$theme_path/js/jquery.mobile-1.0a2.min.js'></script -->

";
}

// vim: syntax=php ts=4
?>
