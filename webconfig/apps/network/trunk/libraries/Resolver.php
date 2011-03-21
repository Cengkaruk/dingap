<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2011 ClearFoundation
//
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

/**
 * The Resolver class manages the /etc/resolv.conf file.
 *
 * @category   Apps
 * @package    
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps//
 * @copyright  2003-2011 ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('network/Network');
clearos_load_library('base/Shell');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Resolver class.
 *
 * Provides tools for editing /etc/resolv.conf.
 *
 * @category   Apps
 * @package    
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps//
 * @copyright  2003-2011 ClearFoundation
 */

class Resolver extends Network
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = "/etc/resolv.conf";
    const CONST_TEST_HOST = 'sdn1.clearsdn.com';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Resolver constructor.
     *
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();

    }

    /**
     * A generic method to grab information from /etc/resolv.conf.
     *
     * @access private
     * @param string $key parameter - eg domain
     *
     * @return string value for given key
     * @throws Engine_Exception
     */

    public function get_parameter($key)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            return "";

        try {
            $value = $file->LookupValue("/^$key\s+/");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return $value;
    }

    /**
     * Returns domain.
     *
     *
     * @return string domain
     * @throws Engine_Exception
     */

    public function get_local_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        $domain = $this->GetParameter('domain');
        return $domain;
    }

    /**
     * Returns DNS servers.
     *
     *
     * @return array DNS servers in an array
     * @throws Engine_Exception
     */

    public function get_nameservers()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            return array();

        // Fill the array
        //---------------

        $nameservers = array();

        $lines = $file->GetContentsAsArray();

        try {
            foreach ($lines as $line) {
                if (preg_match('/^nameserver\s+/', $line))
                    array_push($nameservers, preg_replace('/^nameserver\s+/', '', $line));
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        return $nameservers;
    }

    /**
     * Returns search parameter.
     *
     *
     * @return string search
     * @throws Engine_Exception
     */

    public function get_search()
    {
        clearos_profile(__METHOD__, __LINE__);

        $search = $this->GetParameter('search');
        return $search;
    }

    /**
     * Generic set parameter for /etc/resolv.conf.
     *
     * @access private
     * @param string $key parameter that is being replaced
     * @param string $replacement the full replacement (could be multiple lines)
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_parameter($key, $replacement)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG);

            // Create file if it does not exist
            //---------------------------------

            if (! $file->exists())
                $file->create('root', 'root', '0644');

            $file->ReplaceLines('/^' . $key . '/', '');

            // Add domain (if it exists)
            //--------------------------

            if ($replacement) {
                if (is_array($replacement)) {
                    foreach ($replacement as $line)
                    $file->add_lines($line . "\n");
                } else {
                    $file->add_lines($replacement . "\n");
                }
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage());
        }
    }

    /**
     * Sets domain. 
     *
     * Setting the domain to blank will remove the line from /etc/resolv.conf.
     *
     * @param string $domain domain
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_local_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->IsValidLocalDomain($domain))
            throw new Validation_Exception(RESOLVER_LANG_ERRMSG_DOMAIN_INVALID);

        // Set the parameter
        //------------------

        if ($domain)
            $this->SetParameter('domain', 'domain ' . $domain);
        else
            $this->SetParameter('domain', '');
    }

    /**
     * Sets DNS servers.
     *
     * Setting the DNS servers to blank will remove the line from /etc/resolv.conf.
     *
     * @param array $nameservers DNS servers
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_nameservers($nameservers)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($nameservers))
            $nameservers = array($nameservers);

        // Validate
        //---------

        $thelist = Array();

        foreach ($nameservers as $server) {
            $server = trim($server);

            if (! $server) {
                continue;
            } else if (! $this->IsValidIp($server)) {
                throw new Validation_Exception(RESOLVER_LANG_ERRMSG_NAMESERVERS_INVALID);
            } else {
                $thelist[] = 'nameserver ' . $server;
            }
        }

        if (count($thelist) > 0)
            $this->SetParameter('nameserver', $thelist);
        else
            $this->SetParameter('nameserver', '');
    }

    /**
     * Sets search parameter.
     *
     * Setting the search to blank will remove the line from /etc/resolv.conf.
     *
     * @param string $search search
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_search($search)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->IsValidSearch($search))
            throw new Validation_Exception(RESOLVER_LANG_ERRMSG_SEARCH_INVALID);

        // Set the parameter
        //------------------

        if ($search)
            $this->SetParameter('search', 'search ' . $search);
        else
            $this->SetParameter('search', '');

    }

    /**
     * Perform DNS lookup.
     *
     * Performs a test DNS lookup using an external DNS resolver.  The PHP
     * system will cache the contents of /etc/resolv.conf.  That's leads to
     * FALSE DNS lookup errors when DNS servers happen to change.
     *
     * @param string $domain domain name to look-up
     * @param int $timeout number of seconds until we time-out
     *
     * @return array DNS test results
     * @throws Engine_Exception, Validation_Exception
     */

    public function test_lookup($domain = c_o_n_s_t__t_e_s_t__h_o_s_t, $timeout = 10)
    {
        clearos_profile(__METHOD__, __LINE__);

        $result = array();
        $shell = new Shell();

        try {
            $servers = $this->GetNameservers();

            foreach ($servers as $server) {
                if ($shell->Execute("/usr/bin/dig", "@$server $domain +time=$timeout") == 0)
                    return TRUE;
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_WARNING);
        }

        return FALSE;
    }

    /**
     * Perform DNS test.
     *
     * Performs a DNS look-up on each name server.
     *
     * @param string $domain domain name to look-up
     * @param int $timeout number of seconds until we time-out
     *
     * @return array DNS test results
     * @throws Engine_Exception, Validation_Exception
     */

    public function test_nameservers($domain = c_o_n_s_t__t_e_s_t__h_o_s_t, $timeout = 10)
    {
        clearos_profile(__METHOD__, __LINE__);

        $result = array();
        $shell = new Shell();

        try {
            $servers = $this->GetNameservers();

            foreach ($servers as $server) {
                if ($shell->Execute("/usr/bin/dig", "@$server $domain +time=$timeout") == 0) {
                    $result[$server]["success"] = TRUE;
                } else {
                    $result[$server]["success"] = FALSE;
                }
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_WARNING);
        }

        return $result;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for domain.
     *
     * @param string $domain domain
     *
     * @return boolean TRUE if domain is valid
     */

    public function is_valid_local_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $domain)
            return TRUE;

        if ($this->IsValidDomain($domain))
            return TRUE;

        return FALSE;
    }


    /**
     * Validation routine for search.
     *
     * @param string $search search
     *
     * @return boolean TRUE if search is valid
     */

    public function is_valid_search($search)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $search)
            return TRUE;

        if ($this->IsValidDomain($search))
            return TRUE;

        return FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * @access private
     */

    public function __destruct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__destruct();
    }
}

