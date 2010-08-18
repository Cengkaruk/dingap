<?php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////
/**
 * Privledge Management Classes for Webconfig
 *
 * @package Gui
 * @subpackage WebUser
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 * @version  4.0, 1-14-2006
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C O N S T A N T S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////
/**
 * The base WebUser class with no privledges
 *
 * @package Gui
 * @subpackage WebUser
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 * @version  4.0, 1-14-2006
 *
 */
abstract class WebUser
{
    ///////////////////////////////////////////////////////////////////////////////
    // F I E L D S
    ///////////////////////////////////////////////////////////////////////////////
    /**
     * WebUsername
     *
     * @var string
     */
    private $name = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
    function __construct($name)
    {
        $this->name = $name;
    }

    function getName()
    {
        return $this->name;
    }
    function CanViewPage($page)
    {
        return false;
    }
}

/**
 * A WebUser class with minimal privledges.
 *
 * @package Gui
 * @subpackage WebUser
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 * @version  4.0, 1-14-2006
 *
 * @todo impliment class
 */

class GuestWebUser extends WebUser
{
    ///////////////////////////////////////////////////////////////////////////////
    // F I E L D S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
}
/**
 * A WebUser class with privledges based on assignment by admin.
 *
 * @package Gui
 * @subpackage WebUser
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 * @version  4.0, 1-14-2006
 *
 * @todo impliment class
 */

class SubAdminWebUser extends WebUser
{
    ///////////////////////////////////////////////////////////////////////////////
    // F I E L D S
    ///////////////////////////////////////////////////////////////////////////////
    private $pages = array();
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
    function __construct($name,$pages)
    {
        $this->__construct($name);
        $this->pages = $pages;
    }

    /**
     * Page View Validation
     *
     * @param string $page
     * @return boolean
     */
    function CanViewPage($page)
    {
        return in_array($page,$this->pages);
    }

}
/**
 * A WebUser class with unrestricted privledges.
 *
 * @package Gui
 * @subpackage WebUser
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 * @version  4.0, 1-14-2006
 *
 * @todo impliment class
 */
class AdminWebUser extends WebUser
{
    ///////////////////////////////////////////////////////////////////////////////
    // F I E L D S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
    function CanViewPage($page)
    {
        return true;
    }

}
// vim: syntax=php ts=4
?>
