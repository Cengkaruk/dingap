<?php

///////////////////////////////////////////////////////////////////////////////
// Menu handling
///////////////////////////////////////////////////////////////////////////////

// Build menu
//-----------

$menu_pages = WebMenuFetch();

print_r($menu_pages);

$highlight = array();

$matches = array();
preg_match('/\/app\/[^\/]*/', $_SERVER['PHP_SELF'], $matches);
$basepage = $matches[0];

// Pick out the current pages section and subsection for menu highlighting
foreach ($menu_pages as $pageinfo) {
    if ($pageinfo['url'] == $basepage) {
		$highlight['page'] = $pageinfo['url'];
		$highlight['section'] = $pageinfo['section'];
		$highlight['subsection'] = $pageinfo['section'] . $pageinfo['subsection'];
    }
}

$section = array();

$topmenu = "";
$leftmenu = "";
$current_section = "";
$current_subsection = "";
$section_count = 0;
$active_section_number = 0;
$sections = array();

foreach ($menu_pages as $page) {
	// section + sub-section
	$sss = $page['section'] . $page['subsection'];
	if (isset($sections[$sss])) {
		$sections[$sss]++;
	} else {
		$sections[$sss] = 1;
	}
}

foreach ($menu_pages as $page) {
	// section + sub-section
	$sss = $page['section'] . $page['subsection'];
	
	if ($page['section'] != $current_section) {
		// Don't close top menu section on first run
		if (! empty($topmenu)) {
			$topmenu .= "    </ul>\n";
			$topmenu .= "</li>\n";

			$leftmenu .= "        </ul>\n";
			$leftmenu .= "    </div>\n";
		}

		// Top menu block
		if ($page['section'] == $highlight['section']) {
			$active_section_number = $section_count;
			$topmenu .= "<li class='sfCurrent'>\n";
			$topmenu .= "    <a class='sf-with-url sfCurrent' href='#' onclick=\"$('#clearos6x-left-menu').accordion('activate', $section_count);\">" . $page['section'] . "<span class='sf-sub-indicator'> &#187;</span></a>\n";
		} else {
			$topmenu .= "<li>\n";
			$topmenu .= "    <a class='sf-with-url' href='#' onclick=\"$('#clearos6x-left-menu').accordion('activate', $section_count);\">" . $page['section'] . "<span class='sf-sub-indicator'> &#187;</span></a>\n";
		}
		$topmenu .= "    <ul>\n";

		// Left menu block
		$leftmenu .= "    <h3 class='left-menu-header'><a href='#'>{$page['section']}</a></h3>\n";
		$leftmenu .= "    <div>\n";
		$leftmenu .= "        <ul class='ui-accordion-menu-list'>\n";

		$current_section = $page['section'];
		$section_count++;
	}
	
	$activeClass = ($page['url'] == $highlight['page']) ? 'menu-item-active' : '';

	if ($current_subsection != $page['subsection']) {
		$current_subsection = $page['subsection'];
		$leftmenu .= "            <li class='clearos6x-left-menu-subsection'>{$page['subsection']}</li>\n";
		$topmenu .= "                <li class='clearos6x-top-menu-subsection'>{$page['subsection']}</li>\n";
	}

	if ($sections[$sss] == 1) {
		$topmenu .= "                <li><a class='{$activeClass}' href='{$page['url']}'>{$page['title']}</a></li>\n";
		$leftmenu .= "            <li class='ui-accordion-menu-list-item'><a class='{$activeClass}' href='{$page['url']}'>{$page['title']}</a></li>\n";
	} else {
		$topmenu .= "                <li><a class='{$activeClass}' href='{$page['url']}'>{$page['title']}</a></li>\n";
		$leftmenu .= "            <li class='ui-accordion-menu-list-item'><a class='{$activeClass}' href='{$page['url']}'>{$page['title']}</a></li>\n";
	}
}

$topmenu .= "        </ul>\n";
$topmenu .= "    </li>\n";

$leftmenu .= "        </ul>\n";
$leftmenu .= "    </div>\n";

?>
