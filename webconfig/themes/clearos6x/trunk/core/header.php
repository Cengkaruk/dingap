<?php

///////////////////////////////////////////////////////////////////////////////
// 
// Copyright 2009, 2010 ClearFoundation
//
//////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Header handler for the theme.
 * 
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

/**
 * Returns the header for the theme.
 *
 * Two types of header layouts must be supported in a ClearOS theme.  See 
 * developer documentation for details.
 *
 * @param array $page page data
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

function page_header($page)
{
	// Common to both layouts
	//-----------------------

	if ($page['layout'] == 'default') {
		return _header_default_layout($page);
	} else if ($page['layout'] == 'splash') {
		return _header_splash_layout($page);
	}
}

/**
 * Template for default layout.
 *
 * @param array $page page data
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

function _header_default_layout($page)
{
	// FIXME - needs to be splash too
	if (isset($page['status_success']))
		$success = infobox_highlight($page['status_success']);
	else
		$success = '';

	$menus = _get_menu($page['menus']);

	$left_menu = $menus['left_menu'];
	$top_menu = $menus['top_menu'];
	$active_section_number = $menus['active'];

	$header = "
<!-- Body -->
<body>


<!-- Page Container -->
<div id='clearos6x-layout-container'>


<!-- Header -->
<div id='clearos6x-layout-header'>
	<div id='clearos6x-header-background'></div>
	<div id='clearos6x-header-logo'></div>
	<div id='clearos6x-header-fullname'>" . lang('base_welcome') . "</div>
	<div id='clearos6x-header-organization'><a href='/app/base/logout'>" . lang('base_logout') . "</a></div>
</div>

<!-- Top Menu -->
<div id='clearos6x-layout-top-menu' class=''>
	<ul id='clearos6x-top-menu-list' class='sf-menu'>
		$top_menu
	</ul>		
</div>

<!-- Left Menu -->
<script type='text/javascript'> 
	$(document).ready(function() { 
		$('#clearos6x-top-menu-list').superfish({
			delay: 800,
			pathLevels: 0
		});
	});

	$(document).ready(function(){
		$('#clearos6x-left-menu').accordion({ autoHeight: false, active: $active_section_number });
	});
</script>


<div id='clearos6x-layout-left-menu'>
	<div id='clearos6x-left-menu-top'></div>
	<div id='clearos6x-left-menu'>
		$left_menu
	</div>
</div>

<!-- Content -->
<div id='clearos6x-layout-content'>
";

	$header .= $success;

	return $header;
}

/**
 * Template for splash layout.
 *
 * @param array $page page data
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

function _header_splash_layout($page)
{
	$header = "
<!-- Body -->
<body>

<!-- Page Container -->
<div id='clearos_container'>

<div id='clearos_splash'>

<table cellspacing='0' cellpadding='0' border='0' width='500' align='center'>
	<tr>
		<td>
			<br><br>
			<p align='center'><img src='" . $page['theme_url'] . "/images/logo2.png' alt='ClearOS'></p>
			<br>
		</td>
	</tr>
	<tr>
		<td>
";

	return $header;
}

///////////////////////////////////////////////////////////////////////////////
// Menu handling
///////////////////////////////////////////////////////////////////////////////
// Woah... this code got out of hand :-)  Cleanup required.

function _get_menu($menu_pages) {

	// Build menu
	//-----------

	$highlight = array();

	$matches = array();
	preg_match('/\/app\/[^\/]*/', $_SERVER['PHP_SELF'], $matches);
	$basepage = $matches[0];

	// Pick out the current pages section and subsection for menu highlighting
	foreach ($menu_pages as $url => $pageinfo) {
		if ($url == $basepage) {
			$highlight['page'] = $url;
			$highlight['section'] = $pageinfo['section'];
			$highlight['subsection'] = $pageinfo['section'] . $pageinfo['subsection'];
		}
	}

	$section = array();

	$top_menu = "";
	$left_menu = "";
	$current_section = "";
	$current_subsection = "";
	$section_count = 0;
	$active_section_number = 0;
	$sections = array();

	foreach ($menu_pages as $url => $page) {
		// section + sub-section
		$sss = $page['section'] . $page['subsection'];
		if (isset($sections[$sss])) {
			$sections[$sss]++;
		} else {
			$sections[$sss] = 1;
		}
	}

	foreach ($menu_pages as $url => $page) {
		// section + sub-section
		$sss = $page['section'] . $page['subsection'];
		
		if ($page['section'] != $current_section) {
			// Don't close top menu section on first run
			if (! empty($top_menu)) {
				$top_menu .= "\t\t\t</ul>\n";
				$top_menu .= "\t\t</li>\n";

				$left_menu .= "        </ul>\n";
				$left_menu .= "    </div>\n";
			}

			if ($page['section'] == $highlight['section']) {
				$active_section_number = $section_count;
				$top_menu .= "\t\t<li class='sfCurrent'>\n";
				$top_menu .= "\t\t\t<a class='sf-with-url sfCurrent' href='#' onclick=\"$('#clearos6x-left-menu').accordion('activate', $section_count);\">" . $page['section'] . "<span class='sf-sub-indicator'> &#187;</span></a>\n";
			} else {
				$top_menu .= "\t\t<li>\n";
				$top_menu .= "\t\t\t<a class='sf-with-url' href='#' onclick=\"$('#clearos6x-left-menu').accordion('activate', $section_count);\">" . $page['section'] . "<span class='sf-sub-indicator'> &#187;</span></a>\n";
			}

			$top_menu .= "\t\t\t<ul>\n";

			// Left menu block
			$left_menu .= "    <h3 class='left-menu-header'><a href='#'>{$page['section']}</a></h3>\n";
			$left_menu .= "    <div>\n";
			$left_menu .= "        <ul class='ui-accordion-menu-list'>\n";

			$current_section = $page['section'];
			$section_count++;
		}
		
		$activeClass = ($url == $highlight['page']) ? 'menu-item-active' : '';

		if ($current_subsection != $page['subsection']) {
			$current_subsection = $page['subsection'];
			$left_menu .= "\t\t\t\t<li class='clearos6x-left-menu-subsection'>{$page['subsection']}</li>\n";
			$top_menu .= "\t\t\t\t<li class='clearos6x-top-menu-subsection'>{$page['subsection']}</li>\n";
		}

		if ($sections[$sss] == 1) {
			$top_menu .= "\t\t\t\t<li><a class='{$activeClass}' href='{$url}'>{$page['title']}</a></li>\n";
			$left_menu .= "            <li class='ui-accordion-menu-list-item'><a class='{$activeClass}' href='{$url}'>{$page['title']}</a></li>\n";
		} else {
			$top_menu .= "\t\t\t\t<li><a class='{$activeClass}' href='{$url}'>{$page['title']}</a></li>\n";
			$left_menu .= "            <li class='ui-accordion-menu-list-item'><a class='{$activeClass}' href='{$url}'>{$page['title']}</a></li>\n";
		}
	}

	$top_menu .= "\t\t\t</ul>\n";
	$top_menu .= "\t\t</li>\n";

	$left_menu .= "\t\t\t</ul>\n";
	$left_menu .= "\t\t</div>\n";

	$menus['top_menu'] = $top_menu;
	$menus['left_menu'] = $left_menu;
	$menus['active'] = $active_section_number;

	return $menus;
}

// vim: syntax=php ts=4
?>
