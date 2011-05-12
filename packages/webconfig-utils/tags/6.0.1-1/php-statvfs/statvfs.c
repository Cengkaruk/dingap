/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2007 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id: header,v 1.16.2.1.2.1 2007/01/01 19:32:09 iliaa Exp $ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_statvfs.h"

#include <sys/statvfs.h>

/* If you declare any globals in php_statvfs.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(statvfs)
*/

/* True global resources - no need for thread safety here */
static int le_statvfs;

/* {{{ statvfs_functions[]
 *
 * Every user visible function must have an entry in statvfs_functions[].
 */
zend_function_entry statvfs_functions[] = {
	PHP_FE(statvfs,	NULL)
	{NULL, NULL, NULL}	/* Must be the last line in statvfs_functions[] */
};
/* }}} */

/* {{{ statvfs_module_entry
 */
zend_module_entry statvfs_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"statvfs",
	statvfs_functions,
	PHP_MINIT(statvfs),
	PHP_MSHUTDOWN(statvfs),
	PHP_RINIT(statvfs),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(statvfs),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(statvfs),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_STATVFS
ZEND_GET_MODULE(statvfs)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("statvfs.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_statvfs_globals, statvfs_globals)
    STD_PHP_INI_ENTRY("statvfs.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_statvfs_globals, statvfs_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_statvfs_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_statvfs_init_globals(zend_statvfs_globals *statvfs_globals)
{
	statvfs_globals->global_value = 0;
	statvfs_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(statvfs)
{
	/* If you have INI entries, uncomment these lines 
	REGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(statvfs)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(statvfs)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(statvfs)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(statvfs)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "statvfs support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */


/* Remove the following function when you have succesfully modified config.m4
   so that your module can be compiled into PHP, it exists only for testing
   purposes. */

/* Every user-visible function in PHP should document itself in the source */
/* {{{ proto array statvfs(string path)
   Return an associative array of mounted device stats */
PHP_FUNCTION(statvfs)
{
	char *path = NULL;
	int path_len;
	struct statvfs stats;

	if (ZEND_NUM_ARGS() != 1) WRONG_PARAM_COUNT;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &path, &path_len) == FAILURE) {
		RETURN_FALSE;
	}

	if (statvfs(path, &stats) != 0) {
		RETURN_FALSE;
	}

	fsblkcnt_t kblocks = ((unsigned long long)stats.f_frsize * (unsigned long long)stats.f_blocks) / 1024ul;
	fsblkcnt_t kfree = ((unsigned long long)stats.f_frsize * (unsigned long long)stats.f_bfree) / 1024ul;
	fsblkcnt_t kavail = ((unsigned long long)stats.f_frsize * (unsigned long long)stats.f_bavail) / 1024ul;
	fsblkcnt_t kused = kblocks - kfree;

	array_init(return_value);

	add_assoc_long(return_value, "size", kblocks);
	add_assoc_long(return_value, "free", kavail);
	add_assoc_long(return_value, "used", kused);
}
/* }}} */
/* The previous line is meant for vim and emacs, so it can correctly fold and 
   unfold functions in source code. See the corresponding marks just before 
   function definition, where the functions purpose is also documented. Please 
   follow this convention for the convenience of others editing your code.
*/


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
