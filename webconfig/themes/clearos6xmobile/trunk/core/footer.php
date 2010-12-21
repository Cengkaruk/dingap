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
 * Footer handler for the theme.
 * 
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

/**
 * Returns the footer for the theme.
 *
 * Two types of footer layouts must be supported in a ClearOS theme.  See 
 * developer documentation for details.
 *
 * @param array $page page data
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

function page_footer($page)
{
	$menu_items = '';

	foreach ($page['menus'] as $url => $detail) {
		$menu_items .= "\n\t\t\t<li><a rel='external' href='" . $url . "'>" . $detail['title'] . "</a></li>";
	}
/*
	$menu_items = "
	<li><a href='acura.html'>Acura</a></li>
	<li data-role='list-divider'>Audi3</li>
	<li><a href='bmw.html'>BMW</a></li>
	";
*/

	$links = "<a href='/app/base/theme/set/clearos6x' data-role='button' data-icon='gear' rel='external'>" . lang('base_full_view') . "</a>";

	$footer = "

<!-- Footer --> 
	</div>
	<div data-role='footer' class='ui-bar'>
		$links
	</div>
</div>


<!-- Menu --> 
<div data-role='page' data-theme='b' id='menu' class='theme-page-container'>
	<div data-role='header'>
		<h1>Menu</h1>
	</div>

	<div data-role='content'>	
		<ul data-role='listview' data-theme='g'>$menu_items
		</ul>
	</div>

	<div data-role='footer' class='ui-bar'>
		$links
	</div>
</div>


</body>
</html>
";

	return $footer;
}

// vim: syntax=php ts=4
?>
