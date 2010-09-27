<?
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * SOAP server.
 * This is the all-mighty Webconfig SOAP server.  This one PHP file is called
 * to handle SOAP requests for all defined Webconfig API classes.  Any un-
 * caught exceptions are sent back to the caller as a SOAP fault.
 *
 * To define a class for SOAP export, do the following:
 *
 *   -Create a WSDL file either by hand, using the Zend IDE, or another tool.
 *    Put the WSDL file in /var/webconfig/wsdl using the name of the class and
 *    the extension .wsdl: Suva.class.php -> Suva.wsdl (case sensitive)
 *
 *   -Add the class name to the global $classes array below.
 *    Each class entry contains another array of method/ACL pairs.  You must
 *    add each class method that is to be exported for SOAP.  The value of
 *    each method is an ACL (access control level).  This makes it possible
 *    to restrict access to individual class methods.
 *
 * Invoke this script using the syntax:
 *   http(s)://.../soap.php?classname=<class name>
 *
 * To retrieve a class' WSDL, either call this script using HTTP GET or append
 * the 'wsdl' parameter:
 *   http(s)://.../soap.php?classname=<class name>&amp;wsdl
 *
 * @author Point Clark Networks
 * @license GNU Public License
 * @package Engine
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

parse_str($_SERVER['QUERY_STRING']);

if (!isset($classname)) {
	header('Location: /index.php');
	exit;
}

define('ACL_PUBLIC', 1);
define('ACL_PRIVATE', 2);

$classes = array(
	'Console'		=> array('GetLocaleStrings'				=> ACL_PUBLIC,
							 'GetSystemStats'				=> ACL_PUBLIC,
							 'LoadInterface'				=> ACL_PRIVATE,
							 'SaveInterface'				=> ACL_PRIVATE,
							 'AuthCheck'					=> ACL_PRIVATE),
	'DnsMasq'		=> array('IsInstalled'					=> ACL_PUBLIC,
							 'SetDomainSuffix'				=> ACL_PRIVATE,
							 'EnableDhcpAutomagically'		=> ACL_PUBLIC,
							 'GetDhcpState'					=> ACL_PUBLIC,
							 'GetActiveLeases'				=> ACL_PUBLIC,
							 'GetStaticLeases'				=> ACL_PUBLIC),
	'Firewall'		=> array('GetMode'						=> ACL_PUBLIC,
							 'SetMode'						=> ACL_PRIVATE,
							 'GetInterfaceRole'				=> ACL_PUBLIC,
							 'RemoveInterfaceRole'			=> ACL_PRIVATE,
							 'IsInstalled'					=> ACL_PUBLIC,
							 'Restart'						=> ACL_PRIVATE),
	'Hostname'		=> array('Get'							=> ACL_PUBLIC,
							 'Set'							=> ACL_PRIVATE,
							 'GetActual'					=> ACL_PUBLIC,
							 'GetDomain'					=> ACL_PUBLIC),
	'Iface'			=> array('SetInterface'					=> ACL_PUBLIC,
							 'DeleteConfig'					=> ACL_PRIVATE,
							 'Enable'						=> ACL_PRIVATE,
							 'Disable'						=> ACL_PRIVATE,
							 'GetType'						=> ACL_PUBLIC,
							 'GetBootProtocol'				=> ACL_PUBLIC,
							 'GetTypeName'					=> ACL_PUBLIC,
							 'GetLiveIp'					=> ACL_PUBLIC,
							 'GetLinkStatus'				=> ACL_PUBLIC,
							 'GetSpeed'						=> ACL_PUBLIC,
							 'IsValid'						=> ACL_PUBLIC,
							 'IsConfigured'					=> ACL_PUBLIC),
	'IfaceManager'	=> array('GetInterfaces'				=> ACL_PUBLIC,
							 'GetInterfaceCount'			=> ACL_PUBLIC,
							 'GetVendorDetails'				=> ACL_PUBLIC),
	'Network'		=> array('IsInstalled'					=> ACL_PUBLIC,
							 'Restart'						=> ACL_PRIVATE),
	'Resolver'		=> array('GetNameservers'				=> ACL_PUBLIC,
							 'SetNameservers'				=> ACL_PRIVATE),
	'Samba'			=> array('IsInstalled'					=> ACL_PUBLIC,
							 'Restart'						=> ACL_PRIVATE),
	'Snort'			=> array('IsInstalled'					=> ACL_PUBLIC,
							 'Restart'						=> ACL_PRIVATE),
	'Squid'			=> array('IsInstalled'					=> ACL_PUBLIC,
							 'Restart'						=> ACL_PRIVATE),
	'Stats'			=> array('GetRelease'					=> ACL_PUBLIC,
							 'GetInterfaces'				=> ACL_PUBLIC,
							 'GetInterfaceInfo'				=> ACL_PUBLIC,
							 'GetDiskStats'					=> ACL_PUBLIC,
							 'GetInterfaceStats'			=> ACL_PUBLIC,
							 'GetProcessCount'				=> ACL_PUBLIC,
							 'GetMemStats'					=> ACL_PUBLIC),
	'System'		=> array('Shutdown'						=> ACL_PRIVATE,
							 'Restart'						=> ACL_PRIVATE),
	'Syswatch'		=> array('IsInstalled'					=> ACL_PUBLIC,
							 'Restart'						=> ACL_PRIVATE,
							 'ReconfigureNetworkSettings'	=> ACL_PRIVATE),
);

// Install custom error handler
function soap_error_handler($errno, $errstr, $errfile, $errline)
{
	$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
	$server->fault('Server', "PHP Error: $errno", "$errfile:$errline", strip_tags(html_entity_decode($errstr)));
	exit;
}

set_error_handler('soap_error_handler');

// Search for the class name passed as an URL parameter
foreach ($classes as $name => $methods) {
	if ($name != $classname) continue;

	// Class name is valid

	// If we've been invoked by a HTTP GET or we find 'wsdl' as a
	// URL parameter, return the class' WSDL file (if present).
	if (isset($wsdl) || !isset($HTTP_RAW_POST_DATA)) {
		if (!file_exists("../wsdl/$classname.wsdl")) {
			header('Content-Type: text/plain; charset=ISO-8859-1');
			echo "Oops!  $classname.wsdl not found.\n";
			echo "You must create $classname.wsdl before you can use this class.\n";
			exit;
		}

		// Return WSDL file
		header('Content-Type: text/xml; charset=ISO-8859-1');
		readfile("../wsdl/$classname.wsdl");
		exit;
	}

	// We need to examine the SOAP request in order to determine which
	// method the caller is trying to invoke.  To do this we'll load the RAW POST
	// data into a DOM object and look for elements that belong to the class'
	// name space.
	$dom = new DOMDocument('1.0');

	try {
		$dom->loadXML($HTTP_RAW_POST_DATA);
	}
	catch(DOMException $e) {
		// Fault: XML parse error
		$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
		$server->fault('Server', $e->getMessage(), 'DOMDocument::loadXML',
			strip_tags(html_entity_decode($e->__ToString())));
		exit;
	}

	// There should only be one element found (the method name) using this...
	// XXX: As far as I understand SOAP, only one element should be found,
	// XXX: so far this has been the case in all the SOAP calls I've seen.
	$nodes = $dom->getElementsByTagNameNS($classname, '*');

	if ($nodes->length != 1) {
		// Fault: Invalid node count
		$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
		$server->fault('Server', 'Invalid node count');
		exit;
	}

	// Look for the method name in our global array
	foreach ($methods as $method => $acl_mask) {
		if ($method != $nodes->item(0)->localName) continue;

		// Set some SOAP environment variables (for use by the Webconfig API)
		$_SOAP['REQUEST'] = "$classname::$method";

		// Validate against $acl_mask and remote address
		if ($acl_mask & ACL_PRIVATE) {
			require_once('../api/PosixUser.class.php');

			$user = new PosixUser('root');

			if (!isset($_SERVER['PHP_AUTH_PW']) ||
				!$user->CheckPassword($_SERVER['PHP_AUTH_PW'])) {

				// Fault: Authentication failure
				$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
				$server->fault('Server', 'Access denied');
				exit;
			}
		}
		else
		{
			// Authenticate normal users for public access methods if they're not local
			if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
				require_once('../api/PosixUser.class.php');

				if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
					// Fault: Authentication failure
					$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
					$server->fault('Server', 'Access denied');
					exit;
				}

				$user = new PosixUser($_SERVER['PHP_AUTH_USER']);

				if (!$user->CheckPassword($_SERVER['PHP_AUTH_PW'])) {
					// Fault: Authentication failure
					$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
					$server->fault('Server', 'Access denied');
					exit;
				}
			}
		}

		// Pull in the class declaration
		require_once("../api/$classname.class.php");

		// Handle the SOAP request
		if(!isset($_SOAP['CLASS_MAP']))
			$server = new SoapServer("../wsdl/$classname.wsdl");
		else
			$server = new SoapServer("../wsdl/$classname.wsdl", array('classmap' => $_SOAP['CLASS_MAP']));

		$server->setClass($classname);

		try {
			$server->handle();
        } catch (Exception $e) {
			// Fault: Exception
			$server->fault('Server', strip_tags(html_entity_decode($e->getMessage())),
				"$classname::$method", strip_tags(html_entity_decode($e->__ToString())));
		}

		// Request complete
		exit;
	}

	// Fault: Invalid method
	$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
	$server->fault('Server', 'Invalid method name');
	exit;
}

// Fault: Invalid class name
$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
$server->fault('Server', 'Invalid class name');

// vi: ts=4
?>
