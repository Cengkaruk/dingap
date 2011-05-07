<?php

/**
 * MySQL class
 *
 * @category   Apps
 * @package    MySQL
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mysql/
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

namespace clearos\apps\mysql;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('mysql');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/Shell');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * MySQL class
 *
 * @category   Apps
 * @package    MySQL
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mysql/
 */

class Mysql extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_MYSQLADMIN = '/usr/bin/mysqladmin';
    const COMMAND_MYSQL = '/usr/bin/mysql';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Mysql constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('mysqld');
    }

    /**
     * Checks that the password for given hostname is set.
     *
     * @param string $username username
     * @param string $hostname hostname
     *
     * @return boolean TRUE if set
     * @throws Engine_Exception, Validation_Exception
     */

    public function is_password_set($username, $hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_username($username));
        Validation_Exception::is_valid($this->validate_hostname($hostname));

        $options['validate_exit_code'] = FALSE;

        $shell = new Shell();
        $retval = $shell->execute(
            self::COMMAND_MYSQLADMIN, "-u'$username' -h'$hostname' --protocol=tcp status", FALSE, $options
        );

        if ($retval == 0)
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Checks that the password for localhost.
     *
     * @return boolean TRUE if set
     * @throws Engine_Exception
     */

    public function is_root_password_set()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_password_set('root', 'localhost'))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Sets the database password for localhost and hostname.
     *
     * @param string $username     username
     * @param string $old_password old password
     * @param string $password     password
     * @param string $hostname     hostname
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_password($username, $old_password, $password, $hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_username($username));
        Validation_Exception::is_valid($this->validate_password($old_password));
        Validation_Exception::is_valid($this->validate_password($password));
        Validation_Exception::is_valid($this->validate_hostname($hostname));

        if ($old_password)
            $passwd_param = "-p'$old_password'";
        else
            $passwd_param = "";

        try {
            $options = array();
            $options['env'] = 'LANG=en_US'; 

            $shell = new Shell();
            $shell->Execute(
                self::COMMAND_MYSQLADMIN, 
                "-u'$username' $passwd_param -h'$hostname' --protocol=tcp password '$password'", FALSE, $options
            );
        } catch (Engine_Exception $e) {
            // KLUDGE: detect access denied so we can return a less cryptic message
            $output = $shell->get_last_output_line();
            $error = (preg_match('/Access denied/', $output)) ? lang('mysql_access_denied') : $output;

            throw new Engine_Exception($error);
        }

        // FIXME: exception handling is busted
        try {
            $shell->Execute(
                self::COMMAND_MYSQLADMIN, "-u$username $passwd_param -h$hostname --protocol=tcp flush-privileges"
            );
        } catch (Engine_Exception $e) {
            // Not fatal if it fails
        } catch (Exception $e) {
            // Not fatal if it fails
        }
    }

    /**
     * Sets the root password.
     *
     * @param string $old_password old password
     * @param string $password     password
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_root_password($old_password, $password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // set_password will handle the validation

        $this->set_password('root', $old_password, $password, 'localhost');

        // Set password for 127.0.0.1 as well, if it exists.

        try {
            $this->set_password('root', $password, $password, '127.0.0.1');
        } catch (Exception $e) {
            // Not fatal
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($hostname))
            return lang('mysql_hostname_is_invalid');
    }

    /**
     * Validates password.
     *
     * @param string $password password
     *
     * @return string error message if password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: review password handling on command line (dangerous)
    }

    /**
     * Validates username.
     *
     * @param string $username username
     *
     * @return string error message if username is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([a-z0-9_\-\.\$]+)$/', $username))
            return lang('mysql_username_is_invalid');
    }
}
