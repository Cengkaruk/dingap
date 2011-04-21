<?php

/**
 * Header handler for the ClearOS Enterprise mobile theme.
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

//////////////////////////////////////////////////////////////////////////////
// P A G E  L A Y O U T
//////////////////////////////////////////////////////////////////////////////

function theme_page($page)
{
    if ($page['type'] == MY_Page::TYPE_CONFIGURATION)
        return _configuration_page($page);
    else if ($page['type'] == MY_Page::TYPE_REPORT)
        return _configuration_page($page);
    else if ($page['type'] == MY_Page::TYPE_SPLASH)
        return _configuration_page($page);
    else if ($page['type'] == MY_Page::TYPE_WIZARD)
        return _configuration_page($page);

/*
        return _report_page($page);
        return _splash_page($page);
        return _wizard_page($page);
*/
}

/**
 * Returns the configuration type page.
 *
 * @param array $page page data
 *
 * @return string HTML output
 */

function _configuration_page($page)
{
    $html = "
<!-- Body -->
<body>

<div data-role='page' data-theme='b' id='theme-page' class='theme-page-container'> 
    <div data-role='header'>
        <h1>" . $page['title'] . "</h1>
        <a href='#menu' data-icon='gear' class='ui-btn-right'>" . lang('base_menu') . "</a>
    </div>
    <div data-role='content'>


<!-- Content --> 
";
    $html .= $page['app_view'];

    $menu_items = '';

    // Loop through to build menu
    //---------------------------

    $top_menu = '';
    $category_first_item = TRUE;
    $subcategory_first_item = TRUE;
    $current_category = '';
    $current_subcategory = '';
    $category_count = 0;

    foreach ($page['menus'] as $url => $page_info) {

        // Category transition
        //--------------------

        if ($page_info['category'] != $current_category) {

            // Don't close top menu category on first run
            //-------------------------------------------

            if (! $category_first_item) {
                $top_menu .= "\t\t\t\t\t</ul>\n";
                $top_menu .= "\t\t\t\t</li>\n";
                $top_menu .= "\t\t\t</ul>\n";
                $top_menu .= "\t\t</li>\n";
            }

            // Top Menu
            //---------

            $top_menu .= "\t\t<li>" . $page_info['category'] . "\n";
            $top_menu .= "\t\t\t<ul>\n";

            $current_category = $page_info['category'];
            $category_first_item = FALSE;
            $subcategory_first_item = TRUE;
        }

        // Subcategory transition
        //-----------------------

        if ($current_subcategory != $page_info['subcategory']) {

            if (! $subcategory_first_item) {
                $top_menu .= "\t\t\t\t\t</ul>\n";
                $top_menu .= "\t\t\t\t</li>\n";
            }

            $top_menu .= "\t\t\t\t<li>" . $page_info['subcategory'] . "\n";
            $top_menu .= "\t\t\t\t\t<ul>\n";

            $current_subcategory = $page_info['subcategory'];
            $subcategory_first_item = FALSE;
        }

        // Page transition
        //----------------

        $top_menu .= "\t\t\t\t\t\t<li><a href='" . $url . "'>" . $page_info['title'] . "</a><li>\n";
    }

    // Close out open HTML tags
    //-------------------------

    $top_menu .= "\t\t\t\t\t</ul>\n";
    $top_menu .= "\t\t\t\t</li>\n";
    $top_menu .= "\t\t\t</ul>\n";
    $top_menu .= "\t\t</li>\n";

    // Footer links
    //-------------

    $links = "<a href='/app/base/theme/set/clearos6x' data-role='button' data-icon='gear' rel='external'>" . lang('base_full_view') . "</a>";

    $html .= "

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
        <ul data-role='listview' data-theme='g'>
$top_menu
        </ul>
    </div>

    <div data-role='footer' class='ui-bar'>
        $links
    </div>
</div>


</body>
</html>
";

    return $html;
}
