<?php

/**
 * ClearOS group engine.
 *
 * @category   Apps
 * @package    Groups
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/groups/
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

namespace clearos\apps\groups;

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

clearos_load_library('base/Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS group engine.
 *
 * @category   Apps
 * @package    Groups
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/groups/
 */

class Group_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // Group types
    //------------

    const TYPE_BUILTIN = 'builtin';
    const TYPE_NORMAL = 'normal';
    const TYPE_SYSTEM = 'system';
    const TYPE_WINDOWS = 'windows';
    const TYPE_PLUGIN = 'plugin';
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_ALL = 'all';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    public static $hidden_list = array(
        'account_operators',
        'account operators',
        'administrators',
        'backup_operators',
        'backup operators',
        'domain_computers',
        'domain computers',
        'domain_controllers',
        'domain controllers',
        'domain_guests',
        'domain guests',
        'guests',
        'power_users',
        'power users',
        'print_operators',
        'print operators',
        'server_operators',
        'server operators',
        'users'
    );

    public static $builtin_list = array(
        'allusers',
        'domain_users',
        'domain users'
    );

    public static $windows_list = array(
        'account_operators',
        'account operators',
        'administrators',
        'backup_operators',
        'backup operators',
        'cert_publishers',
        'cert publishers',
        'dnsadmins',
        'dnsupdateproxy',
        'domain_admins',
        'domain admins',
        'domain_computers',
        'domain computers',
        'domain_controllers',
        'domain controllers',
        'domain_guests',
        'domain guests',
        'domain_users',
        'domain users',
        'enterprise_admins',
        'enterprise admins',
        'group_policy_creator_owners',
        'group policy creator owners',
        'guests',
        'power_users',
        'power users',
        'print_operators',
        'print operators',
        'ras_and_ias_servers',
        'ras and ias servers',
        'schema_admins',
        'schema admins',
        'server_operators',
        'server operators',
        'telnetclients',
        'users'
    );

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Group_Engine constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }
}
