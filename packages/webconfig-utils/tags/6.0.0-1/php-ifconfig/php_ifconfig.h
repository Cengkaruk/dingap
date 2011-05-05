/*
  +----------------------------------------------------------------------+
  | PHP Version 4                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2003 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.02 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available at through the world-wide-web at                           |
  | http://www.php.net/license/2_02.txt.                                 |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Darryl Sokoloski, Point Clark Networks                       |
  +----------------------------------------------------------------------+

  $Id: php_ifconfig.h,v 1.1 2004/02/18 22:39:39 devel Exp $ 
*/

#ifndef PHP_IFCONFIG_H
#define PHP_IFCONFIG_H

extern zend_module_entry ifconfig_module_entry;
#define phpext_ifconfig_ptr &ifconfig_module_entry

#ifdef PHP_WIN32
#define PHP_IFCONFIG_API __declspec(dllexport)
#else
#define PHP_IFCONFIG_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_MINIT_FUNCTION(ifconfig);
PHP_MSHUTDOWN_FUNCTION(ifconfig);
PHP_RINIT_FUNCTION(ifconfig);
PHP_RSHUTDOWN_FUNCTION(ifconfig);
PHP_MINFO_FUNCTION(ifconfig);

PHP_FUNCTION(ifconfig_init);
PHP_FUNCTION(ifconfig_list);
PHP_FUNCTION(ifconfig_address);
PHP_FUNCTION(ifconfig_netmask);
PHP_FUNCTION(ifconfig_broadcast);
PHP_FUNCTION(ifconfig_hwaddress);
PHP_FUNCTION(ifconfig_flags);
PHP_FUNCTION(ifconfig_mtu);
PHP_FUNCTION(ifconfig_metric);
PHP_FUNCTION(ifconfig_link);
PHP_FUNCTION(ifconfig_speed);
PHP_FUNCTION(ifconfig_debug);

/* 
  	Declare any global variables you may need between the BEGIN
	and END macros here:     

ZEND_BEGIN_MODULE_GLOBALS(ifconfig)
	long global_context;
ZEND_END_MODULE_GLOBALS(ifconfig)
*/

/* In every utility function you add that needs to use variables 
   in php_ifconfig_globals, call TSRM_FETCH(); after declaring other 
   variables used by that function, or better yet, pass in TSRMLS_CC
   after the last function argument and declare your utility function
   with TSRMLS_DC after the last declared argument.  Always refer to
   the globals in your function as IFCONFIG_G(variable).  You are 
   encouraged to rename these macros something shorter, see
   examples in any other php module directory.
*/

#ifdef ZTS
#define IFCONFIG_G(v) TSRMG(ifconfig_globals_id, zend_ifconfig_globals *, v)
#else
#define IFCONFIG_G(v) (ifconfig_globals.v)
#endif

#endif	/* PHP_IFCONFIG_H */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
