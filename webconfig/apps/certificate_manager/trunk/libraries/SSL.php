<?php

/**
 * Certificate manager class.
 *
 * @category   Apps
 * @package    Certificate_Manager
 * @subpackage Exceptions
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/certificate_manager/
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
//
// The original SSL API was filename-centric.  For example, to get the 
// attributes of a particular certificate, you would use
// get_certificate_attributes($filename).  With the addition of default user
// certificates, system certificates, and potentially specific certificates
// (e.g. web server), the API became less filename-centric.
//
// Result: some API inconsistencies.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\certificate_manager;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('certificate_manager');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Country as Country;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\certificate_manager\SSL as SSL;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\organization\Organization as Organization;

clearos_load_library('base/Country');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('certificate_manager/SSL');
clearos_load_library('network/Hostname');
clearos_load_library('organization/Organization');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\certificate_manager\Certificate_Already_Exists_Exception as Certificate_Already_Exists_Exception;
use \clearos\apps\certificate_manager\Certificate_Not_Found_Exception as Certificate_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('certificate_manager/Certificate_Already_Exists_Exception');
clearos_load_library('certificate_manager/Certificate_Not_Found_Exception');


///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Certificate manager class.
 *
 * The SSL class is used to create Certificate Authorities, create, sign, and
 * manage certificates and keys.  There are a few attributes that are used
 * for certificates/keys.
 *
 * Type -- The type refers to the certificate type, e.g. key, certificate,
 * pkcs12, CSR, etc.  The types supported in this class are defined in the 
 * TYPE_X constants.
 *
 * Purpose -- The use refers to the intended use of the certficate, e.g. mail user,
 * VPN client, etc.  The uses supported in the class are defined in the USE_X
 * constants.
 *
 * @category   Apps
 * @package    Certificate_Manager
 * @subpackage Exceptions
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/certificate_manager/
 */

class SSL extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * In the old SSL class, there were two prefixes (sys, usr)
     * and three purposes:
     *
     * - PURPOSE_LOCAL (now PURPOSE_SERVER_LOCAL)
     *   for local servers on the system (webconfig, LDAP)
     *
     * - PURPOSE_SERVER (now PURPOSE_SERVER_CUSTOM)
     *   for custom servers (e.g. web server)
     *
     * - PURPOSE_EMAIL (now PURPOSE_CLIENT_CUSTOM)
     *   for e-mail encryption/signatures
     *
     * This new class maintains the old methodology with 
     * PURPOSE_CLIENT_CUSTOM and PURPOSE_SERVER_CUSTOM, and creates
     * specialized built-in certificates:
     *
     * - PURPOSE_CLIENT_LOCAL: certificate key/pair for all users
     * - PURPOSE_SERVER_LOCAL: server certificate for webconfig, LDAP
     *
     * TODO: we could also add the following in a future release:
     * - PURPOSE_SERVER_MAIL: server certificates for the mail server
     * - PURPOSE_SERVER_HTTPD: server certificates for the web server
     */

    // Purposes
    const PURPOSE_CLIENT_CUSTOM = 'client_custom';  // Custom client (e-mail) certificate
    const PURPOSE_SERVER_CUSTOM = 'server_custom';  // Custom server certificate
    const PURPOSE_CLIENT_LOCAL = 'client_local';  // Client certificate for all local users
    const PURPOSE_SERVER_LOCAL = 'server_local';  // Server certificate for local servers

    // Files and paths
    const PATH_SSL = '/etc/pki/CA';
    const FILE_CONF = '/etc/pki/CA/openssl.cnf';
    const FILE_INDEX = '/etc/pki/CA/index.txt';
    const FILE_CA_CRT = '/etc/pki/CA/cacert.pem';
    const FILE_CA_KEY = '/etc/pki/CA/private/cakey.pem';
    const FILE_DH_PREFIX = 'dh';
    const FILE_DH_SUFFIX = '.pem';

    // Commands
    const COMMAND_OPENSSL = '/usr/bin/openssl';

    // Defaults
    const DEFAULT_CA_EXPIRY = 9125; // 25 yrs in days
    const DEFAULT_KEY_SIZE = 2048;
    const DEFAULT_DH_KEY_SIZE = 2048;

    // File prefixes
    const PREFIX_CUSTOM = 'usr';
    const PREFIX_CLIENT_LOCAL = 'client';
    const PREFIX_SERVER_LOCAL = 'sys';

    // Key types and suffixes
    const TYPE_ALL = 'pem';
    const TYPE_P12 = 'p12';
    const TYPE_KEY = 'key';
    const TYPE_CERTIFICATE = 'cert';
    const TYPE_REQUEST = 'req';
    const SUFFIX_ALL = '.pem';
    const SUFFIX_P12 = '.p12';
    const SUFFIX_KEY = '-key.pem';
    const SUFFIX_CERTIFICATE = '-cert.pem';
    const SUFFIX_REQUEST = '-req.pem';

    // Signing types
    const SIGN_SELF = 1;
    const SIGN_3RD_PARTY = 2;

    // Terms
    const TERM_1DAY = 1;
    const TERM_7DAYS = 7;
    const TERM_1MONTH = 30;
    const TERM_3MONTHS = 90;
    const TERM_6MONTHS = 180;
    const TERM_1YEAR = 365;
    const TERM_2YEAR = 739;
    const TERM_3YEAR = 1095;
    const TERM_5YEAR = 1825;
    const TERM_10YEAR = 3650;
    const TERM_15YEAR = 5475;
    const TERM_20YEAR = 7300;
    const TERM_25YEAR = 9125;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $configuration = NULL;
    protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * SSL constructor.
     */

    public function __construct() 
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Creates a new SSL certificate request.
     *
     * @param string $common_name common for for the certificate
     * @param string $purpose     purpose of the certificate
     *
     * @return string filename of certificate created
     * @throws Engine_Exception
     */

    public function create_certificate_request($common_name, $purpose)
    {
        clearos_profile(__METHOD__, __LINE__);

        $prefix = $this->_get_file_prefix($common_name, $purpose);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        // Default CA section name
        $ca = $this->configuration['ca']['default_ca'];

        // Set SSL directory prefix
        // Sanitize/validate other configuration parameters before expansion.
        $req_dname = $this->configuration['req']['distinguished_name'];

        $this->configuration[$req_dname]['commonName_default'] = $common_name;
        $this->configuration['global']['dir'] = SSL::PATH_SSL;
        $this->configuration['req']['encrypt_key'] = 'no';
        $this->configuration['req']['default_keyfile'] = SSL::PATH_SSL . '/private/' . $prefix . '-key.pem';

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        // Expand configuration variables
        $this->_expand_configuration();

        // Save working configuration to a temporary file.  This is to be read
        // later by the OpenSSL binary to generate a new certificate request.
        $config = tempnam('/var/tmp', 'openssl');
        $this->_save_configuration($config);

        // Construct OpenSSL arguments
        $args = sprintf("req -new -out %s -batch -config %s", SSL::PATH_SSL . '/' . $prefix . '-req.pem', $config);

        // Execute OpenSSL
        $shell = new Shell();
        $exitcode = $shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE);

        $configfile = new File($config, TRUE);
        $configfile->delete();

        // Change file attributes
        $file = new File(self::PATH_SSL . "/private/$prefix-key.pem", TRUE);
        $file->chmod(600);
        $file->chown('root', 'root');

        return "$prefix-req.pem";
    }

    /**
     * Creates a new root CA certificate.
     *
     * @return void
     * @throws Certificate_Already_Exists_Exception, Engine_Exception
     */

    public function create_certificate_authority()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        // Default CA section name
        $ca = $this->configuration['ca']['default_ca'];

        // Set SSL directory prefix
        // Sanitize/validate other configuration parameters before expansion.
        $this->configuration['global']['dir'] = SSL::PATH_SSL;
        $this->configuration['req']['default_keyfile'] = $this->configuration[$ca]['private_key'];

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        // We want to override the default 1 year expir for the CA
        $this->configuration[$ca]['default_days'] = self::DEFAULT_CA_EXPIRY;

        // Expand configuration variables
        $this->_expand_configuration();

        // If the CA certification exists, throw and exception
        $file = new File($this->configuration[$ca]['certificate'], TRUE);

        if ($file->exists())
            throw new Certificate_Already_Exists_Exception();

        // Save working configuration to a temporary file.  This is to be read
        // later by the OpenSSL binary to generate a new root CA.
        $config = tempnam('/var/tmp', 'openssl');
        $this->_save_configuration($config);

        // Construct OpenSSL arguments
        $args = sprintf('req -new -x509 -extensions v3_ca -days %d -out %s -batch -nodes -config %s',
            $this->configuration[$ca]['default_days'],
            $this->configuration[$ca]['certificate'],
            $config
        );

        // Use an existing private CA key if present (otherwise, generate a new one)
        $file = new File($this->configuration['req']['default_keyfile'], TRUE);

        if ($file->exists())
            $args .= sprintf(' -key %s', $this->configuration['req']['default_keyfile']);

        // Execute OpenSSL
        $shell = new Shell();
        $shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE);

        // Save custom CA configuration
        $this->_save_configuration();

        $config_file = new File($config, TRUE);
        $config_file->delete();

        // Change file attributes
        $file = new File(self::FILE_CA_KEY, TRUE);
        $file->chmod(600);
        $file->chown('root', 'root');
    }

    /**
     * Creates a default client certificate.
     *
     * @param string $username username
     *
     * @return void
     * @throws Engine_Exception
     */

    public function create_default_client_certificate($username, $password, $verify)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME validate

        $organization = new Organization();

        // FIXME: $domain = $organization->GetDomain();
        $org_name = $organization->get_organization();
        $org_unit = $organization->get_unit();
        $city = $organization->get_city();
        $region = $organization->get_region();
        $country = $organization->get_country();

        $domain = empty($domain) ? 'example.com' : $domain;
        $org_name = empty($org_name) ? 'Organization' : $org_name;
        $org_unit = empty($org_unit) ? 'Unit' : $org_unit;
        $city = empty($city) ? 'City' : $city;
        $region = empty($region) ? 'Region' : $region;
        $country = empty($country) ? 'XX' : $country;

        $this->set_rsa_key_size(self::DEFAULT_KEY_SIZE);
        $this->set_organization_name($org_name);
        $this->set_organizational_unit($org_unit);
        $this->set_email_address($username . "@" . $domain);
        $this->set_locality($city);
        $this->set_state_or_province($region);
        $this->set_country_code($country);
        $this->set_purpose(SSL::PURPOSE_CLIENT_LOCAL);
        $this->set_term(SSL::TERM_10YEAR);

        $filename = $this->create_certificate_request($username, self::PURPOSE_CLIENT_LOCAL);
        $filename = $this->sign_certificate_request($filename);
        $this->export_pkcs12($filename, $password, $verify);
    }

    /**
     * Creates a Diffie-Hellman.
     *
     * @param integer $key_size key size
     *
     * @return void
     * @throws Engine_Exception
     */

    public function create_diffie_hellman($key_size = self::DEFAULT_DH_KEY_SIZE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $dhfile = self::PATH_SSL . "/" . self::FILE_DH_PREFIX . "$key_size" . self::FILE_DH_SUFFIX;

        $file = new File($dhfile);

        if ($file->exists())
            throw new Certificate_Already_Exists_Exception();

        $args = "dhparam -out $dhfile $key_size";
        $shell = new Shell();
        $shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE);
    }

    /**
     * Decrypts message.
     *
     * @param string $filename filename (including path) of the message to decrypt
     *
     * @return string $filename filename of decrypted message
     * @throws Engine_Exception
     */

    public function decrypt_message($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $tmp_file = tempnam("/var/tmp", "decrypt");
        try {
            $file = new File($tmp_file);
            $file->chown("webconfig", "webconfig");
            $file->chmod("0600");
        } catch (Exception $e) {
            try {
                $tempfile = new File($tmp_file, TRUE);
                $tempfile->delete();
            } catch (Exception $e) {}
            throw new Engine_Exception(clearos_exception_message($e));
        }

        # Execute OpenSSL
        try {
            $folder = new Folder(SSL::PATH_SSL . "/private/");
            $private_keys = $folder->GetListing();
            foreach ($private_keys as $private_key) {
                # Get public/private key pairs
                $key = SSL::PATH_SSL . "/private/" . $private_key;
                $cert = SSL::PATH_SSL . "/" . preg_replace("/-key/i", "-cert", $private_key);

                $args = "smime -decrypt -in $filename -recip $cert -inkey $key -out $tmp_file";
                if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE) == 0) {
                    $file->delete();
                    return $tmp_file;
                }
            }
            $file->delete();
            return;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Deletes certificate.
     *
     * @param string $filename certificate filename
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_certificate($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        // If the it's a PKCS12 file, delete it.
        if (preg_match('/' . self::SUFFIX_P12 . '$/', $filename)) {
            $file = new File(self::PATH_SSL . "/" . $filename);

            if ($file->exists())
                $file->delete();

            // We don't delete private key if deleting the PKCS12
            return;
        }

        // This method can be called on req or certs...find out which
        if (preg_match('/' . self::SUFFIX_REQUEST . '$/', $filename)) {
            $req = preg_replace("/-req.pem/", "-cert.pem", $filename); 
            $crt = $filename;
            $key = preg_replace("/-req.pem/", "-key.pem", $filename); 
            $p12 = preg_replace("/-req.pem/", ".p12", $filename); 
        } else {
            $req = $filename;
            $crt = preg_replace("/-cert.pem/", "-req.pem", $filename); 
            $key = preg_replace("/-cert.pem/", "-key.pem", $filename); 
            $p12 = preg_replace("/-cert.pem/", ".p12", $filename); 
        }

        // Revoke and delete PKCS12 and private keys
        $this->revoke_certificate($filename);

        $file = new File(self::PATH_SSL . "/$crt");
        if ($file->exists())
            $file->delete();

        $file = new File(self::PATH_SSL . "/$req");
        if ($file->exists())
            $file->delete();

        $file = new File(self::PATH_SSL . "/$p12");
        if ($file->exists())
            $file->delete();

        $file = new File(self::PATH_SSL . "/private/$key", TRUE);
        if ($file->exists())
            $file->delete();
    }

    /**
     * Deletes default client certificate.
     *
     * @param string $username username
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_default_client_certificate($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $prefix = $this->_get_file_prefix($username, self::PURPOSE_CLIENT_LOCAL);
        $certfile = $prefix . "-cert.pem";

        if (file_exists(self::PATH_SSL . "/" . $certfile))
            $this->delete_certificate($certfile);
    }

    /**
     * Deletes the root CA certificate.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_certificate_authority()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        // Default CA section name
        $ca = $this->configuration['ca']['default_ca'];

        // Set SSL directory prefix
        // Sanitize/validate other configuration parameters before expansion.
        $this->configuration['global']['dir'] = SSL::PATH_SSL;

        // Expand configuration variables
        $this->_expand_configuration();

        // If the CA certification exists, delete it.
        $file = new File($this->configuration[$ca]['certificate'], TRUE);

        if ($file->exists())
            $file->delete();
    }

    /**
     * Checks the existence default client certificate.
     *
     * @param string $username username
     *
     * @return boolean TRUE if certificate exists
     * @throws Engine_Exception
     */

    public function exists_default_client_certificate($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $prefix = $this->_get_file_prefix($username, self::PURPOSE_CLIENT_LOCAL);
        $certfile = $prefix . "-cert.pem";

        $file = new File(SSL::PATH_SSL . "/$prefix-cert.pem");

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Checks the existence of certificate authority.
     *
     * @return boolean TRUE if certificate authority has already been created
     * @throws Engine_Exception
     */

    public function exists_certificate_authority()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $this->get_certificate_authority_filename();
        } catch (Certificate_Not_Found_Exception $e) {
            return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }   

        return TRUE;
    }

    /**
     * Checks the existence of local server certificate.
     *
     * @return boolean TRUE if server certificate exists
     * @throws Engine_Exception
     */

    public function exists_system_certificate()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(SSL::PATH_SSL . "/" . self::PREFIX_SERVER_LOCAL . "-0-cert.pem");

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Exports PKCS#12.
     *
     * @param string $filename certificate filename (must be unique among all certificates)
     * @param string $password password used to encrypt PKCS#12 file
     * @param string $verify   password verify
     *
     * @return void
     */

    public function export_pkcs12($filename, $password, $verify)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME Validation

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        // SSL directory
        $dir = SSL::PATH_SSL;

        // Default CA section name
        $ca = $this->configuration["ca"]["default_ca"];

        // If file exists, delete current one...we are renewing cert.
        $file = new File("$dir/" . preg_replace('/-cert.pem$/', '.p12', $filename));
        if ($file->exists())
            $file->delete();

        $file = new File("$dir/$filename");
        if (!$file->exists())
            throw new Certificate_Not_Found_Exception();

        // Set SSL directory prefix
        // Sanitize/validate other configuration parameters before expansion.
        $this->configuration["global"]["dir"] = $dir;

        // Expand configuration variables
        $this->_expand_configuration();

        // Save password to temporary file
        $passout = tempnam("/var/tmp", "openssl");

        try {
            $file = new File($passout);
            $file->chown("root", "root");
            $file->chmod("0600");
            $file->add_lines($password);
        } catch (Exception $e) {
            try {
                $passfile = new File($passout, TRUE);
                $passfile->delete();
            } catch (Exception $e) {}
            throw new Engine_Exception(clearos_exception_message($e));
        }

        // Construct OpenSSL arguments
        $args = sprintf("pkcs12 -export -in %s -inkey %s -certfile %s -name \"%s\" -passout file:%s -out %s",
            "$dir/$filename",
            "$dir/private/" . preg_replace('/-cert.pem$/', "-key.pem", $filename),
            $this->configuration[$ca]["certificate"],
            preg_replace('/-cert.pem$/', ".p12", $filename), $passout,
            "$dir/" . preg_replace('/-cert.pem$/', ".p12", $filename));

        // Execute OpenSSL
        try {
            $shell = new Shell();
            $exitcode = $shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE);
        } catch (Exception $e) {
            try {
                $passfile = new File($passout, TRUE);
                $passfile->delete();
            } catch (Exception $e) {}
            throw new Engine_Exception(clearos_exception_message($e));
        }

        try {
            $passfile = new File($passout, TRUE);
            $passfile->delete();
        } catch (Exception $e) {}

        if ($exitcode != 0) {
            $errstr = $shell->get_last_output_line();
            $output = $shell->get_output();
            try {
                $delfile = new File("$dir/" . preg_replace('/-cert.pem$/', ".p12", $filename), TRUE);
                $delfile->delete();
            } catch (Exception $e) {}

            throw new Engine_Exception($errstr);
        }
    }

    /**
     * Returns certificate attributes.
     *
     * @param string $filename certificate path and filename
     *
     * @return array list of certificate attributes
     * @throws Certificate_Not_Found_Exception, Engine_Exception
     */

    public function get_certificate_attributes($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Ensure file/certificate exists
        if (! preg_match('/\//', $filename))
            $filename = self::PATH_SSL . '/' . $filename;

        $file = new File($filename);

        if (! $file->exists())
            throw new Certificate_Not_Found_Exception();

        // Since we cannot 'peek' inside PKCS12 without the password, use the x509 cert data
        if (preg_match('/' . self::SUFFIX_P12 . '$/', $filename)) 
            $filename = preg_replace('/' . self::SUFFIX_P12 . '$/', '-cert.pem', $filename);
        
        if (preg_match('/' . self::SUFFIX_REQUEST . '/', $filename))
            $type = 'req';
        else
            $type = 'x509';

        // Make sure parsing is done in English
        $options['env'] = 'LANG=en_US';
        $options['validate_exit_code'] = FALSE;

        // Create array and set some defaults
        $attributes = array('ca' => FALSE, 'server' => FALSE, 'smime' => FALSE);

        // It would be nice to get this all from one call, but you can't count on fields set
        $shell = new Shell();

        $args = "$type -in $filename -noout -dates";
        if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE, $options) == 0) {
            $output = $shell->get_output();
            $attributes['expireNotBefore'] = preg_replace('/notBefore=/i', '', $output[0]);
            $attributes['expireNotAfter'] = preg_replace('/notAfter=/i', '', $output[1]);
        }

        $args = "$type -in $filename -noout -modulus";
        if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE, $options) == 0) {
            $output = $shell->get_output();
            $attributes['key_size'] = strlen(preg_replace('/Modulus=/i', '', $output[0]))/2*8;
        }

        $args = "$type -in $filename -noout -subject";
        if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE, $options) == 0) {
            $output = $shell->get_output();
            $filter =  trim(preg_replace('/subject= \//', '', $output[0]));
            $keyvaluepair = explode("/", $filter);
            // TODO: some attributes (like org_unit) are optional?
            // Should these attributes be set to empty string?
            foreach ($keyvaluepair as $pair) {
                $split = explode('=', $pair);
                $key = $split[0];
                $value = $split[1];
                if ($key == 'O')
                    $attributes['org_name'] = $value;
                elseif ($key == 'OU')
                    $attributes['org_unit'] = $value;
                elseif ($key == 'emailAddress')
                    $attributes['email'] = $value;
                elseif ($key == 'L')
                    $attributes['city'] = $value;
                elseif ($key == 'ST')
                    $attributes['region'] = $value;
                elseif ($key == 'C')
                    $attributes['country'] = $value;
                elseif ($key == 'CN')
                    $attributes['common_name'] = $value;
            }
        }

        $args = "$type -in $filename -noout -purpose";
        if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE, $options) == 0) {
            $output = $shell->get_output();

            foreach ($output as $line) {
                $split = explode(' : ', $line);
                $key = $split[0];
                $value = isset($split[1]) ? $split[1] : '';

                // Just take one of the CA fields...it will tell us all we need to know.
                if ($key == 'SSL server CA') {
                    if ($value == 'Yes')
                        $attributes['ca'] = TRUE;
                }

                if ($key == 'SSL server') {
                    if ($value == 'Yes')
                        $attributes['server'] = TRUE;
                }

                if ($key == 'S/MIME signing') {
                    if ($value == 'Yes')
                        $attributes['smime'] = TRUE;
                }
            }
        }

        return $attributes;
    }

    /**
     * Returns PEM contents.
     *
     * @param  string  $filename  Certificate filename
     *
     * @return  array  contents of certificate
     * @throws  Certificate_Not_Found_Exception, Engine_Exception
     */

    public function get_certificate_pem($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = SSL::PATH_SSL . "/$filename";

        $file = new File($filename);
        if (! $file->exists())
            throw new Certificate_Not_Found_Exception();

        try {
            $contents = $file->get_contents_as_array();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }

        return $contents;
    }

    /**
     * Returns certificate text.
     *
     * @param  string  $filename  Certificate filename
     *
     * @return  array  contents of certificate text
     * @throws  Certificate_Not_Found_Exception, Engine_Exception
     */

    public function get_certificate_text($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = SSL::PATH_SSL . "/$filename";

        # Make sure parsing is done in English
        $options['env'] = "LANG=en_US";

        $file = new File($filename);
        if (!$file->exists())
            throw new Certificate_Not_Found_Exception();

        if (preg_match('/' . self::SUFFIX_REQUEST . '$/', $filename))
            $type = 'req';
        else
            $type = 'x509';

        try {
            $shell = new Shell();
            # It would be nice to get this all from one call, but you can't count on fields set
            $args = "$type -in $filename -noout -text";
            if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE, $options) == 0) {
                $contents = $shell->get_output();
            } else {
                $error = $shell->get_last_output_line();
                throw new Engine_Exception($error);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }

        return $contents;
    }

    /**
     * Returns a list of certificates on the server.
     *
     * @param string $type type of certificate
     *
     * @return array a list of certificates
     */

    public function get_certificates($type = self::TYPE_ALL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $certs = array();

        try {
            $folder = new Folder(self::PATH_SSL);
            $files = $folder->get_listing();
            foreach ($files as $file) {
                try {
                    if (preg_match("/$type/", $file))
                        $certs[$file] = $this->get_certificate_attributes($file);
                } catch (Exception $ignore) {
                    continue;
                }
            }
        } catch (Exception $e) {
            //
        }

        if ($type == SSL::TYPE_P12)
            $sorted = $this->sort_certificates($certs, 'email');
        else
            $sorted = $this->sort_certificates($certs);

        return $sorted;
    }

    /**
     * Returns certificate authority attributes.
     *
     *
     * @return array attributes of certificate authority
     * @throws Certificate_Not_Found_Exception, Engine_Exception
     */

    public function get_certificate_authority_attributes()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ca_file = $this->get_certificate_authority_filename();

        $attributes = $this->get_certificate_attributes($ca_file);
        $attributes['filename'] = $ca_file;

        return $attributes;
    }

    /**
     * Returns certificate authority filename.
     *
     *
     * @return string certificate authority filename
     * @throws Certificate_Not_Found_Exception, Engine_Exception
     */

    public function get_certificate_authority_filename()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        // SSL directory
        $dir = SSL::PATH_SSL;

        // Default CA section name
        $ca = $this->configuration['ca']['default_ca'];

        // Set SSL directory prefix
        // Sanitize/validate other configuration parameters before expansion.
        $this->configuration['global']['dir'] = $dir;

        // Expand configuration variables
        $this->_expand_configuration();

        // If the CA certification doesn't exist, throw and exception
        $file = new File($this->configuration[$ca]['certificate'], TRUE);

        if (!$file->exists())
            throw new Certificate_Not_Found_Exception();

        return $this->configuration[$ca]['certificate'];
    }

    /**
     * Returns RSA key size options.
     *
     * @return array
     */

    public function get_rsa_key_size_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            512 => '512b',
            1024 => '1024b',
            2048 => '2048b',
            4096 => '4096b'
        );

        return $options;
    }

    /**
     * Returns server certificate attributes.
     *
     * @return array attributes of server certificate
     * @throws Certificate_Not_Found_Exception, Engine_Exception
     */

    public function get_system_certificate_attributes()
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = self::PREFIX_SERVER_LOCAL . "-0-cert.pem";

        $attributes = $this->get_certificate_attributes($filename);

        $attributes['filename'] = self::PATH_SSL . "/" . $filename;

        return $attributes;
    }

    /**
     * Returns certificate signing options.
     *
     *
     * @return array
     */

    public function get_signing_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            self::SIGN_SELF=>SSL_LANG_SIGN_SELF,
            self::SIGN_3RD_PARTY=>SSL_LANG_SIGN_3RD_PARTY
        );

        return $options;
    }

    /**
     * Returns certificate term options.
     *
     *
     * @return array
     */

    public function get_term_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            self::TERM_1DAY=>"1 " . SSL_LANG_DAY,
            self::TERM_7DAYS=>"7 " . SSL_LANG_DAYS,
            self::TERM_1MONTH=>"1 " . SSL_LANG_MONTH,
            self::TERM_3MONTHS=>"3 " . SSL_LANG_MONTHS,
            self::TERM_6MONTHS=>"6 " . SSL_LANG_MONTHS,
            self::TERM_1YEAR=>"1 " . SSL_LANG_YEAR,
            self::TERM_2YEAR=>"2 " . SSL_LANG_YEARS,
            self::TERM_3YEAR=>"3 " . SSL_LANG_YEARS,
            self::TERM_5YEAR=>"5 " . SSL_LANG_YEARS,
            self::TERM_10YEAR=>"10 " . SSL_LANG_YEARS,
            self::TERM_15YEAR=>"15 " . SSL_LANG_YEARS,
            self::TERM_20YEAR=>"20 " . SSL_LANG_YEARS,
            self::TERM_25YEAR=>"25 " . SSL_LANG_YEARS
        );

        return $options;
    }

    /**
     * Initializes the default certificate authority and system certificate.
     *
     * @param string $domain domain
     * @param string $orgname organization name
     * @param string $unit organization unit
     * @param string $city city
     * @param string $region region
     * @param string $country country
     *
     * @return void
     * @throws Certificate_Not_Found_Exception, Engine_Exception
     */

    public function initialize($hostname = NULL, $domain = NULL, $orgname =  NULL, $unit = NULL, $city = NULL, $region = NULL, $country = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $organization = new Organization();

        // FIXME $domain = empty($domain) ? $organization->GetDomain() : $domain;
        $domain = 'example.com';
        $orgname = empty($orgname) ? $organization->get_organization() : $orgname;
        $unit = empty($unit) ? $organization->get_unit() : $unit;
        $city = empty($city) ? $organization->get_city() : $city;
        $region = empty($region) ? $organization->get_region() : $region;
        $country = empty($country) ? $organization->get_country() : $country;

        if (empty($hostname)) {
            $hostnameobj = new Hostname();
            $hostname = $hostnameobj->get();
        }

        $ca_exists = $this->exists_certificate_authority();

        if (!$ca_exists) {
            $ssl = new SSL();
            $ssl->set_rsa_key_size(self::DEFAULT_KEY_SIZE);
            $ssl->set_common_name("ca." . $domain);
            $ssl->set_organization_name($orgname);
            $ssl->set_organizational_unit($unit);
            $ssl->set_email_address("security@" . $domain);
            $ssl->set_locality($city);
            $ssl->set_state_or_province($region);
            $ssl->set_country_code($country);
            $ssl->set_term(SSL::TERM_10YEAR); 

            $ssl->create_certificate_authority();
        }

        $syscert_exists = $this->exists_system_certificate();

        if (!$syscert_exists) {
            $ssl = new SSL();
            $ssl->set_rsa_key_size(self::DEFAULT_KEY_SIZE);
            $ssl->set_organization_name($orgname);
            $ssl->set_organizational_unit($unit);
            $ssl->set_email_address("security@" . $domain);
            $ssl->set_locality($city);
            $ssl->set_state_or_province($region);
            $ssl->set_country_code($country);
            $ssl->set_term(SSL::TERM_10YEAR); 
            $ssl->set_purpose(SSL::PURPOSE_SERVER_LOCAL);

            // Create certificate
            $filename = $ssl->create_certificate_request($hostname, SSL::PURPOSE_SERVER_LOCAL);
            $ssl->sign_certificate_request($filename);
        }
    }

    /**
     * Imports signed certificate.
     *
     * @param string $filename the REQ filename
     * @param string $cert the certificate contents
     *
     * @return void
     * @throws Certificate_Not_Found_Exception, Engine_Exception
     */

    public function import_signed_certificate($filename, $cert)
    {
        clearos_profile(__METHOD__, __LINE__);

        $cert = trim($cert);

        # Validation
        if (! $this->IsValidCertificate($cert)) {
            throw new Validation_Exception(SSL_LANG_ERRMSG_INVALID_CERT);
        }
        
        # Put cert in array for dump_contents_from_array method
        $cert_in_array = array($cert);

        $file = new File(SSL::PATH_SSL . "/" . $filename);
        if (!$file->exists())
            throw new Certificate_Not_Found_Exception();

        $cert_filename = preg_replace('/-req.pem$/', '-cert.pem', $filename);

        try {
            $cert_file = new File(SSL::PATH_SSL . "/" . $cert_filename);
            if ($cert_file->exists())
                $cert_file->delete();
            $cert_file->create("root", "root", "0600");
            $cert_file->dump_contents_from_array($cert_in_array);
            # Delete request
            $file->delete();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Checks to see if PKCS12 file already exists for a x509 certificate.
     *
     * @param string $filename x509 certificate filename
     *
     * @return boolean TRUE if PKCS12 file exists
     * @throws Engine_Exception
     */

    public function is_pkcs12_exist($filename)
    {
        clearos_profile(__METHOD__, __LINE__);
        try {
            $pkcs12 = preg_replace('/-cert.pem$/', '.p12', $filename);
            $file = new File(self::PATH_SSL . "/" . $pkcs12);
            if ($file->exists())
                return TRUE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
        return FALSE;
    }

    /**
     * Checks to see if certificate is signed from the resident CA.
     *
     * @param  string  $filename  the certificate filename
     *
     * @return  boolean  TRUE if signed locally 
     * @throws  Certificate_Not_Found_Exception, Engine_Exception
     */

    public function is_signed_by_local_c_a($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        # CA
        $ca = self::FILE_CA_CRT;

        # Cert
        $cert = self::PATH_SSL . "/" . $filename;

        # Check that CA exists
        $file = new File($ca);
        if (! $file->exists())
            throw new Certificate_Not_Found_Exception();

        # Check that certificate exists
        $file = new File($cert);
        if (! $file->exists())
            throw new Certificate_Not_Found_Exception();

        try {
            $shell = new Shell();
            # Get subject of CA
            $args = "x509 -in $ca -noout -subject";
            if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE) == 0) {
                $subject = trim(preg_replace('/^subject=/', '', $shell->get_last_output_line()));
                # Now compare against issuer of certificate
                $args = "x509 -in $cert -noout -issuer ";
                if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE) == 0) {
                    $issuer = trim(preg_replace('/^issuer=/', '', $shell->get_last_output_line()));
                    # If we get here, compare the two...a match returns TRUE
                    if ($issuer == $subject)
                        return TRUE;
                    else
                        return FALSE;
                } else {
                    $error = $shell->get_last_output_line();
                    throw new Engine_Exception($error);
                }
            } else {
                $error = $shell->get_last_output_line();
                throw new Engine_Exception($error);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Renews a certificate.
     *
     * @param  string  $filename  Certificate filename
     * @param  int  $term  the certificate expiry term (in days)
     * @param  string  $password  Password used to encrypt PKCS#12 file
     * @param  string  $verify  Password verify
     * @param  boolean  $pkcs12  Flag Password verify
     *
     * @return  string  $filename of signed certificate
     * @throws  Validation_Exception, Engine_Exception
     */

    public function renew_certificate($filename, $term, $password = NULL, $verify = NULL, $pkcs12 = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        # Validation
        if ($pkcs12 != NULL && !$this->IsValidPassword($password, $verify)) {
            $errors = $this->GetValidationErrors();
            throw new Validation_Exception($errors[0]);
        }
        
        try {
            # Need attributes of exiting cert
            $cert = $this->get_certificate_attributes($filename);
            # Set start date to now (YYMMDDHHMMSSZ)
            $timestamp = time();
            $start_date = date("ymdhis\Z", $timestamp);
// FIXME
            if (isset($cert['smime']) && $cert['smime'])
                $this->set_purpose(self::PURPOSE_CLIENT_CUSTOM);
            else
                $this->set_purpose(self::PURPOSE_SERVER_LOCAL);
            # Add on existing cert's expiry
            $timestamp = strtotime($cert['expireNotAfter']) + ($term*24*60*60);
            $end_date = date("ymdhis\Z", $timestamp);
            $this->set_start_date($start_date);
            $this->set_end_date($end_date);
            $this->set_organization_name($cert['org_name']);
            $this->set_organizational_unit($cert['org_unit']);
            # TODO - this may be blank...OK?
            if ($cert['email'])
                $this->set_email_address($cert['email']);
            $this->set_locality($cert['city']);
            $this->set_state_or_province($cert['region']);
            $this->set_country_code($cert['country']);

            # Force filename
// FIXME
            $req_filename = $this->create_certificate_request($cert['common_name'], $filename);
            if (! $this->is_signed_by_local_ca($filename))
                return;
            $this->revoke_certificate($filename);
            $crt_filename = $this->sign_certificate_request($req_filename, TRUE);
            if ($pkcs12) {
                $this->export_pkcs12($crt_filename, $password, $verify);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Revokes an SSL certificate.
     *
     * @param string $filename certificate filename
     *
     * @return void
     * @throws Engine_Exception
     */

    public function revoke_certificate($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $subject = NULL;
        $id = NULL;

        // Don't revoke a CA cert...it's not in the index
// FIXME: now full path
        if ($filename == self::FILE_CA_CRT)
            return;

        // Requests don't have to be revoked
        if (preg_match('/' . self::SUFFIX_REQUEST . '$/', $filename))
            return;

        // Get Subject from cert
        $shell = new Shell();
        $args = "x509 -in " . self::PATH_SSL . "/$filename -noout -subject";
        $shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE);
        $output = $shell->get_output();
        $subject = trim(preg_replace('/subject=/i', '', $output[0]));

        // Get cert index
        $file = new File(self::FILE_INDEX);
        if (! $file->exists())
            throw new Engine_Exception(LOCALE_LANG_ERRMSG_WEIRD);

        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            // Match subject
            $parts = explode("\t", $line);
            if ($parts[0] == "V" && $parts[5] == $subject) {  // V = Valid
                $id = $parts[3];
                break;
            }
        }
        
        if ($id != NULL) {
            $args = "ca -revoke " . self::PATH_SSL . "/newcerts/$id.pem -config " . self::FILE_CONF;
            $shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE);

            // Revoke was successful
            $file = new File(self::PATH_SSL . "/" . preg_replace('/-cert.pem$/', '.p12', $filename));
            if ($file->exists())
                $file->delete();
        }
    }

    /**
     * Sets organization name.
     *
     * @param string $organization organization name
     *
     * @return void
     */

    public function set_organization_name($organization, $default = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        if (!$default)
            $this->configuration[$req_dname]['organizationName_default'] = $organization;
        else
            $this->_set_default_value($req_dname, 'organizationName_default', $organization);
    }

    /**
     * Sets organizational unit name.
     *
     * @param string $unit organizational unit (ie. Marketing, IT etc.)
     *
     * @return void
     */

    public function set_organizational_unit($unit, $default = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        if (!$default)
            $this->configuration[$req_dname]['organizationalUnitName_default'] = $unit;
        else
            $this->_set_default_value($req_dname, 'organizationalUnitName_default', $unit);
    }

    /**
     * Sets email address.
     *
     * @param string email e-mail address
     *
     * @return void
     * @throws  Validation_Exception
     */

    public function set_email_address($email, $default = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        if (!$default)
            $this->configuration[$req_dname]['emailAddress_default'] = $email;
        else
            $this->_set_default_value($req_dname, 'emailAddress_default', $email);
    }

    /**
     * Sets locality.
     *
     * @param string locality locality (city, district)
     *
     * @return void
     */

    public function set_locality($locality, $default = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        if (!$default)
            $this->configuration[$req_dname]['localityName_default'] = $locality;
        else
            $this->_set_default_value($req_dname, 'localityName_default', $locality);
    }

    /**
     * Sets state or province
     *
     * @param string $state state or province
     *
     * @return void
     */

    public function set_state_or_province($state, $default = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        if (!$default)
            $this->configuration[$req_dname]['stateOrProvinceName_default'] = $state;
        else
            $this->_set_default_value($req_dname, 'stateOrProvinceName_default', $state);
    }

    /**
     * Sets country code.
     *
     * @param string code country code
     *
     * @return void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function set_country_code($code, $default = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        if (!$default)
            $this->configuration[$req_dname]['countryName_default'] = $code;
        else
            $this->_set_default_value($req_dname, 'countryName_default', $code);
    }

    /**
     * Sets RSA key size.
     *
     * @param string name Common name
     *
     * @return void
     */

    public function set_rsa_key_size($key_size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        $this->configuration['req']['default_bits'] = $key_size;
    }

    /**
     * Sets common name.
     *
     * @param string $name common name
     *
     * @return void
     */

    public function set_common_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['distinguished_name']))
            $this->configuration['req']['distinguished_name'] = 'req_distinguished_name';

        $req_dname = $this->configuration['req']['distinguished_name'];

        $this->configuration[$req_dname]['commonName_default'] = $name;
    }

    /**
     * Sets term.
     *
     * @param integer $term certificate expiry term (in days)
     *
     * @return void
     */

    public function set_term($term)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        $ca = $this->configuration['ca']['default_ca'];

        $this->configuration[$ca]['default_days'] = $term;
    }

    /**
     * Set start date.
     *
     * The format is: YYMMDDHHMMSSZ, where "Z" is the capital letter "Z"
     *
     * @param string $date start date 
     *
     * @return void
     */

    public function set_start_date($date)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        $ca = $this->configuration['ca']['default_ca'];

        $this->configuration[$ca]['default_startdate'] = $date;
    }

    /**
     * Sets the purpose of the key/pair (see class overview).
     *
     * @param string $purpose purpose of certificate key/pair
     *
     * @return void
     */

    public function set_purpose($purpose)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($purpose === self::PURPOSE_CLIENT_LOCAL)
            $nscerttype = 'client, email, objsign';
        else if ($purpose === self::PURPOSE_CLIENT_CUSTOM)
            $nscerttype = 'client, email, objsign';
        else if ($purpose === self::PURPOSE_SERVER_LOCAL)
            $nscerttype = 'server';
        else if ($purpose === self::PURPOSE_SERVER_CUSTOM)
            $nscerttype = 'server';
        else
            throw new Validation_Exception(lang('certificate_manager_purpose_invalid'));

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        $this->_set_certificate_ns_cert_type($nscerttype);
    }

    /**
     * Sets end date.
     *
     * The format is: YYMMDDHHMMSSZ, where "Z" is the capital letter "Z"
     *
     * @param string $date override the term variable by setting the end date (used for renewals). 
     *
     * @return void
     */

    public function set_end_date($date)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        $ca = $this->configuration['ca']['default_ca'];

        $this->configuration[$ca]['default_enddate'] = $date;
    }

    /**
     * Sign an SSL certificate request.
     *
     * @param string  $filename certificate filename (must be unique among all certificates)
     * @param boolean $renew    flag indicating whether this is a certificate renewal
     *
     * @return string $filename of signed certificate
     * @throws Certificate_Already_Exists_Exception, Engine_Exception
     */

    public function sign_certificate_request($filename, $renew = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        // SSL directory
        $dir = SSL::PATH_SSL;

        // Default CA section name
        $ca = $this->configuration['ca']['default_ca'];

        if (!preg_match('/' . self::SUFFIX_REQUEST . '$/', $filename))
            throw new Engine_Exception(SSL_LANG_ERRMSG_NOT_A_REQ);

        if (!$renew) {
            $file = new File("$dir/" . preg_replace('/-req.pem$/', '-cert.pem', $filename));

            if ($file->exists())
                throw new Certificate_Already_Exists_Exception();
        }

        // Set SSL directory prefix
        // Sanitize/validate other configuration parameters before expansion.
        $this->configuration['global']['dir'] = $dir;

        // Expand configuration variables
        $this->_expand_configuration();

        // Save working configuration to a temporary file.  This is to be read
        // later by the OpenSSL binary to generate a new certificate request.
        $config = tempnam('/var/tmp', 'openssl');
        $this->_save_configuration($config);

        // Construct OpenSSL arguments
        $args = sprintf('ca -extensions v3_req -days %d -out %s -batch -config %s -infiles %s ',
            $this->configuration[$ca]['default_days'],
            '/dev/null', $config,
            $dir . '/' . $filename);

        // Execute OpenSSL
        $shell = new Shell();
        $shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE);

        $configfile = new File($config, TRUE);
        $configfile->delete();

        $file = new File($this->configuration[$ca]["serial"] . ".old");
        $id = chop($file->get_contents());

        $file = new File($this->configuration[$ca]["new_certs_dir"] . "/$id.pem");
        $file->copy_to("$dir/" . preg_replace('/-req.pem$/', '-cert.pem', $filename));

        // We do not need the certificate request anymore
        $file = new File("$dir/$filename");
        $file->delete();

        return preg_replace('/-req.pem$/', '-cert.pem', $filename);
    }

    /**
     * Sorts certificates.
     *
     * @param string $certs TODO
     * @param string $field TODO
     *
     * @return array TODO
     */

    public function sort_certificates($certs, $field = 'common_name')
    {
        clearos_profile(__METHOD__, __LINE__);

        $sorted = array();
        $keys = array();

        if (!is_array($certs) || count($certs) <= 1)
            return $certs;

        foreach ($certs as $filename => $cert) {
            $keys[$filename] = $cert[$field];
        }

        asort($keys);

        foreach ($keys as $filename => $key)
            $sorted[$filename] = $certs[$filename];

        return $sorted;
    }

    /**
     * Update a certificate.
     *
     * @param  string  $common_name Common name to use (secure.pointclark.net, jim@pointclark.com, etc)
     * @param  string  $filename  Certificate filename
     *
     * @return  void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function update_certificate($common_name, $filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidCommonName($common_name)) {
            $errors = $this->GetValidationErrors();
            throw new Validation_Exception($errors[0]);
        }

        try {
            # Need expire start/stop of exiting cert
            $cert = $this->get_certificate_attributes($filename);
            $this->set_start_date(date("ymdhis\Z", strtotime($cert['expireNotBefore'])));
            $this->set_end_date(date("ymdhis\Z", strtotime($cert['expireNotAfter'])));
            # Need purpose
// FIXME
            if (isset($cert['smime']) && $cert['smime'])
                $this->set_purpose(self::PURPOSE_CLIENT_CUSTOM);
            else
                $this->set_purpose(self::PURPOSE_SERVER_LOCAL);

            # Force filename
            $req_filename = $this->create_certificate_request($common_name, $filename);

            if (! $this->is_signed_by_local_ca($filename))
                return;

            $this->revoke_certificate($filename);
            $crt_filename = $this->sign_certificate_request($req_filename, TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Verifies signature of SMIME encoded email.
     *
     * @param string $filename filename (including path)
     *
     * @return boolean TRUE if signature is verified
     * @throws Engine_Exception
     */

    public function verify_smime($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        # Execute OpenSSL
        try {
            $shell = new Shell();
            $args = "smime -verify -in $filename -CAfile " . self::FILE_CA_CRT;
            if ($shell->execute(SSL::COMMAND_OPENSSL, $args, TRUE) == 0)
                return TRUE;

            return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for certificate password.
     *
     * @param  string  $password  Certificate password
     * @param  string  $verify  Certificate password verify
     *
     * @return  boolean  TRUE if password is valid
     */

    public function is_valid_password($password, $verify)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($password != $verify) {
            $this->AddValidationError(SSL_LANG_ERRMSG_PASSWORD_VERIFY, __METHOD__ ,__LINE__);
            return FALSE;
        }
        // Must begin w/a letter/number
        $state = preg_match('/^([a-zA-Z0-9]+[0-9a-zA-Z\.\-\!\@\#\$\%\^\&\*\(\)_]*)$/', $password);

        if (!$state) {
            $this->AddValidationError(SSL_LANG_ERRMSG_PASSWORD_INVALID, __METHOD__ ,__LINE__);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validation routine for common name.
     *
     * @param  string  $name  Certificate's common name
     *
     * @return  boolean  TRUE if common name is valid
     */

    public function is_valid_common_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $name) {
            $this->AddValidationError(SSL_LANG_ERRMSG_COMMON_NAME_INVALID, __METHOD__ ,__LINE__);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validation routine for email.
     *
     * @param  string  $email  Certificate e-mail
     *
     * @return  boolean  TRUE if email is valid
     */

    public function is_valid_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^[a-z0-9\._-]+@+[a-z0-9\._-]+$/', $email)) {
            $this->AddValidationError(SSL_LANG_ERRMSG_EMAIL_INVALID, __METHOD__ ,__LINE__);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validation routine for certificate.
     *
     * @param  string  $cert  certificate contents
     *
     * @return  boolean  TRUE if cert is valid
     */

    public function is_valid_certificate($cert)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $cert) {
            $this->AddValidationError(SSL_LANG_ERRMSG_CERT_INVALID, __METHOD__ ,__LINE__);
            return FALSE;
        }

        if (! preg_match("/^-----BEGIN .*-----/", $cert)) {
            $this->AddValidationError(SSL_LANG_ERRMSG_EMAIL_INVALID, __METHOD__ ,__LINE__);
            return FALSE;
        }

        if (! preg_match("/-----END .*-----$/", $cert)) {
            $this->AddValidationError(SSL_LANG_ERRMSG_EMAIL_INVALID, __METHOD__ ,__LINE__);
            return FALSE;
        }

        return TRUE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the file prefix to use.
     *
     * File naming conventions:
     * - Built-in client certificates: client-<username>
     * - Built-in server certificate: sys-0
     * - Custom client or server certificates: usr-X where X is sequential
     *
     * @param string $purpose purpose of the certificate
     * @throws Engine_Exception
     */

    protected function _get_file_prefix($common_name, $purpose)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($purpose === self::PURPOSE_CLIENT_LOCAL) {

            $prefix = self::PREFIX_CLIENT_LOCAL . '-' . $common_name;

        } else if ($purpose === self::PURPOSE_SERVER_LOCAL) {

            $prefix = self::PREFIX_SERVER_LOCAL . '-0';

        } else if (($purpose === self::PURPOSE_CLIENT_CUSTOM) || ($purpose ===  self::PURPOSE_SERVER_CUSTOM)) {
            $folder = new Folder(SSL::PATH_SSL);

            $files = $folder->get_listing();

            $next = 0;
            $match = array();
                    
            foreach ($files as $file) {
                if (preg_match("/$prefix([0-9]*)-cert\\.pem$/", $file, $match)) {
                    if ((int)$match[1] >= $next)
                        $next = (int)$match[1] + 1;
                }
            }

            $prefix = self::PREFIX_CUSTOM . '-' . $next;
        }

        return $prefix;
    }

    /**
     * Expands/substitutes configuration variables.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _expand_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        foreach ($this->configuration as $section => $entries) {
            foreach ($entries as $key => $value) {
                // Skip complex variable expansion
                if (preg_match('/\./', $key))
                    continue;

                // Skip RANDFILE
                if ($key === 'RANDFILE')
                    continue;

                eval("\$$key = \"$value\";");
                eval("\$this->configuration[\"$section\"][\"$key\"] = \"$value\";");
            }
        }
    }

    /**
     * Loads an OpenSSL configuration file.
     *
     * The returned array format: [section][key] => value
     *
     * @access private
     * @return array config
     * @throws Engine_Exception
     */

    protected function _load_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = SSL::FILE_CONF;

        $file = new File($filename, TRUE);
        $contents = $file->get_contents_as_array();

        $config = array();
        $section = 'global';
        $wash_pattern[] = '/\s*=\s*/';
        $wash_replace[] = '=';
        $wash_pattern[] = '/\[\s*(\w+)\s*\]/';
        $wash_replace[] = '[\\1]';
        $wash_pattern[] = '/"/';
        $wash_replace[] = '';

        foreach ($contents as $line) {
            $l = chop($line);
            if (!strlen($l)) continue;

            if (($i = strpos($l, '#')) !== FALSE) {
                if ($i == 0) continue;
                $l = chop(substr($l, 0, $i));
                if (!strlen($l)) continue;
            }

            $l = preg_replace($wash_pattern, $wash_replace, $l);

            if (($i = strpos($l, '=')) === FALSE) {
                $section = preg_replace('/\[(\w+)\s*\]/', "\\1", $l);
                continue;
            }

            $config[$section][substr($l, 0, $i)] = substr($l, $i + 1);
        }

        $this->is_loaded = TRUE;

        return $config;
    }

    /**
     * Saves an OpenSSL configuration file.
     *
     * The expected array format is: [section][key] => value
     *
     * @access private
     * @param string $filename Configuration filename
     * @param array $contents Configuration file content array
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_configuration($filename = NULL, $contents = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if ($filename == NULL)
            $filename = SSL::FILE_CONF;

        if ($contents == NULL)
            $contents = $this->configuration;

        $expanded[] = '# OpenSSL configuration generated by:';
        $expanded[] = sprintf("# %s:%d %s($filename)", basename(__FILE__), __LINE__, __METHOD__);

        foreach ($contents as $section => $entries) {
            if ($section != 'global') $expanded[] = "[ $section ]";
    
            foreach ($entries as $key => $value)
                $expanded[] = sprintf('%-30s = %s', $key, $value);

            $expanded[] = '';
        }

        $expanded[] = "# End of $filename";

        $file = new File($filename, TRUE);

        if (!$file->exists()) {
            $file->create('root', 'root', '0600');
        } else {
            $file->chown('root', 'root');
            $file->chmod('0600');
        }

        $file->dump_contents_from_array($expanded);
    }

    /**
     * Sets a key to value.
     *
     * @access private
     * @param string $section Configuration section
     * @param string $key Configuration keyword
     * @param string $value Keyword value
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_default_value($section, $key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->configuration = $this->_load_configuration();

        $this->configuration[$section][$key] = $value;

        $this->_save_configuration();
    }

    /**
     * Sets certificate type (nsCertType).
     *
     * Valid types: client, server, email, objsign, reserved, sslCA, emailCA, objCA.
     *
     * @param string $type valid nsCertType
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_certificate_ns_cert_type($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->configuration = $this->_load_configuration();

        if (!isset($this->configuration['req']['req_extensions']))
            $this->configuration['req']['req_extensions'] = 'v3_req';

        $req_v3 = $this->configuration['req']['req_extensions'];

        $this->configuration[$req_v3]['nsCertType'] = $type;
    }
}
