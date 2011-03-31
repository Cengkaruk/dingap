<?php

/**
 * Header handler for the ClearOS Enterprise theme.
 *
 * @category  Theme
 * @package   ClearOS_Enterprise
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
 * Returns the webconfig page.
 *
 * This class provides the mechanism for managing the type and look & feel
 * and layout of a webconfig page.  The following elements need to be handled:
 *
 * - Content
 * - Banner
 * - Footer
 * - Status Area
 * - Menu
 * - Help Box
 * - Summary Box 
 * - Report Box
 * 
 * We don't want a menu system showing up on something like the login page!
 * The app developer can specify one of four different page types.  It's up
 * to you how to lay them out of course.
 *
 * - Configuration - this contains all elements
 *   - content, banner, footer, status, menu, help, summary, report
 *
 * - Report - reports need more real estate, so summary and report elements are omitted
 *   - content, banner, footer, status, menu, help    
 *
 * - Splash - minimalist page (e.g. login)
 *    - content, status
 * 
 * - Wizard 
 *    - content, status, help, summary (?)
 *
 * @return string HTML output
 */

//////////////////////////////////////////////////////////////////////////////
// P A G E  L A Y O U T
//////////////////////////////////////////////////////////////////////////////

function theme_page($page)
{
    if ($page['type'] == MY_Page::TYPE_CONFIGURATION)
        return _configuration_page($page);
    else if ($page['type'] == MY_Page::TYPE_REPORT)
        return _report_page($page);
    else if ($page['type'] == MY_Page::TYPE_SPLASH)
        return _splash_page($page);
    else if ($page['type'] == MY_Page::TYPE_WIZARD)
        return _wizard_page($page);
}

/**
 * Returns the configuration page.
 *
 * @param array $page page data   
 *
 * @return string HTML output
 */   

function _configuration_page($page)
{
    $menus = _get_menu($page['menus']);

    return "
<!-- Body -->
<body>

<!-- Page Container -->
<div id='theme-page-container'>" .

    _get_banner($page, $menus) . "

    <!-- Main Content Container -->
    <div id='theme-main-content-container'>
        <div class='theme-main-content-top'>
		<div class='green-stroke-top'></div>
		<div class='green-stroke-left'></div>
		<div class='green-stroke-right'></div>
		</div>
		<div class='theme-core-content'>
		" .
            _get_left_menu($page, $menus) .
            _get_app($page) .
            _get_sidebar($page) .
        "
		</div>
		" .
		_get_footer($page) .
		"
    </div>
</div>
</body>
</html>
";
}

/**
 * Returns the report page.
 *
 * @param array $page page data   
 *
 * @return string HTML output
 */   

function _report_page($page)
{
    $menus = _get_menu($page['menus']);

    return "
<!-- Body -->
<body>

<!-- Page Container -->
<div id='theme-page-container'>" .

    _get_banner($page, $menus) . "

    <!-- Main Content Container -->
    <div id='theme-main-content-container'>
        <div class='theme-main-content-top'>
		<div class='green-stroke-top'></div>
		<div class='green-stroke-left'></div>
		<div class='green-stroke-right'></div>
		</div>
		<div class='theme-core-content'>
		" .
            _get_left_menu($page, $menus) .
            _get_app($page) .
        "
		</div>
    </div>
</div>
</body>
</html>
";
}

/**
 * Returns the splash page.
 *
 * @param array $page page data   
 *
 * @return string HTML output
 */   

function _splash_page($page)
{
    return "
<!-- Body -->
<body>

<!-- Page Container -->
<div id='theme-page-container'>
    <!-- Main Content Container -->
    <div id='theme-main-content-container'>
        <div class='theme-main-content-top'>
		<div class='green-stroke-top'></div>
		<div class='green-stroke-left'></div>
		<div class='green-stroke-right'></div>
		</div>
		<div class='theme-core-content'>
		" .
            _get_app($page) .
        "
		</div>
    </div>
</div>
</body>
</html>
";
}

/**
 * Returns the wizard page.
 *
 * @param array $page page data   
 *
 * @return string HTML output
 */   

function _wizard_page($page)
{
    return "
<!-- Body -->
<body>

<!-- Page Container -->
<div id='theme-page-container'>" .

    _get_splash_banner($page) . "

    <!-- Main Content Container -->
    <div id='theme-main-content-container'>
        <div class='theme-main-content-top'>
		<div class='green-stroke-top'></div>
		<div class='green-stroke-left'></div>
		<div class='green-stroke-right'></div>
		</div>
		<div class='theme-core-content'>
		" .
            _get_wizard_menu($page) .
            _get_app($page) .
        "
		</div>
		" .
		_get_footer($page) .
		"
    </div>
</div>
</body>
</html>
";
}

//////////////////////////////////////////////////////////////////////////////
// L A Y O U T  H E L P E R S
//////////////////////////////////////////////////////////////////////////////

function _get_app($page)
{
    return "
        <!-- Content -->
        <div id='theme-content-container'>
		<div id='theme-content-help'>
		<div class='help-sides'>
		" . $page['page_help'] . "
		</div>
		<div class='help-bottom'></div>
		</div>
		<div id='theme-content-left'>
        " . $page['app_view'] . "
        </div>
		<div id='theme-sidebar-container'>
		<div class='sidebar-top'></div>
        " . $page['page_summary'] . "
        " . $page['page_report'] . "
		<div class='sidebar-bottom'></div>
    	</div>
		</div>
    ";
}

function _get_footer($page) 
{
    return "
    <!-- Footer -->
    <div id='theme-footer-container'>
        Web Theme - Copyright &copy; 2010, 2011 ClearFoundation. All Rights Reserved.
        <b><a href='/app/base/theme/set/clearos6xmobile'>Mobile View</a></b>
    </div>
    ";
}

function _get_sidebar($page)
{
    return "
    <!-- Sidebar -->
    
    ";
}

function _get_splash_banner($page, $menus)
{
    return "
<div id='theme-banner-container'>
    <div id='theme-banner-background'></div>
    <div id='theme-banner-logo'></div>
	<div class='name-holder'>
        <a href='/app/base/session/logout' style='color: #98bb60;'><span id='theme-banner-logout'>" . lang('base_logout') . "</span></a>
        <div id='theme-banner-fullname'>" . lang('base_welcome') . "</div>
    </div>
</div>";
}

function _get_banner($page, $menus)
{
    $top_menu = $menus['top_menu'];
    $active_category_number = $menus['active_category'];

$html = "
<!-- Banner -->
<div id='theme-banner-container'>
    <div id='theme-banner-background'></div>
    <div id='theme-banner-logo'></div>
	<div class='name-holder'>
        <a href='/app/base/session/logout' style='color: #98bb60;'><span id='theme-banner-logout'>" . lang('base_logout') . "</span></a>
        <div id='theme-banner-fullname'>" . lang('base_welcome') . "</div>
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
</div>
";

    return $html;
}

function _get_left_menu($page, $menus)
{
    $left_menu = $menus['left_menu'];

    $html = "
    <!-- Left Menu -->
    <div id='theme-left-menu-container'>
        <div id='theme-left-menu-top'></div>
        <div id='theme-left-menu'>
$left_menu
        </div>
    </div>
    ";

    return $html;
}

function _get_wizard_menu($page)
{
    $html = "
    <!-- Wizard Menu -->
    <div id='theme-left-menu-container'>
        <div id='theme-left-menu-top'></div>
        <div id='theme-left-menu'>
            Wizard menu goes here.
        </div>
    </div>
    ";

    return $html;
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
            $top_menu .= "\t\t\t<a class='sf-with-url $class' href='#' onclick=\"$('#theme-left-menu').accordion('activate', $category_count);\">" . $page['category'] . "</a>\n";

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
