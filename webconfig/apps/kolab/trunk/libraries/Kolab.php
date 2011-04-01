<?php

/**
 * Kolab groupware class.
 *
 * @category   Apps
 * @package    Kolab
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/kolab/
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

namespace clearos\apps\kolab;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('kolab');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Software as Software;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Software');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Kolab groupware class.
 *
 * @category   Apps
 * @package    Kolab
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/kolab/
 */

class Kolab extends Software
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_KOLABCONF = '/usr/sbin/kolabconf';
    const FILE_CONFIG = '/etc/kolab/kolab.conf';
    const INVITATION_ALWAYS_ACCEPT = 'ACT_ALWAYS_ACCEPT';
    const INVITATION_ALWAYS_REJECT = 'ACT_ALWAYS_REJECT';
    const INVITATION_REJECT_IF_CONFLICTS = 'ACT_REJECT_IF_CONFLICTS';
    const INVITATION_MANUAL_IF_CONFLICTS = 'ACT_MANUAL_IF_CONFLICTS';
    const INVITATION_MANUAL = 'ACT_MANUAL';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $config = NULL;
    protected $is_loaded = FALSE;
    protected $invitation_policies = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Kolab constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->invitation_policies = array(
            self::INVITATION_ALWAYS_ACCEPT => lang('kolab_always_accept_invitation'),
            self::INVITATION_ALWAYS_REJECT => lang('kolab_always_reject_invitation'),
            self::INVITATION_REJECT_IF_CONFLICTS => lang('kolab_reject_if_conflict'),
            self::INVITATION_MANUAL_IF_CONFLICTS => lang('kolab_manual_operation_if_conflict'),
            self::INVITATION_MANUAL => lang('kolab_manual_operation')
        );

        parent::__construct('kolabd');
    }

    /**
     * Returns invitation policies.
     *
     * @return array invitation policies
     * @throws Engine_Exception
     */

    public static function get_invitation_policies()
    {
        clearos_profile(__METHOD__, __LINE__);

        $kolab = new Kolab();
        return $kolab->invitation_policies;
    }

    /**
     * Load configuration.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = new Configuration_File(self::FILE_CONFIG, 'split', ':', 2);

        $this->config = $config->load();
        $this->is_loaded = TRUE;
    }
}
