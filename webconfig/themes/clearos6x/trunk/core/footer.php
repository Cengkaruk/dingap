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
	// FIXME: change to constant value

	if ($page['layout'] == 'default') {
		$footer = "


<!-- Footer -->
	</div>
	<div id='clearos6x-layout-footer'>
		Web Theme - Copyright &copy; 2010, 2011 ClearFoundation. All Rights Reserved.
		<b><a href='/app/base/theme/set/clearos6xmobile'>Mobile View</a></b>
	</div>
</div>
</body>
</html>
";
	} else if ($page['layout'] = 'splash') {
		$footer = "


<!-- Footer -->
				</td>
			</tr>
		</table>
	</div>
</div>
</body>
</html>
";
	}

	// FIXME: what to do on invalid layout?

	return $footer;
}

// vim: syntax=php ts=4
?>
