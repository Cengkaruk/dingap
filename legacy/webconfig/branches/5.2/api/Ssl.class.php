<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2008 Point Clark Networks.
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
//
// The original SSL API was filename-centric.  For example, to get the 
// attributes of a particular certificate, you would use
// GetCertificateAttributes($filename).  With the addition of default user
// certificates, system certificates, and potentially specific certificates
// (e.g. web server), the API became less filename-centric.
//
// Result: some API inconsistencies.
//
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

/**
 * SSL CA and key management class.
 *
 * @package Api
 * @author Point Clark Networks
 * @license GNU Public License
 * @copyright Copyright 2006-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Engine.class.php");
require_once("File.class.php");
require_once("Folder.class.php");
require_once("Hostname.class.php");
require_once("Organization.class.php");
require_once("ShellExec.class.php");

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenSSL execution exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class SslExecutionException extends EngineException
{
	/**
	 * SslExecutionException constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg, $code)
	{
		parent::__construct($errmsg, $code);
	}
}

/**
 * Certificate exists exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class SslCertificateExistsException extends EngineException
{
	/**
	 * SslCertificateExistsException constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg, $code)
	{
		parent::__construct($errmsg, $code);
	}
}

/**
 * Certificate not found exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class SslCertificateNotFoundException extends EngineException
{
	/**
	 * SslCertificateNotFoundException constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg, $code)
	{
		parent::__construct($errmsg, $code);
	}
}

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * SSL CA and key management class.
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
 * @package Api
 * @author Point Clark Networks
 * @license GNU Public License
 * @copyright Copyright 2006-2008, Point Clark Networks
 */

class Ssl extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $configuration = null;
	protected $is_loaded = false;

	const DIR_SSL = "/etc/ssl";
	const FILE_CONF = "openssl.cnf";
	const FILE_INDEX = "certindex.txt";
	const FILE_CA_CRT = "ca-cert.pem";
	const FILE_CA_KEY = "ca-key.pem";
	const FILE_DH_PREFIX = "dh";
	const FILE_DH_SUFFIX = ".pem";
	const FILE_ISO3166 = "/usr/share/zoneinfo/iso3166.tab";
	const LOG_TAG = "ssl";
	const CMD_OPENSSL = "/usr/bin/openssl";
	const DEFAULT_CA_EXPIRY = 9125; # 25 yrs in days
	const DEFAULT_KEY_SIZE = 2048;

	/* In the old SSL class, there were two prefixes (sys, usr)
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
	 * This new class maintains the old functionality with 
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

	const PURPOSE_CLIENT_CUSTOM = "client_custom";  // Custom client (e-mail) certificate
	const PURPOSE_SERVER_CUSTOM = "server_custom";  // Custom server certificate
	const PURPOSE_CLIENT_LOCAL = "client_local";  // Client certificate for all local users
	const PURPOSE_SERVER_LOCAL = "server_local";  // Server certificate for local servers

	const PREFIX_CUSTOM = "usr";
	const PREFIX_CLIENT_LOCAL = "client";
	const PREFIX_SERVER_LOCAL = "sys";

	const TYPE_ALL = '\\.pem$';
	const TYPE_P12 = '\\.p12$';
	const TYPE_KEY = '-key\\.pem$';
	const TYPE_CRT = '-cert\\.pem$';
	const TYPE_REQ = '-req\\.pem$';
	const SIGN_SELF = 1;
	const SIGN_3RD_PARTY = 2;
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
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Ssl constructor.
	 */

	public function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Creates a new SSL certificate request.
	 *
	 * @param string $commonname Common name to use (secure.pointclark.net, jim@pointclark.com, etc)
	 * @param string $purpose purpose of the certificate
	 * @return string filename of certificate created
	 * @throws EngineException
	 */

	public function CreateCertificateRequest($commonname, $purpose)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$prefix = $this->_GetFilePrefix($commonname, $purpose);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		// SSL directory
		$dir = Ssl::DIR_SSL;

		// Default CA section name
		$ca = $this->configuration["ca"]["default_ca"];

		// Set SSL directory prefix
		// Sanitize/validate other configuration parameters before expansion.
		$this->configuration["global"]["dir"] = $dir;
		$this->configuration["req"]["encrypt_key"] = "no";
		$this->configuration["req"]["default_keyfile"] = "$dir/private/$prefix-key.pem";
		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";
		$req_dname = $this->configuration["req"]["distinguished_name"];
		$this->configuration[$req_dname]["commonName_default"] = $commonname;

		// Expand configuration variables
		$this->_ExpandConfiguration();

		// Save working configuration to a temporary file.  This is to be read
		// later by the OpenSSL binary to generate a new certificate request.
		$config = tempnam("/var/tmp", "openssl");

		try {
			$this->_SaveConfiguration($config);
		} catch (Exception $e) {
			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// Construct OpenSSL arguments
		$args = sprintf("req -new -out %s -batch -config %s", "$dir/$prefix-req.pem", $config);

		// Execute OpenSSL
		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(Ssl::CMD_OPENSSL, $args, true);
		} catch (Exception $e) {
			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}
			throw new FileException($e->getMessage(), COMMON_WARNING);
		}

		try {
			$configfile = new File($config, true);
			$configfile->Delete();
		} catch (Exception $e) {}

		if ($exitcode != 0) {
			$errstr = $shell->GetLastOutputLine();
			$output = $shell->GetOutput();
			if (isset($output[count($output) - 2]))
				$errstr = $output[count($output) - 2];
			throw new SslExecutionException($errstr, COMMON_WARNING);
		}

		# Change file attributes
		try {
			$file = new File(self::DIR_SSL . "/private/$prefix-key.pem", true);
			$file->Chmod(640);
			$file->Chown("root", "ssl");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		return "$prefix-req.pem";
	}

	/**
	 * Creates a Diffie-Hellman.
	 *
	 * @param int $keysize key size (defaults to 1024)
	 * @return void
	 * @throws EngineException
	 */

	public function CreateDiffieHellman($keysize = 1024)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$dhfile = self::DIR_SSL . "/" . self::FILE_DH_PREFIX . "$keysize" . self::FILE_DH_SUFFIX;
		$file = new File($dhfile);
		$exists = false;

		try {
			if ($file->Exists())
				$exists = true;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		if ($exists) {
			throw new SslCertificateExistsException(SSL_LANG_ERRMSG_CERT_EXISTS, COMMON_WARNING);
		} else {
			try {
				$args = "dhparam -out $dhfile $keysize";
				$shell = new ShellExec();
				$exitcode = $shell->Execute(Ssl::CMD_OPENSSL, $args, true);
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_WARNING);
			}
		}
	}

	/**
	 * Creates a new root CA certificate.
	 *
	 * @return void
	 * @throws SslCertificateExistsException, EngineException
	 */

	public function CreateCertificateAuthority()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		// SSL directory
		$dir = Ssl::DIR_SSL;

		// Default CA section name
		$ca = $this->configuration["ca"]["default_ca"];

		// Set SSL directory prefix
		// Sanitize/validate other configuration parameters before expansion.
		$this->configuration["global"]["dir"] = $dir;
		$this->configuration["req"]["default_keyfile"] = $this->configuration["$ca"]["private_key"];

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		// We want to override the default 1 year expir for the CA
		$this->configuration["$ca"]["default_days"] = self::DEFAULT_CA_EXPIRY;

		// Expand configuration variables
		$this->_ExpandConfiguration();

		// If the CA certification exists, throw and exception
		try {
			$file = new File($this->configuration["$ca"]["certificate"], true);
			if ($file->Exists())
				throw new SslCertificateExistsException(SSL_LANG_ERRMSG_CA_EXISTS, COMMON_WARNING);
		} catch (Exception $e) {
			throw new FileException($e->getMessage(), COMMON_WARNING);
		}

		// Save working configuration to a temporary file.  This is to be read
		// later by the OpenSSL binary to generate a new root CA.
		$config = tempnam("/var/tmp", "openssl");

		try {
			$this->_SaveConfiguration($config);
		} catch (Exception $e) {
			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// Construct OpenSSL arguments
		$args = sprintf("req -new -x509 -extensions v3_ca -days %d -out %s -batch -nodes -config %s",
			$this->configuration["$ca"]["default_days"],
			$this->configuration["$ca"]["certificate"],
			$config);

		// Use an existing private CA key if present (otherwise, generate a new one)
		try {
			$file = new File($this->configuration["req"]["default_keyfile"], true);
			if ($file->Exists())
				$args .= sprintf(" -key %s", $this->configuration["req"]["default_keyfile"]);
		} catch (Exception $e) {
			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// Execute OpenSSL
		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(Ssl::CMD_OPENSSL, $args, true);
		} catch (Exception $e) {
			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		if ($exitcode != 0) {
			$errstr = $shell->GetLastOutputLine();
			$output = $shell->GetOutput();

			if (isset($output[count($output) - 2]))
				$errstr = $output[count($output) - 2];

			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}

			throw new SslExecutionException($errstr, COMMON_WARNING);
		}

		# Save custom CA configuration
		try {
			$this->_SaveConfiguration();
		} catch (Exception $e) {
			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		try {
			$configfile = new File($config, true);
			$configfile->Delete();
		} catch (Exception $e) {}

		# Change file attributes
		try {
			$file = new File(self::DIR_SSL . "/private/" . self::FILE_CA_KEY, true);
			$file->Chmod(660);
			$file->Chown("root", "ssl");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Creates a default client certificate
	 *
	 * @param string $username username
	 * @return void
	 * @throws EngineException
	 */

	public function CreateDefaultClientCertificate($username, $password, $verify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidPassword($password, $verify)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if (file_exists(COMMON_CORE_DIR . "/api/Organization.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/Organization.class.php");

			try {
				$organization = new Organization();

				$domain = $organization->GetDomain();
				$org_name = $organization->GetName();
				$org_unit = $organization->GetUnit();
				$city = $organization->GetCity();
				$region = $organization->GetRegion();
				$country = $organization->GetCountry();
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_WARNING);
			}
		}

		$domain = empty($domain) ? "example.com" : $domain;
		$org_name = empty($org_name) ? "Organization" : $org_name;
		$org_unit = empty($org_unit) ? "Unit" : $org_unit;
		$city = empty($city) ? "City" : $city;
		$region = empty($region) ? "Region" : $region;
		$country = empty($country) ? "Region" : $country;

		$this->SetRsaKeySize("2048");
		$this->SetOrganizationName($org_name);
		$this->SetOrganizationalUnit($org_unit);
		$this->SetEmailAddress($username . "@" . $domain);
		$this->SetLocality($city);
		$this->SetStateOrProvince($region);
		$this->SetCountryCode($country);
		$this->SetPurpose(Ssl::PURPOSE_CLIENT_LOCAL);
		$this->SetTerm(Ssl::TERM_10YEAR);

		$filename = $this->CreateCertificateRequest($username, self::PURPOSE_CLIENT_LOCAL);
		$filename = $this->SignCertificateRequest($filename);
		$this->ExportPkcs12($filename, $password, $verify);
	}

	/**
	 * Decrypts message.
	 *
	 * @param string $filename filename (including path) of the message to decrypt
	 * @return string $filename filename of decrypted message
	 * @throws EngineException
	 */

	public function DecryptMessage($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$shell = new ShellExec();
		$tmp_file = tempnam("/var/tmp", "decrypt");
		try {
			$file = new File($tmp_file);
			$file->Chown("webconfig", "webconfig");
			$file->Chmod("0600");
		} catch (Exception $e) {
			try {
				$tempfile = new File($tmp_file, true);
				$tempfile->Delete();
			} catch (Exception $e) {}
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		# Execute OpenSSL
		try {
			$folder = new Folder(Ssl::DIR_SSL . "/private/");
			$private_keys = $folder->GetListing();
			foreach ($private_keys as $private_key) {
				# Get public/private key pairs
				$key = Ssl::DIR_SSL . "/private/" . $private_key;
				$cert = Ssl::DIR_SSL . "/" . eregi_replace("-key", "-cert", $private_key);

				$args = "smime -decrypt -in $filename -recip $cert -inkey $key -out $tmp_file";
				if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true) == 0) {
					$file->Delete();
					return $tmp_file;
				}
			}
			$file->Delete();
			return;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Deletes certificate.
	 *
	 * @param string $filename certificate filename
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteCertificate($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# If the it's a PKCS12 file, delete it.
		if (ereg(self::TYPE_P12, $filename)) {
			try {
				$file = new File(self::DIR_SSL . "/" . $filename);
				if ($file->Exists())
					$file->Delete();
				# We don't delete private key if deleting the PKCS12
				return;
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_WARNING);
			}
		}

		# This function can be called on req or certs...find out which
		if (!ereg(self::TYPE_REQ, $filename)) {
			$req = $filename;
			$crt = ereg_replace("-cert.pem", "-req.pem", $filename); 
			$key = ereg_replace("-cert.pem", "-key.pem", $filename); 
			$p12 = ereg_replace("-cert.pem", ".p12", $filename); 
		} else {
			$req = ereg_replace("-req.pem", "-cert.pem", $filename); 
			$crt = $filename;
			$key = ereg_replace("-req.pem", "-key.pem", $filename); 
			$p12 = ereg_replace("-req.pem", ".p12", $filename); 
		}

		# Revoke and delete PKCS12 and private keys
		try {
			$this->RevokeCertificate($filename);
			$file = new File(self::DIR_SSL . "/$crt");
			if ($file->Exists())
				$file->Delete();
			$file = new File(self::DIR_SSL . "/$req");
			if ($file->Exists())
				$file->Delete();
			$file = new File(self::DIR_SSL . "/$p12");
			if ($file->Exists())
				$file->Delete();
			$file = new File(self::DIR_SSL . "/private/$key", true);
			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Deletes default client certificate.
	 *
	 * @param string $username username
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteDefaultClientCertificate($username)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$prefix = $this->_GetFilePrefix($username, self::PURPOSE_CLIENT_LOCAL);
		$certfile = $prefix . "-cert.pem";

		if (file_exists(self::DIR_SSL . "/" . $certfile))
			$this->DeleteCertificate($certfile);
	}

	/**
	 * Deletes the root CA certificate.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function DeleteCertificateAuthority()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		// SSL directory
		$dir = Ssl::DIR_SSL;

		// Default CA section name
		$ca = $this->configuration["ca"]["default_ca"];

		// Set SSL directory prefix
		// Sanitize/validate other configuration parameters before expansion.
		$this->configuration["global"]["dir"] = $dir;

		// Expand configuration variables
		$this->_ExpandConfiguration();

		// If the CA certification exists, delete it.
		try {
			$file = new File($this->configuration["$ca"]["certificate"], true);
			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}
	/**
	 * Checks the existence default client certificate.
	 *
	 * @param string $username username
	 * @return boolean true if certificate exists
	 * @throws EngineException
	 */

	public function ExistsDefaultClientCertificate($username)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$prefix = $this->_GetFilePrefix($username, self::PURPOSE_CLIENT_LOCAL);
		$certfile = $prefix . "-cert.pem";

		try {
			$file = new File(Ssl::DIR_SSL . "/$prefix-cert.pem");

			if ($file->Exists())
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}   
	}

	/**
	 * Checks the existence of certificate authority.
	 *
	 * @return boolean true if certificate authority has already been created
	 * @throws EngineException
	 */

	public function ExistsCertificateAuthority()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$this->GetCertificateAuthorityFilename();
		} catch (SslCertificateNotFoundException $e) {
			return false;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}   

		return true;
	}

	/**
	 * Checks the existence of local server certificate.
	 *
	 * @return boolean true if server certificate exists
	 * @throws EngineException
	 */

	public function ExistsSystemCertificate()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(Ssl::DIR_SSL . "/" . self::PREFIX_SERVER_LOCAL . "-0-cert.pem");

			if ($file->Exists())
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}   
	}

	/**
	 * Exports PKCS#12.
	 *
	 * @param string $filename certificate filename (must be unique among all certificates)
	 * @param string $password password used to encrypt PKCS#12 file
	 * @param string $verify password verify
	 * @return void
	 */

	public function ExportPkcs12($filename, $password, $verify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validation
		if (! $this->IsValidPassword($password, $verify)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		// SSL directory
		$dir = Ssl::DIR_SSL;

		// Default CA section name
		$ca = $this->configuration["ca"]["default_ca"];

		try {
			$file = new File("$dir/" . ereg_replace("-cert\\.pem$", ".p12", $filename));
			# If file exists, delete current one...we are renewing cert.
			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		try {
			$file = new File("$dir/$filename");
			if (!$file->Exists())
				throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CERT_MISSING, COMMON_NOTICE);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// Set SSL directory prefix
		// Sanitize/validate other configuration parameters before expansion.
		$this->configuration["global"]["dir"] = $dir;

		// Expand configuration variables
		$this->_ExpandConfiguration();

		// Save password to temporary file
		$passout = tempnam("/var/tmp", "openssl");

		try {
			$file = new File($passout);
			$file->Chown("root", "root");
			$file->Chmod("0600");
			$file->AddLines($password);
		} catch (Exception $e) {
			try {
				$passfile = new File($passout, true);
				$passfile->Delete();
			} catch (Exception $e) {}
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// Construct OpenSSL arguments
		$args = sprintf("pkcs12 -export -in %s -inkey %s -certfile %s -name \"%s\" -passout file:%s -out %s",
			"$dir/$filename",
			"$dir/private/" . ereg_replace("-cert\\.pem$", "-key.pem", $filename),
			$this->configuration[$ca]["certificate"],
			ereg_replace("-cert\\.pem$", ".p12", $filename), $passout,
			"$dir/" . ereg_replace("-cert\\.pem$", ".p12", $filename));

		// Execute OpenSSL
		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(Ssl::CMD_OPENSSL, $args, true);
		} catch (Exception $e) {
			try {
				$passfile = new File($passout, true);
				$passfile->Delete();
			} catch (Exception $e) {}
			throw new FileException($e->getMessage(), COMMON_WARNING);
		}

		try {
			$passfile = new File($passout, true);
			$passfile->Delete();
		} catch (Exception $e) {}

		if ($exitcode != 0) {
			$errstr = $shell->GetLastOutputLine();
			$output = $shell->GetOutput();
			try {
				$delfile = new File("$dir/" . ereg_replace("-cert\\.pem$", ".p12", $filename), true);
				$delfile->Delete();
			} catch (Exception $e) {}
			throw new SslExecutionException($errstr, COMMON_WARNING);
		}
	}

	/**
	 * Returns certificate attributes.
	 *
	 * @param string $filename certificate path and filename
	 * @return array list of certificate attributes
	 * @throws SslCertificateNotFoundException, EngineException
	 */

	public function GetCertificateAttributes($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# Make sure parsing is done in English
		$options['env'] = "LANG=en_US";

		# Create array and set some defaults
		$attributes = array("ca" => false, "server" => false, "smime" => false);

		# Ensure file/certificate exists
		$filename = self::DIR_SSL . "/" . $filename;
		$file = new File($filename);

		if (! $file->Exists())
			throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CERT_NOT_EXIST . $filename, COMMON_NOTICE);

		# Since we cannot 'peek' inside PKCS12 without the password, use the x509 cert data
		if (ereg(self::TYPE_P12, $filename)) 
			$filename = ereg_replace(self::TYPE_P12, "-cert.pem", $filename);
		
		if (ereg(self::TYPE_REQ, $filename))
			$type = 'req';
		else
			$type = 'x509';

		# Execute OpenSSL
		try {
			$shell = new ShellExec();
			# It would be nice to get this all from one call, but you can't count on fields set
			$args = "$type -in $filename -noout -dates";
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true, $options) == 0) {
				$output = $shell->GetOutput();
				$attributes['expireNotBefore'] = eregi_replace('notBefore=', '', $output[0]);
				$attributes['expireNotAfter'] = eregi_replace('notAfter=', '', $output[1]);
			}
			$args = "$type -in $filename -noout -modulus";
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true, $options) == 0) {
				$output = $shell->GetOutput();
				$attributes['key_size'] = strlen(eregi_replace('Modulus=', '', $output[0]))/2*8;
			}
			$args = "$type -in $filename -noout -subject";
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true, $options) == 0) {
				$output = $shell->GetOutput();
				$filter =  trim(eregi_replace('subject= /', '', $output[0]));
				$keyvaluepair = explode("/", $filter);
				// TODO: some attributes (like org_unit) are optional?
				// Should these attributes be set to ""?
				foreach ($keyvaluepair as $pair) {
					$split = explode("=", $pair);
					$key = $split[0];
					$value = $split[1];
					if ($key == "O")
						$attributes['org_name'] = $value;
					elseif ($key == "OU")
						$attributes['org_unit'] = $value;
					elseif ($key == "emailAddress")
						$attributes['email'] = $value;
					elseif ($key == "L")
						$attributes['city'] = $value;
					elseif ($key == "ST")
						$attributes['region'] = $value;
					elseif ($key == "C")
						$attributes['country'] = $value;
					elseif ($key == "CN")
						$attributes['common_name'] = $value;
				}
			}

			$args = "$type -in $filename -noout -purpose";
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true, $options) == 0) {
				$output = $shell->GetOutput();
				foreach ($output as $line) {
					$split = explode(" : ", $line);
					$key = $split[0];
					$value = isset($split[1]) ? $split[1] : "";
					# Just take one of the CA fields...it will tell us all we need to know.
					if ($key == "SSL server CA") {
						if ($value == "Yes")
							$attributes['ca'] = true;
					}
					if ($key == "SSL server") {
						if ($value == "Yes")
							$attributes['server'] = true;
					}
					if ($key == "S/MIME signing") {
						if ($value == "Yes")
							$attributes['smime'] = true;
					}
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		return $attributes;
	}

	/**
	 * Returns PEM contents.
	 *
	 * @param  string  $filename  Certificate filename
	 * @return  array  contents of certificate
	 * @throws  SslCertificateNotFoundException, EngineException
	 */

	public function GetCertificatePem($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$filename = Ssl::DIR_SSL . "/$filename";

		$file = new File($filename);
		if (! $file->Exists())
			throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CERT_MISSING, COMMON_NOTICE);

		try {
			$contents = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		return $contents;
	}

	/**
	 * Returns certificate text.
	 *
	 * @param  string  $filename  Certificate filename
	 * @return  array  contents of certificate text
	 * @throws  SslCertificateNotFoundException, EngineException
	 */

	public function GetCertificateText($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$filename = Ssl::DIR_SSL . "/$filename";

		# Make sure parsing is done in English
		$options['env'] = "LANG=en_US";

		$file = new File($filename);
		if (!$file->Exists())
			throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CERT_MISSING, COMMON_NOTICE);

		if (ereg(self::TYPE_REQ, $filename))
			$type = 'req';
		else
			$type = 'x509';

		try {
			$shell = new ShellExec();
			# It would be nice to get this all from one call, but you can't count on fields set
			$args = "$type -in $filename -noout -text";
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true, $options) == 0) {
				$contents = $shell->GetOutput();
			} else {
				$error = $shell->GetLastOutputLine();
				throw new SslExecutionException($error, COMMON_WARNING);
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		return $contents;
	}

	/**
	 * Returns a list of certificates on the server.
	 *
	 * @param string $type type of certificate
	 * @return array a list of certificates
	 */

	public function GetCertificates($type = self::TYPE_ALL)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$certs = array();

		try {
			$folder = new Folder(self::DIR_SSL);
			$files = $folder->GetListing();
			foreach ($files as $file) {
				try {
					if (ereg($type, $file))
						$certs[$file] = $this->GetCertificateAttributes($file);
				} catch (Exception $ignore) {
					continue;
				}
			}
		} catch (Exception $e) {
			//
		}

		if ($type == Ssl::TYPE_P12)
			$sorted = $this->SortCertificates($certs, 'email');
		else
			$sorted = $this->SortCertificates($certs);

		return $sorted;
	}

	/**
	 * Returns certificate authority attributes.
	 *
	 * @return array attributes of certificate authority
	 * @throws SslCertificateNotFoundException, EngineException
	 */

	public function GetCertificateAuthorityAttributes()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$ca_file = $this->GetCertificateAuthorityFilename();

		$attributes = $this->GetCertificateAttributes(basename($ca_file));
		$attributes['filename'] = $ca_file;

		return $attributes;
	}

	/**
	 * Returns certificate authority filename.
	 *
	 * @return string certificate authority filename
	 * @throws SslCertificateNotFoundException, EngineException
	 */

	public function GetCertificateAuthorityFilename()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		// SSL directory
		$dir = Ssl::DIR_SSL;

		// Default CA section name
		$ca = $this->configuration["ca"]["default_ca"];

		// Set SSL directory prefix
		// Sanitize/validate other configuration parameters before expansion.
		$this->configuration["global"]["dir"] = $dir;

		// Expand configuration variables
		$this->_ExpandConfiguration();

		// If the CA certification doesn't exist, throw and exception
		$file = new File($this->configuration["$ca"]["certificate"], true);
		if (!$file->Exists())
			throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CA_MISSING, COMMON_NOTICE);

		return $this->configuration["$ca"]["certificate"];
	}

	/**
	 * Returns RSA key size options.
	 *
	 * @return array
	 */

	public function GetRSAKeySizeOptions()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$options = array(
			512=>"512b",
			1024=>"1024b",
			2048=>"2048b",
			4096=>"4096b"
		);

		return $options;
	}

	/**
	 * Returns server certificate attributes.
	 *
	 * @return array attributes of server certificate
	 * @throws SslCertificateNotFoundException, EngineException
	 */

	public function GetSystemCertificateAttributes()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$filename = self::PREFIX_SERVER_LOCAL . "-0-cert.pem";

		$attributes = $this->GetCertificateAttributes($filename);

		$attributes['filename'] = self::DIR_SSL . "/" . $filename;

		return $attributes;
	}

	/**
	 * Returns certificate signing options.
	 *
	 * @return array
	 */

	public function GetSigningOptions()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$options = array(
			self::SIGN_SELF=>SSL_LANG_SIGN_SELF,
			self::SIGN_3RD_PARTY=>SSL_LANG_SIGN_3RD_PARTY
		);

		return $options;
	}

	/**
	 * Returns certificate term options.
	 *
	 * @return array
	 */

	public function GetTermOptions()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

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
	 * @return void
	 * @throws SslCertificateNotFoundException, EngineException
	 */

	public function Initialize($hostname = null, $domain = null, $orgname =  null, $unit = null, $city = null, $region = null, $country = null)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$organization = new Organization();

			$hostname = empty($hostname) ? $organization->GetInternetHostname() : $hostname;
			$domain = empty($domain) ? $organization->GetDomain() : $domain;
			$orgname = empty($orgname) ? $organization->GetName() : $orgname;
			$unit = empty($unit) ? $organization->GetUnit() : $unit;
			$city = empty($city) ? $organization->GetCity() : $city;
			$region = empty($region) ? $organization->GetRegion() : $region;
			$country = empty($country) ? $organization->GetCountry() : $country;

			if (empty($hostname)) {
				$hostnameobj = new Hostname();
				$hostname = $hostnameobj->Get();
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$ca_exists = $this->ExistsCertificateAuthority();

		if (!$ca_exists) {
			$ssl = new Ssl();
			$ssl->SetRsaKeySize(2048);
			$ssl->SetCommonName("ca." . $domain);
			$ssl->SetOrganizationName($orgname);
			$ssl->SetOrganizationalUnit($unit);
			$ssl->SetEmailAddress("security@" . $domain);
			$ssl->SetLocality($city);
			$ssl->SetStateOrProvince($region);
			$ssl->SetCountryCode($country);
			$ssl->SetTerm(Ssl::TERM_10YEAR); 

			$ssl->CreateCertificateAuthority();
		}

		$syscert_exists = $this->ExistsSystemCertificate();

		if (!$syscert_exists) {
			$ssl = new Ssl();
			$ssl->SetRsaKeySize(2048);
			$ssl->SetOrganizationName($orgname);
			$ssl->SetOrganizationalUnit($unit);
			$ssl->SetEmailAddress("security@" . $domain);
			$ssl->SetLocality($city);
			$ssl->SetStateOrProvince($region);
			$ssl->SetCountryCode($country);
			$ssl->SetTerm(Ssl::TERM_10YEAR); 
			$ssl->SetPurpose(Ssl::PURPOSE_SERVER_LOCAL);

			// Create certificate
			$filename = $ssl->CreateCertificateRequest($hostname, Ssl::PURPOSE_SERVER_LOCAL);
			$ssl->SignCertificateRequest($filename);
		}
	}

	/**
	 * Imports signed certificate.
	 *
	 * @param string $filename the REQ filename
	 * @param string $cert the certificate contents
	 * @return void
	 * @throws SslCertificateNotFoundException, EngineException
	 */

	public function ImportSignedCertificate($filename, $cert)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$cert = trim($cert);

		# Validation
		if (! $this->IsValidCertificate($cert)) {
			throw new ValidationException(SSL_LANG_ERRMSG_INVALID_CERT);
		}
		
		# Put cert in array for DumpContentsFromArray method
		$cert_in_array = array($cert);

		$file = new File(Ssl::DIR_SSL . "/" . $filename);
		if (!$file->Exists())
			throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CERT_MISSING, COMMON_NOTICE);

		$cert_filename = ereg_replace("-req.pem", "-cert.pem", $filename);
		try {
			$cert_file = new File(Ssl::DIR_SSL . "/" . $cert_filename);
			if ($cert_file->Exists())
				$cert_file->Delete();
			$cert_file->Create("root", "root", "0600");
			$cert_file->DumpContentsFromArray($cert_in_array);
			# Delete request
			$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Checks to see if PKCS12 file already exists for a x509 certificate.
	 *
	 * @param string $filename x509 certificate filename
	 * @return boolean true if PKCS12 file exists
	 * @throws EngineException
	 */

	public function IsPkcs12Exist($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
		try {
			$pkcs12 = ereg_replace("-cert\\.pem", ".p12", $filename);
			$file = new File(self::DIR_SSL . "/" . $pkcs12);
			if ($file->Exists())
				return true;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
		return false;
	}

	/**
	 * Checks to see if certificate is signed from the resident CA.
	 *
	 * @param  string  $filename  the certificate filename
	 * @return  boolean  true if signed locally 
	 * @throws  SslCertificateNotFoundException, EngineException
	 */

	public function IsSignedByLocalCA($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# CA
		$ca = self::DIR_SSL . "/" . self::FILE_CA_CRT;

		# Cert
		$cert = self::DIR_SSL . "/" . $filename;

		# Check that CA exists
		$file = new File($ca);
		if (! $file->Exists())
			throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CERT_MISSING, COMMON_NOTICE);

		# Check that certificate exists
		$file = new File($cert);
		if (! $file->Exists())
			throw new SslCertificateNotFoundException(SSL_LANG_ERRMSG_CERT_MISSING, COMMON_NOTICE);

		try {
			$shell = new ShellExec();
			# Get subject of CA
			$args = "x509 -in $ca -noout -subject";
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true) == 0) {
				$subject = trim(ereg_replace("^subject=", "", $shell->GetLastOutputLine()));
				# Now compare against issuer of certificate
				$args = "x509 -in $cert -noout -issuer ";
				if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true) == 0) {
					$issuer = trim(ereg_replace("^issuer=", "", $shell->GetLastOutputLine()));
					# If we get here, compare the two...a match returns true
					if ($issuer == $subject)
						return true;
					else
						return false;
				} else {
					$error = $shell->GetLastOutputLine();
					throw new SslExecutionException($error, COMMON_WARNING);
				}
			} else {
				$error = $shell->GetLastOutputLine();
				throw new SslExecutionException($error, COMMON_WARNING);
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
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
	 * @return  string  $filename of signed certificate
	 * @throws  ValidationException, EngineException
	 */

	public function RenewCertificate($filename, $term, $password = null, $verify = null, $pkcs12 = null)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# Validation
		if ($pkcs12 != null && !$this->IsValidPassword($password, $verify)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}
		
		try {
			# Need attributes of exiting cert
			$cert = $this->GetCertificateAttributes($filename);
			# Set start date to now (YYMMDDHHMMSSZ)
			$timestamp = time();
			$start_date = date("ymdhis\Z", $timestamp);
// FIXME
			if (isset($cert['smime']) && $cert['smime'])
				$this->SetPurpose(self::PURPOSE_CLIENT_CUSTOM);
			else
				$this->SetPurpose(self::PURPOSE_SERVER_LOCAL);
			# Add on existing cert's expiry
			$timestamp = strtotime($cert['expireNotAfter']) + ($term*24*60*60);
			$end_date = date("ymdhis\Z", $timestamp);
			$this->SetStartDate($start_date);
			$this->SetEndDate($end_date);
			$this->SetOrganizationName($cert['org_name']);
			$this->SetOrganizationalUnit($cert['org_unit']);
			# TODO - this may be blank...OK?
			if ($cert['email'])
				$this->SetEmailAddress($cert['email']);
			$this->SetLocality($cert['city']);
			$this->SetStateOrProvince($cert['region']);
			$this->SetCountryCode($cert['country']);

			# Force filename
// FIXME
			$req_filename = $this->CreateCertificateRequest($cert['common_name'], $filename);
			if (! $this->IsSignedByLocalCA($filename))
				return;
			$this->RevokeCertificate($filename);
			$crt_filename = $this->SignCertificateRequest($req_filename, true);
			if ($pkcs12) {
				$this->ExportPkcs12($crt_filename, $password, $verify);
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Revokes an SSL certificate.
	 *
	 * @param  string  $filename  Certificate filename
	 * @return  void
	 * @throws  EngineException
	 */

	public function RevokeCertificate($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$subject = null;
		$id = null;

		# Don't revoke a CA cert...it's not in the index
		if ($filename == self::FILE_CA_CRT)
			return;
		# Requests don't have to be revoked
		if (ereg(self::TYPE_REQ, $filename))
			return;
		try {
			# Get Subject from cert
			$shell = new ShellExec();
			$args = "x509 -in " . self::DIR_SSL . "/$filename -noout -subject";
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true) == 0) {
				$output = $shell->GetOutput();
				$subject = trim(eregi_replace('subject=', '', $output[0]));
			} else {
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
			}
			# Get cert index
			$file = new File(self::DIR_SSL . "/" . self::FILE_INDEX);
			if (! $file->Exists())
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
			$lines = $file->GetContentsAsArray();
			foreach ($lines as $line) {
				# Match subject
				$parts = explode("\t", $line);
				if ($parts[0] == "V" && $parts[5] == $subject) {  # V = Valid
					$id = $parts[3];
					break;
				}
			}
			
			if ($id != null) {
				$args = "ca -revoke " . self::DIR_SSL . "/certs/$id.pem -config " . self::DIR_SSL . "/" . self::FILE_CONF;
				if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true) == 0) {
					# Revoke was successful
					$file = new File(self::DIR_SSL . "/" . ereg_replace("-cert.pem", ".p12", $filename));
					if ($file->Exists())
						$file->Delete();
					return;
				} else {
					$error = $shell->GetLastOutputLine();
					throw new SslExecutionException($error, COMMON_WARNING);
				}
			} else {
				# May not have been signed...just log.
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Set organization name
	 *
	 * @param  string  $organization  Organization name
	 * @return void
	 */

	public function SetOrganizationName($organization, $default = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		if (!$default)
			$this->configuration[$req_dname]["organizationName_default"] = $organization;
		else
			$this->_SetDefaultValue($req_dname, "organizationName_default", $organization);
	}

	/**
	 * Set organizational unit name
	 *
	 * @param  string  $unit  Organizational unit (ie. Marketing, IT etc.)
	 * @return  void
	 */

	public function SetOrganizationalUnit($unit, $default = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		if (!$default)
			$this->configuration[$req_dname]["organizationalUnitName_default"] = $unit;
		else
			$this->_SetDefaultValue($req_dname, "organizationalUnitName_default", $unit);
	}

	/**
	 * Set email address
	 *
	 * @param string email Email address
	 * @return void
	 * @throws  ValidationException
	 */

	public function SetEmailAddress($email, $default = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (! $this->IsValidEmail($email)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		if (!$default)
			$this->configuration[$req_dname]["emailAddress_default"] = $email;
		else
			$this->_SetDefaultValue($req_dname, "emailAddress_default", $email);
	}

	/**
	 * Set locality
	 *
	 * @param string locality Locality (city, district)
	 * @return void
	 */

	public function SetLocality($locality, $default = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		if (!$default)
			$this->configuration[$req_dname]["localityName_default"] = $locality;
		else
			$this->_SetDefaultValue($req_dname, "localityName_default", $locality);
	}

	/**
	 * Set State or Province
	 *
	 * @param string state State or Province
	 * @return void
	 */

	public function SetStateOrProvince($state, $default = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		if (!$default)
			$this->configuration[$req_dname]["stateOrProvinceName_default"] = $state;
		else
			$this->_SetDefaultValue($req_dname, "stateOrProvinceName_default", $state);
	}

	/**
	 * Set country code
	 *
	 * @param string code Country code
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	public function SetCountryCode($code, $default = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$countryobj = new Country();
		$list = $countryobj->GetList();

		if (! isset($list[$code]))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - Country");

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		if (!$default)
			$this->configuration[$req_dname]["countryName_default"] = $code;
		else
			$this->_SetDefaultValue($req_dname, "countryName_default", $code);
	}

	/**
	 * Set RSA key size
	 *
	 * @param string name Common name
	 * @return void
	 */

	public function SetRSAKeySize($key_size)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		$this->configuration["req"]["default_bits"] = $key_size;
	}

	/**
	 * Set common name
	 *
	 * @param string name Common name
	 * @return void
	 */

	public function SetCommonName($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidCommonName($name)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (!isset($this->configuration["req"]["distinguished_name"]))
			$this->configuration["req"]["distinguished_name"] = "req_distinguished_name";

		$req_dname = $this->configuration["req"]["distinguished_name"];

		$this->configuration[$req_dname]["commonName_default"] = $name;
	}

	/**
	 * Set term.
	 *
	 * @param  int  $term  the certificate expiry term (in days)
	 * @return void
	 */

	public function SetTerm($term)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		$ca = $this->configuration["ca"]["default_ca"];

		$this->configuration[$ca]["default_days"] = $term;
	}

	/**
	 * Set start date.
	 *
	 * @param  string  $startdate  start date (used for renewals).  The format is
	 *							 YYMMDDHHMMSSZ, where "Z" is the capital letter "Z"
	 * @return void
	 */

	public function SetStartDate($startdate)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		$ca = $this->configuration["ca"]["default_ca"];

		$this->configuration[$ca]["default_startdate"] = $startdate;
	}

	/**
	 * Sets the purpose of the key/pair (see class overview).
	 *
	 * @param string $purpose purpose of certificate key/pair
	 * @return void
	 */

	public function SetPurpose($purpose)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($purpose === self::PURPOSE_CLIENT_LOCAL)
			$nscerttype = "client, email, objsign";
		else if ($purpose === self::PURPOSE_CLIENT_CUSTOM)
			$nscerttype = "client, email, objsign";
		else if ($purpose === self::PURPOSE_SERVER_LOCAL)
			$nscerttype = "server";
		else if ($purpose === self::PURPOSE_SERVER_CUSTOM)
			$nscerttype = "server";
		else
			throw new ValidationException(SSL_LANG_PURPOSE . " - " . LOCALE_LANG_INVALID);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		$this->_SetCertificateNsCertType($nscerttype);
	}

	/**
	 * Set end date.
	 *
	 * @param  string  $enddate  override the term variable by setting the end date (used for renewals).  The format is
	 *						   YYMMDDHHMMSSZ, where "Z" is the capital letter "Z"
	 * @return void
	 */

	public function SetEndDate($enddate)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		$ca = $this->configuration["ca"]["default_ca"];

		$this->configuration[$ca]["default_enddate"] = $enddate;
	}

	/**
	 * Sign an SSL certificate request.
	 *
	 * @param  string  $filename  Certificate filename (must be unique among all certificates)
	 * @param  boolean  $renew  Flag indicating whether this is a certificate renewal
	 * @return  string  $filename of signed certificate
	 * @throws SslCertificateExistsException, EngineException
	 */

	public function SignCertificateRequest($filename, $renew = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		// SSL directory
		$dir = Ssl::DIR_SSL;

		// Default CA section name
		$ca = $this->configuration["ca"]["default_ca"];

		if (!ereg(self::TYPE_REQ, $filename))
			throw new EngineException(SSL_LANG_ERRMSG_NOT_A_REQ, COMMON_WARNING);

		if (!$renew) {
			try {
				$file = new File("$dir/" . ereg_replace("-req\\.pem$", "-cert.pem", $filename));
				if ($file->Exists())
					throw new SslCertificateExistsException(SSL_LANG_ERRMSG_CERT_EXISTS, COMMON_WARNING);
			} catch (Exception $e) {
				throw new FileException($e->getMessage(), COMMON_WARNING);
			}
		}

		// Set SSL directory prefix
		// Sanitize/validate other configuration parameters before expansion.
		$this->configuration["global"]["dir"] = $dir;

		// Expand configuration variables
		$this->_ExpandConfiguration();

		// Save working configuration to a temporary file.  This is to be read
		// later by the OpenSSL binary to generate a new certificate request.
		$config = tempnam("/var/tmp", "openssl");

		try {
			$this->_SaveConfiguration($config);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		// Construct OpenSSL arguments
		$args = sprintf("ca -extensions v3_req -days %d -out %s -batch -config %s -infiles %s ",
			$this->configuration["$ca"]["default_days"],
			"/dev/null", $config,
			"$dir/$filename");

		// Execute OpenSSL
		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(Ssl::CMD_OPENSSL, $args, true);
		} catch (Exception $e) {
			try {
				$configfile = new File($config, true);
				$configfile->Delete();
			} catch (Exception $e) {}
			throw new FileException($e->getMessage(), COMMON_WARNING);
		}

		try {
			$configfile = new File($config, true);
			$configfile->Delete();
		} catch (Exception $e) {}

		if ($exitcode != 0) {
			$errstr = $shell->GetLastOutputLine();
			$output = $shell->GetOutput();

			Logger::Syslog(self::LOG_TAG, "SSL signing error");

			foreach ($output as $line)
				Logger::Syslog(self::LOG_TAG, " ... $line");

			throw new SslExecutionException(SSL_LANG_ERRMSG_FAILED_SIGNING, COMMON_WARNING);
		}

		try {
			$file = new File($this->configuration[$ca]["serial"] . ".old");
			$id = chop($file->GetContents());
			$file = new File($this->configuration[$ca]["new_certs_dir"] . "/$id.pem");
			$file->CopyTo("$dir/" . ereg_replace("-req\\.pem$", "-cert.pem", $filename));
			# We do not need the certificate request anymore
			$file = new File("$dir/$filename");
			$file->Delete();
		} catch (Exception $e) {
			throw new FileException($e->getMessage(), COMMON_WARNING);
		}

		return ereg_replace("-req\\.pem$", "-cert.pem", $filename);
	}

	/**
	 * Sorts certificates.
	 *
	 * @param string $certs TODO
	 * @param string $field TODO
	 * @return array TODO
	 */

	public function SortCertificates($certs, $field = 'common_name')
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

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
	 * @param  string  $commonname Common name to use (secure.pointclark.net, jim@pointclark.com, etc)
	 * @param  string  $filename  Certificate filename
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	public function UpdateCertificate($commonname, $filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidCommonName($commonname)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		try {
			# Need expire start/stop of exiting cert
			$cert = $this->GetCertificateAttributes($filename);
			$this->SetStartDate(date("ymdhis\Z", strtotime($cert['expireNotBefore'])));
			$this->SetEndDate(date("ymdhis\Z", strtotime($cert['expireNotAfter'])));
			# Need purpose
// FIXME
			if (isset($cert['smime']) && $cert['smime'])
				$this->SetPurpose(self::PURPOSE_CLIENT_CUSTOM);
			else
				$this->SetPurpose(self::PURPOSE_SERVER_LOCAL);

			# Force filename
			$req_filename = $this->CreateCertificateRequest($commonname, $filename);
			if (! $this->IsSignedByLocalCA($filename))
				return;
			$this->RevokeCertificate($filename);
			$crt_filename = $this->SignCertificateRequest($req_filename, true);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Verifies signature of SMIME encoded email.
	 *
	 * @param string $filename filename (including path)
	 * @return boolean true if signature is verified
	 * @throws EngineException
	 */

	public function VerifySmime($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# Execute OpenSSL
		try {
			$shell = new ShellExec();
			$args = "smime -verify -in $filename -CAfile " . self::DIR_SSL . "/" . self::FILE_CA_CRT;
			if ($shell->Execute(Ssl::CMD_OPENSSL, $args, true) == 0)
				return true;

			return false;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
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
	 * @return  boolean  true if password is valid
	 */

	public function IsValidPassword($password, $verify)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($password != $verify) {
			$this->AddValidationError(SSL_LANG_ERRMSG_PASSWORD_VERIFY, __METHOD__ ,__LINE__);
			return false;
		}
		// Must begin w/a letter/number
		$state = preg_match('/^([a-zA-Z0-9]+[0-9a-zA-Z\.\-\!\@\#\$\%\^\&\*\(\)_]*)$/', $password);

		if (!$state) {
			$this->AddValidationError(SSL_LANG_ERRMSG_PASSWORD_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validation routine for common name.
	 *
	 * @param  string  $name  Certificate's common name
	 * @return  boolean  true if common name is valid
	 */

	public function IsValidCommonName($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $name) {
			$this->AddValidationError(SSL_LANG_ERRMSG_COMMON_NAME_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validation routine for email.
	 *
	 * @param  string  $email  Certificate e-mail
	 * @return  boolean  true if email is valid
	 */

	public function IsValidEmail($email)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!eregi("^[a-z0-9\._-]+@+[a-z0-9\._-]+$", $email)) {
			$this->AddValidationError(SSL_LANG_ERRMSG_EMAIL_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		return true;
	}

	/**
	 * Validation routine for certificate.
	 *
	 * @param  string  $cert  certificate contents
	 * @return  boolean  true if cert is valid
	 */

	public function IsValidCertificate($cert)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $cert) {
			$this->AddValidationError(SSL_LANG_ERRMSG_CERT_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		if (! preg_match("/^-----BEGIN .*-----/", $cert)) {
			$this->AddValidationError(SSL_LANG_ERRMSG_EMAIL_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		if (! preg_match("/-----END .*-----$/", $cert)) {
			$this->AddValidationError(SSL_LANG_ERRMSG_EMAIL_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		return true;
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
	 * @throws EngineException
	 */

	private function _GetFilePrefix($commonname, $purpose)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($purpose === self::PURPOSE_CLIENT_LOCAL) {

			$prefix = self::PREFIX_CLIENT_LOCAL . "-" . $commonname;

		} else if ($purpose === self::PURPOSE_SERVER_LOCAL) {

			$prefix = self::PREFIX_SERVER_LOCAL . "-0";

		} else if (($purpose === self::PURPOSE_CLIENT_CUSTOM) || ($purpose ===  self::PURPOSE_SERVER_CUSTOM)) {
			try {
				$folder = new Folder(Ssl::DIR_SSL);

				if (! $folder->Exists())
					throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);

				$files = $folder->GetListing();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			$next = 0;
			$match = array();
					
			foreach ($files as $file) {
				if (ereg("$prefix([0-9]*)-cert\\.pem", $file, $match)) {
					if ((int)$match[1] >= $next)
						$next = (int)$match[1] + 1;
				}
			}

			$prefix = self::PREFIX_CUSTOM . "-" . $next;
		}

		return $prefix;
	}

	/**
	 * Expands/substitutes configuration variables.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	private function _ExpandConfiguration()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		foreach ($this->configuration as $section => $entries) {
			foreach ($entries as $key => $value) {
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
	 * @param string $filename Configuration filename
	 * @return array config
	 * @throws EngineException
	 */

	private function _LoadConfiguration($filename = null)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($filename == null)
			$filename = Ssl::DIR_SSL . "/" . Ssl::FILE_CONF;

		try {
			$file = new File($filename, true);
			$contents = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}

		$config = array();
		$section = "global";
		$wash_pattern[] = "/\s*=\s*/";
		$wash_replace[] = "=";
		$wash_pattern[] = "/\[\s*(\w+)\s*\]/";
		$wash_replace[] = "[\\1]";
		$wash_pattern[] = "/\"/";
		$wash_replace[] = "";

		foreach ($contents as $line) {
			$l = chop($line);
			if (!strlen($l)) continue;

			if (($i = strpos($l, "#")) !== false) {
				if ($i == 0) continue;
				$l = chop(substr($l, 0, $i));
				if (!strlen($l)) continue;
			}

			$l = preg_replace($wash_pattern, $wash_replace, $l);

			if (($i = strpos($l, "=")) === false) {
				$section = preg_replace("/\[(\w+)\s*\]/", "\\1", $l);
				continue;
			}

			$config[$section][substr($l, 0, $i)] = substr($l, $i + 1);
		}

		$this->is_loaded = true;

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
	 * @return void
	 * @throws EngineException
	 */

	private function _SaveConfiguration($filename = null, $contents = null)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if ($filename == null)
			$filename = Ssl::DIR_SSL . "/" . Ssl::FILE_CONF;

		if ($contents == null)
			$contents = $this->configuration;

		$expanded[] = "# OpenSSL configuration generated by:";
		$expanded[] = sprintf("# %s:%d %s($filename)", basename(__FILE__), __LINE__, __METHOD__);

		foreach ($contents as $section => $entries) {
			if ($section != "global") $expanded[] = "[ $section ]";
	
			foreach ($entries as $key => $value)
				$expanded[] = sprintf("%-30s = %s", $key, $value);

			$expanded[] = "";
		}

		$expanded[] = "# End of $filename";

		try {
			$file = new File($filename, true);
			if (!$file->Exists())
				$file->Create("root", "root", "0600");
			else {
				$file->Chown("root", "root");
				$file->Chmod("0600");
			}
			$file->DumpContentsFromArray($expanded);
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets a key to value.
	 *
	 * @access private
	 * @param string $section Configuration section
	 * @param string $key Configuration keyword
	 * @param string $value Keyword value
	 * @return void
	 * @throws EngineException
	 */

	private function _SetDefaultValue($section, $key, $value)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		$this->configuration["$section"]["$key"] = $value;

		// Save configuration
		try {
			$this->_SaveConfiguration();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets certificate type (nsCertType).
	 *
	 * Valid types: client, server, email, objsign, reserved, sslCA, emailCA, objCA.
	 *
	 * @access private
	 * @param string $type valid nsCertType
	 * @return void
	 * @throws EngineException
	 */

	private function _SetCertificateNsCertType($type)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->is_loaded)
			$this->configuration = $this->_LoadConfiguration();

		if (!isset($this->configuration["req"]["req_extensions"]))
			$this->configuration["req"]["req_extensions"] = "v3_req";

		$req_v3 = $this->configuration["req"]["req_extensions"];

		$this->configuration[$req_v3]["nsCertType"] = $type;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
