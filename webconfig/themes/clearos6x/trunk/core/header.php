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
	if ($page['layout'] == 'default')
		return _header_default_layout($page);
	else if ($page['layout'] == 'splash')
		return _header_splash_layout($page);
	else if ($page['layout'] == 'wizard')
		return _header_wizard_layout($page);
}

/**
 * Template for default layout.
 */

function _header_default_layout($page)
{
	// FIXME - needs to be splash too
	if (empty($page['status_success']))
		$success = '';
	else
		$success = infobox_highlight($page['status_success']);

	$menus = _get_menu($page['menus']);

	$left_menu = $menus['left_menu'];
	$top_menu = $menus['top_menu'];
	$active_category_number = $menus['active_category'];

	$header = "
<!-- Body -->
<body>


<!-- Page Container -->
<div id='theme-page-container'>

<!-- Banner -->
<div id='theme-banner-container'>
	<div id='theme-banner-background'></div>
	<div id='theme-banner-logo'></div>
	<div id='theme-banner-fullname'>" . lang('base_welcome') . "</div>
	<div id='theme-banner-logout'><a href='/app/base/session/logout'>" . lang('base_logout') . "</a></div>
</div>

<!-- Menu Javascript -->
<script type='text/javascript'> 
	$(document).ready(function() { 
		$('#theme-top-menu-list').superfish({
			delay: 800,
			pathLevels: 0
		});
	});

	$(document).ready(function(){
		$('#theme-left-menu').accordion({ autoHeight: false, active: $active_category_number });
	});
</script>

<!-- Top Menu -->
<div id='theme-top-menu-container'>
	<ul id='theme-top-menu-list' class='sf-menu'>
$top_menu
	</ul>		
</div>

<!-- Left Menu -->
<div id='theme-left-menu-container'>
	<div id='theme-left-menu-top'></div>
	<div id='theme-left-menu'>
$left_menu
	</div>
</div>

<!-- Content -->
<div id='theme-content-container'>
";
/*

<!-- Summary Widget -->
<div class='theme-summary-box-container ui-widget'>
	<div class='clearos-summary-box-header ui-state-active ui-corner-top'>
		<div class='clearos-summary-box-title'>DHCP Server Summary</div>
	</div>
	<div>Stuff in here.
	</div>
</div>
";
*/

	$header .= $success;

	return $header;
}

/**
 * Template for splash layout.
 */

function _header_splash_layout($page)
{
	$header = "
<!-- Body -->
<body>

<!-- Page Container -->
<div id='theme-page-container'>

<!-- Banner -->
<div id='theme-banner-splash-container'> </div>

<!-- Content -->
<div id='theme-content-splash-container'>

";

	return $header;
}

/**
 * Template for wizard layout.
 */

function _header_wizard_layout($page)
{
	$header = "
<!-- Body -->
<body>

<!-- Page Container -->
<div id='theme-page-container'>

<!-- Banner -->
<div id='theme-banner-wizard-container'> </div>

<!-- Left Menu -->
<div id='theme-wizard-menu-container'>
	<div id='theme-wizard-menu-top'></div>
	<div id='theme-wizard-menu'>
$left_menu_FIXME
	</div>
</div>

<!-- Content -->
<div id='theme-content-wizard-container'>

";

	return $header;
}


///////////////////////////////////////////////////////////////////////////////
// Menu handling
///////////////////////////////////////////////////////////////////////////////

function _get_menu($menu_pages) {

	// Highlight information for given page
	//-------------------------------------	

	$highlight = array();
	$matches = array();

	preg_match('/\/app\/[^\/]*/', $_SERVER['PHP_SELF'], $matches);
	$basepage = $matches[0];

	foreach ($menu_pages as $url => $pageinfo) {
		if ($url == $basepage) {
			$highlight['page'] = $url;
			$highlight['category'] = $pageinfo['category'];
			$highlight['subcategory'] = $pageinfo['category'] . $pageinfo['subcategory'];
		}
	}

	// Loop through to build menu
	//---------------------------

	$top_menu = "";
	$left_menu = "";
	$current_category = "";
	$current_subcategory = "";
	$category_count = 0;
	$active_category_number = 0;

	foreach ($menu_pages as $url => $page) {
		
		// Category transition
		//--------------------

		if ($page['category'] != $current_category) {

			// Detect active category for given page
			//--------------------------------------

			if ($page['category'] == $highlight['category']) {
				$active_category_number = $category_count;
				$class = 'sfCurrent';
			} else {
				$class = '';
			}

			// Don't close top menu category on first run
			//-------------------------------------------

			if (! empty($top_menu)) {
				$top_menu .= "\t\t\t</ul>\n";
				$top_menu .= "\t\t</li>\n";

				$left_menu .= "\t\t\t</ul>\n";
				$left_menu .= "\t\t</div>\n";
			}

			// Top Menu
			//---------

			$top_menu .= "\t\t<li class='$class'>\n";
			$top_menu .= "\t\t\t<a class='sf-with-url $class' href='#' onclick=\"$('#theme-left-menu').accordion('activate', $category_count);\">" . $page['category'] . "<span class='sf-sub-indicator'> &#187;</span></a>\n";

			$top_menu .= "\t\t\t<ul>\n";

			// Left Menu
			//----------

			$left_menu .= "\t\t<h3 class='theme-left-menu-category'><a href='#'>{$page['category']}</a></h3>\n";
			$left_menu .= "\t\t<div>\n";
			$left_menu .= "\t\t\t<ul class='theme-left-menu-list'>\n";

			// Counters
			//---------

			$current_category = $page['category'];
			$category_count++;
		}
		
		// Subcategory transition
		//-----------------------

		if ($current_subcategory != $page['subcategory']) {
			$current_subcategory = $page['subcategory'];
			$left_menu .= "\t\t\t\t<li class='theme-left-menu-subcategory'>{$page['subcategory']}</li>\n";
			$top_menu .= "\t\t\t\t<li class='theme-top-menu-subcategory'>{$page['subcategory']}</li>\n";
		}

		// Page transition
		//----------------

		$activeClass = ($url == $highlight['page']) ? 'menu-item-active' : '';

		$top_menu .= "\t\t\t\t<li><a class='{$activeClass}' href='{$url}'>{$page['title']}</a></li>\n";
		$left_menu .= "\t\t\t\t<li class='theme-left-menu-item'><a class='{$activeClass}' href='{$url}'>{$page['title']}</a></li>\n";
	}

	// Close out open HTML tags
	//-------------------------

	$top_menu .= "\t\t\t</ul>\n";
	$top_menu .= "\t\t</li>\n";

	$left_menu .= "\t\t\t</ul>\n";
	$left_menu .= "\t\t</div>\n";

	// Return HTML formatted menu
	//---------------------------

	$menus['top_menu'] = $top_menu;
	$menus['left_menu'] = $left_menu;
	$menus['active_category'] = $active_category_number;

	return $menus;
}

// vim: syntax=php ts=4
?>
