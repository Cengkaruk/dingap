<?php

/**
 * OpenLDAP plugin driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

///////////////////////////////////////////////////////////////////////////////
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

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\openldap_directory;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('groups');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap_directory\Group_Driver as Group_Driver;

clearos_load_library('base/Engine');
clearos_load_library('openldap_directory/Group_Driver');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP plugin driver.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Plugin_Driver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $plugin_name = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Plugin constructor.
     *
     * @param string $plugin plugin name.
     *
     * @return void
     */

    public function __construct($plugin)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->plugin_name = $plugin . '_plugin';
    }

    /**
     * Adds a plugin to the system.
     *
     * @param string $description group description
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add($members = array())
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        $group->add($description, $members);
    }

    /**
     * Adds a member to a plugin group.
     *
     * @param string $username username
     *
     * @return FALSE if user was already a member
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_member($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        // FIXME - change description in add() to something better
        if (! $group->exists())
            $group->add($this->plugin_name);

        $group->add_member($username);
    }

    /**
     * Deletes a plugin group from the system.
     *
     * @return void
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function delete()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        $group->delete();
    }

    /**
     * Deletes a member from a plugin group.
     *
     * @param string $username username
     *
     * @return FALSE if user was already not a member
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_member($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        // FIXME - change description in add() to something better
        if (! $group->exists())
            $group->add($this->plugin_name);

        $group->delete_member($username);
    }

    /**
     * Checks the existence of the plugin group.
     *
     * @return boolean TRUE if plugin group exists
     * @throws Engine_Exception
     */

    public function exists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        return $group->exists();
    }

    /**
     * Returns a list of group members.
     *
     * @return array list of group members
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function get_members()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        return $group->get_members();
    }

    /**
     * Returns the plugin group description.
     *
     * @return string plugin group description
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function get_description()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        return $group->get_description();
    }

    /**
     * Returns the plugin group information.
     *
     * @return array plugin group information
     * @throws Group_Not_Found_Exception, Engine_Exception
     */

    public function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        return $group->get_info();
    }

    /**
     * Sets the plugin group description.
     *
     * @param string $description plugin group description
     *
     * @return void
     * @throws Group_Not_Found_Exception, Engine_Exception, Validation_Exception
     */

    public function set_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        return $group->set_description($description);
    }

    /**
     * Sets the plugin group member list.
     *
     * @param array $members array of plugin group members
     *
     * @return void
     * @throws Group_Not_Found_Exception, Engine_Exception, Validation_Exception
     */

    public function set_members($members)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new Group_Driver($this->plugin_name);

        return $group->set_members($members);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for plugin description.
     *
     * @param string description
     *
     * @return string error message description is invalid
     */

    public function validate_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([\w \.\-]*)$/', $description))
            return lang('groups_description_is_invalid');
    }

    /**
     * Validation routine for plugin name.
     *
     * Groups must begin with a letter and allow underscores.
     *
     * @param string $plugin_name plugin name
     *
     * @return boolean error message if plugin name is invalid
     */

    public function validate_plugin_name($plugin_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([a-zA-Z]+[0-9a-zA-Z\.\-_\s]*)$/', $plugin_name))
            return lang('groups_plugin_name_is_invalid');
    }
}
