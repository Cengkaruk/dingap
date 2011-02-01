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

/** 
 * Returns the header content.
 *
 * This function returns all the HTML that comes before the page content.
 * - Banner
 * - Menu system
 * - Other standard widgets
 *
 * @return string HTML output
 */

function theme_page_header($page)
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

    $header = "
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

    $header .= $success;

    return $header;
}

/**
 * Template for splash layout.
 *
 * @param array $page page data
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

function _header_splash_layout($page)
{
// FIXME
    $header = "
";

    return $header;
}
