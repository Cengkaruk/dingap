<?php

/**
 * Organization class.
 *
 * @category   Apps
 * @package    Organization
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/organization/
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

namespace clearos\apps\organization;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('organization');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Country as Country;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Country');
clearos_load_library('base/Engine');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Organization class.
 *
 * @category   Apps
 * @package    Organization
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/organization/
 */

class Organization extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clearos/organization.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Organization constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns city.
     *
     * @return string city
     * @throws Engine_Exception
     */

    public function get_city()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['city'];
    }

    /**
     * Returns country.
     *
     * @return string country
     * @throws Engine_Exception
     */

    public function get_country()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['country'];
    }

    /**
     * Returns name of organization.
     *
     * @return string name of organization
     * @throws Engine_Exception
     */

    public function get_organization()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['organization'];
    }

    /**
     * Returns postal code.
     *
     * @return string postal code
     * @throws Engine_Exception
     */

    public function get_postal_code()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['postalcode'];
    }

    /**
     * Returns region (state or province).
     *
     * @return string region (state or province)
     * @throws Engine_Exception
     */

    public function get_region()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['region'];
    }

    /**
     * Returns street address.
     *
     * @return string street address
     * @throws Engine_Exception
     */

    public function get_street()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['street'];
    }

    /**
     * Returns name of organization unit.
     *
     * @return string name of organization unit
     * @throws Engine_Exception
     */

    public function get_unit()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['organization_unit'];
    }

    /**
     * Sets city.
     *
     * @param string $city city
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_city($city)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_city($city));

        $this->_set_parameter('city', $city);
    }

    /**
     * Sets country.
     *
     * @param string $country country
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_country($country)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($country))
            $country = '';
        else
            Validation_Exception::is_valid($this->validate_country($country));

        $this->_set_parameter('country', $country);
    }

    /**
     * Sets organization name.
     *
     * @param string $organization organization name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_organization($organization)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_organization($organization));

        $this->_set_parameter('organization', $organization);
    }

    /**
     * Sets postal code.
     *
     * @param string $postal_code postal code
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_postal_code($postal_code)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_postal_code($postal_code));

        $this->_set_parameter('postalcode', $postal_code);
    }

    /**
     * Sets region.
     *
     * @param string $region region (state or province)
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_region($region)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_region($region));

        $this->_set_parameter('region', $region);
    }

    /**
     * Sets street address.
     *
     * @param string $street street address
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_street($street)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_street($street));

        $this->_set_parameter('street', $street);
    }

    /**
     * Sets organization unit.
     *
     * @param string $unit organization unit
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_unit($unit)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_unit($unit));

        $this->_set_parameter('organization_unit', $unit);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for city.
     *
     * @param string $city city
     *
     * @return string error message if city is invalid
     */

    public function validate_city($city)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $city))
            return lang('organization_city_invalid');
    }

    /**
     * Validation routine for country.
     *
     * @param string $country country
     *
     * @return string error message if country is invalid
     */

    public function validate_country($country)
    {
        clearos_profile(__METHOD__, __LINE__);

        $country_object = new Country();
        $countries = $country_object->get_list();

        if (! array_key_exists($country, $countries))
            return lang('organization_country_invalid');
    }

    /**
     * Validation routine for organization.
     *
     * @param string $organization organization
     *
     * @return string error message if organization is invalid
     */

    public function validate_organization($organization)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $organization))
            return lang('organization_organization_invalid');
    }

    /**
     * Validation routine for postal code.
     *
     * @param string $postal_code postal code
     *
     * @return string error message if postal code is invalid
     */

    public function validate_postal_code($postal_code)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $postal_code))
            return lang('organization_postal_code_invalid');
    }

    /**
     * Validation routine for state or province.
     *
     * @param string $region region
     *
     * @return string error message if region is invalid
     */

    public function validate_region($region)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $region))
            return lang('organization_region_invalid');
    }

    /**
     * Validation routine for street.
     *
     * @param string $street street
     *
     * @return string error message if street is invalid
     */

    public function validate_street($street)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $street))
            return lang('organization_street_address_invalid');
    }

    /**
     * Validation routine for organization unit.
     *
     * @param string $unit organization unit
     *
     * @return string error message if unit is invalid
     */

    public function validate_unit($unit)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/([:;\/#!@])/", $unit))
            return lang('organization_unit_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S 
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration files.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $config_file = new Configuration_File(self::FILE_CONFIG);
            $this->config = $config_file->load();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Sets a parameter in the config file.
     *
     * @param string $key   name of the key in the config file
     * @param string $value value for the key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->is_loaded = FALSE;

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            $file->create("root", "root", "0644"); 

        $match = $file->replace_lines("/^$key\s*=\s*/", "$key = $value\n");

        if (!$match)
            $file->add_lines("$key = $value\n");
    }
}
