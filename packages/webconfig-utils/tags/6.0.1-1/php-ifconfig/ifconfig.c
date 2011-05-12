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

  $Id: ifconfig.c,v 1.7 2004/09/17 03:24:17 devel Exp $ 
*/

#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <sys/ioctl.h>
#include <sys/param.h>
#include <sys/types.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <linux/if_arp.h>
#include <linux/sockios.h>
#include <errno.h>

#define IF_DEBUG	0x0001

#ifndef IFCONFIG_TEST
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_ifconfig.h"

/* If you declare any globals in php_ifconfig.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(ifconfig)
 */

char *if_version(char *buffer, size_t size);

/* True global resources - no need for thread safety here */
static int le_ifconfig;
#define le_ifconfig_name	"ifconfig handle"

static void _php_if_free(zend_rsrc_list_entry *rsrc TSRMLS_DC);

/* {{{ ifconfig_functions[]
 *
 * Every user visible function must have an entry in ifconfig_functions[].
 */
function_entry ifconfig_functions[] = {
	PHP_FE(ifconfig_init, NULL)
	PHP_FE(ifconfig_list, NULL)
	PHP_FE(ifconfig_address, NULL)
	PHP_FE(ifconfig_netmask, NULL)
	PHP_FE(ifconfig_broadcast, NULL)
	PHP_FE(ifconfig_hwaddress, NULL)
	PHP_FE(ifconfig_flags, NULL)
	PHP_FE(ifconfig_mtu, NULL)
	PHP_FE(ifconfig_metric, NULL)
	PHP_FE(ifconfig_link, NULL)
	PHP_FE(ifconfig_speed, NULL)
	PHP_FE(ifconfig_debug, NULL)
	{NULL, NULL, NULL}	/* Must be the last line in ifconfig_functions[] */
};
/* }}} */

/* {{{ ifconfig_module_entry
 */
zend_module_entry ifconfig_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"ifconfig",
	ifconfig_functions,
	PHP_MINIT(ifconfig),
	PHP_MSHUTDOWN(ifconfig),
	NULL,
	NULL,
	PHP_MINFO(ifconfig),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_IFCONFIG
ZEND_GET_MODULE(ifconfig)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("ifconfig.global_value",      "42", PHP_INI_ALL, OnUpdateInt, global_value, zend_ifconfig_globals, ifconfig_globals)
    STD_PHP_INI_ENTRY("ifconfig.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_ifconfig_globals, ifconfig_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_ifconfig_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_ifconfig_init_globals(zend_ifconfig_globals *ifconfig_globals)
{
	ifconfig_globals->global_value = 0;
	ifconfig_globals->global_string = NULL;
}
*/
/* }}} */

#define REGISTER_IFCONFIG_CONSTANT(__c) REGISTER_LONG_CONSTANT(#__c, __c, CONST_CS | CONST_PERSISTENT)

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(ifconfig)
{
	/* If you have INI entries, uncomment these lines 
	ZEND_INIT_MODULE_GLOBALS(ifconfig, php_ifconfig_init_globals, NULL);
	REGISTER_INI_ENTRIES();
	*/

	REGISTER_IFCONFIG_CONSTANT(IFF_UP);
	REGISTER_IFCONFIG_CONSTANT(IFF_BROADCAST);
	REGISTER_IFCONFIG_CONSTANT(IFF_DEBUG);
	REGISTER_IFCONFIG_CONSTANT(IFF_LOOPBACK);
	REGISTER_IFCONFIG_CONSTANT(IFF_POINTOPOINT);
	REGISTER_IFCONFIG_CONSTANT(IFF_RUNNING);
	REGISTER_IFCONFIG_CONSTANT(IFF_NOARP);
	REGISTER_IFCONFIG_CONSTANT(IFF_PROMISC);
	REGISTER_IFCONFIG_CONSTANT(IFF_NOTRAILERS);
	REGISTER_IFCONFIG_CONSTANT(IFF_ALLMULTI);
	REGISTER_IFCONFIG_CONSTANT(IFF_MASTER);
	REGISTER_IFCONFIG_CONSTANT(IFF_SLAVE);
	REGISTER_IFCONFIG_CONSTANT(IFF_MULTICAST);
	REGISTER_IFCONFIG_CONSTANT(IFF_PORTSEL);
	REGISTER_IFCONFIG_CONSTANT(IFF_AUTOMEDIA);
	REGISTER_IFCONFIG_CONSTANT(IFF_DYNAMIC);

	le_ifconfig = zend_register_list_destructors_ex(_php_if_free, NULL, le_ifconfig_name, module_number);
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(ifconfig)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(ifconfig)
{
	char v[256];

	php_info_print_table_start();
	php_info_print_table_row(2, "IFconfig Support", "enabled");
	php_info_print_table_row(2, "IFconfig Version", if_version(v, sizeof(v)));
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */

#endif // ndef IFCONFIG_TEST

#ifdef IFCONFIG_TEST
#define IF_FREE(p)			free(p)
#define IF_MALLOC(n)		malloc(n)
#define IF_STRDUP(p)		strdup(p)
#else
#define IF_FREE(p)			efree(p)
#define IF_MALLOC(n)		emalloc(n)
#define IF_STRDUP(p)		estrdup(p)
#endif

#ifndef IFCONFIG_TEST
#define IF_ERROR(ctx) \
{ \
	if(ctx->last_error) \
		IF_FREE(ctx->last_error); \
	ctx->last_error = IF_STRDUP(strerror(errno)); \
	if((ctx->flags & IF_DEBUG)) \
		php_error(E_WARNING, "%s: %s", \
			get_active_function_name(TSRMLS_C), ctx->last_error); \
}
#define IF_ERROR_STR(ctx, str) \
{ \
	if(ctx->last_error) \
		IF_FREE(ctx->last_error); \
	ctx->last_error = IF_STRDUP(str); \
	if((ctx->flags & IF_DEBUG)) \
		php_error(E_WARNING, "%s: %s", \
			get_active_function_name(TSRMLS_C), ctx->last_error); \
}
#else
#define IF_ERROR(ctx) \
{ \
	if(ctx->last_error) \
		IF_FREE(ctx->last_error); \
	ctx->last_error = IF_STRDUP(strerror(errno)); \
	if((ctx->flags & IF_DEBUG)) \
		fprintf(stderr, "%s: %s.\n", __func__, ctx->last_error); \
}
#define IF_ERROR_STR(ctx, str) \
{ \
	if(ctx->last_error) \
		IF_FREE(ctx->last_error); \
	ctx->last_error = IF_STRDUP(str); \
	if((ctx->flags & IF_DEBUG)) \
		fprintf(stderr, "%s: %s.\n", __func__, ctx->last_error); \
}
#endif

#define MAX_INTERFACES	1024
#ifdef IFCONFIG_TEST
#define PROC_NET_DEV	"net.dev"
#else
#define PROC_NET_DEV	"/proc/net/dev"
#endif
#define inaddrr(x) (*(struct in_addr *) &ifr.x[sizeof(sa.sin_port)])

// Context handle
typedef struct IF_CTX_t
{
	int sd;
	char *last_error;
	unsigned short flags;
	char *interfaces[MAX_INTERFACES];
} if_ctx;

// From ethtool...
struct ethtool_value
{
	__uint32_t cmd;
	__uint32_t data;
};

// From ethtool...
struct ethtool_cmd
{
	__uint32_t	cmd;
	__uint32_t	supported;
	__uint32_t	advertising;
	__uint16_t	speed;
	__uint8_t	duplex;
	__uint8_t	port;
	__uint8_t	phy_address;
	__uint8_t	transceiver;
	__uint8_t	autoneg;
	__uint32_t	maxtxpkt;
	__uint32_t	maxrxpkt;
	__uint32_t	reserved[4];
};

// Create a context and socket.
// Return a context handle or -1 on error.
if_ctx *if_init(void)
{
	int sd;
	if_ctx *p_ctx;

	if((sd = socket(AF_INET, SOCK_DGRAM, IPPROTO_IP)) == -1)
		return (if_ctx *)NULL;

	p_ctx = (if_ctx *)IF_MALLOC(sizeof(struct IF_CTX_t));
	if(!p_ctx) return (if_ctx *)NULL;

	memset(p_ctx, 0, sizeof(struct IF_CTX_t));

	p_ctx->sd = sd;
	p_ctx->flags = IF_DEBUG;
	memset(p_ctx->interfaces, 0, sizeof(char *) * MAX_INTERFACES);

	return p_ctx;
}

// Free context, close socket.
void if_free(if_ctx *p_ctx)
{
	int i;

	if(!p_ctx) return;

	if(p_ctx->sd != -1) close(p_ctx->sd);
	if(p_ctx->last_error) IF_FREE(p_ctx->last_error);

	for(i = 0; i < MAX_INTERFACES; i++)
	{
		if(p_ctx->interfaces[i]) IF_FREE(p_ctx->interfaces[i]);
	}

	IF_FREE(p_ctx);
}

// Return version string
char *if_version(char *buffer, size_t size)
{
	memset(buffer, 0, size);
	snprintf(buffer, size, "$Id: ifconfig.c,v 1.7 2004/09/17 03:24:17 devel Exp $");
	return buffer;
}

// Retrieve an array of all interfaces.
// Return the number of interfaces found or -1 on error.
int if_list(if_ctx *p_ctx)
{
	FILE *h_file;
	char buffer[256];
	int i, if_count = 0;
	char if_name[IFNAMSIZ];
	struct ifconf ifc;
	struct ifreq *p_ifr;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	memset(&ifc, 0, sizeof(struct ifconf));

	// Find out how many interfaces there are.
	if(ioctl(p_ctx->sd, SIOCGIFCONF, &ifc) == -1)
	{
		fprintf(stderr, "%s: %s.\n",
			__func__, strerror(errno));
		return -1;
	}

	ifc.ifc_req = (struct ifreq *)IF_MALLOC(ifc.ifc_len);

	if(!ifc.ifc_req)
	{
		IF_ERROR(p_ctx)
		return -1;
	}

	// Get the array of ifreq structures.
	if(ioctl(p_ctx->sd, SIOCGIFCONF, &ifc) == -1)
	{
		IF_ERROR(p_ctx)
		IF_FREE(ifc.ifc_req);
		return -1;
	}

	if(ifc.ifc_len)
	{
		if_count = ifc.ifc_len / sizeof(struct ifreq);

		if(if_count > MAX_INTERFACES)
		{
			IF_ERROR_STR(p_ctx, "Too many interfaces")
			IF_FREE(ifc.ifc_req);
			return -1;
		}

		p_ifr = ifc.ifc_req;

		for(i = 0; (char *)p_ifr < (char *)ifc.ifc_req + ifc.ifc_len; ++p_ifr, i++)
		{
			if(p_ctx->interfaces[i])
				IF_FREE(p_ctx->interfaces[i]);

			p_ctx->interfaces[i] = IF_STRDUP(p_ifr->ifr_name);

#ifdef IFCONFIG_TEST
			fprintf(stderr, "%s: adding interface #%d: %s\n", __func__, i, p_ifr->ifr_name);
#endif
		}
	}

	h_file = fopen(PROC_NET_DEV, "r");

	if(!h_file)
	{
		IF_ERROR(p_ctx)
		IF_FREE(ifc.ifc_req);
		return -1;
	}

	fgets(buffer, sizeof(buffer), h_file);
	fgets(buffer, sizeof(buffer), h_file);

	while(!feof(h_file))
	{
		char *offset;
		short exists; exists = 0;

		memset(buffer, 0, sizeof(buffer));
		fgets(buffer, sizeof(buffer), h_file);

		for(offset = buffer; offset[0] == 0x20; offset++);

		memset(if_name, 0, sizeof(if_name));
		if(sscanf(offset, "%[A-z0-9]", if_name) != 1) break;

		for(i = 0; i < if_count; i++)
		{
			if(!strncmp(p_ctx->interfaces[i], if_name, IFNAMSIZ))
			{
				exists = 1; break;
			}
		}

		if(!exists)
		{
			if(++if_count > MAX_INTERFACES)
			{
				fclose(h_file);
				IF_ERROR_STR(p_ctx, "Too many interfaces")
				IF_FREE(ifc.ifc_req);
				return -1;
			}

			if(p_ctx->interfaces[if_count - 1])
				IF_FREE(p_ctx->interfaces[if_count - 1]);

			p_ctx->interfaces[if_count - 1] = IF_STRDUP(if_name);
#ifdef IFCONFIG_TEST
			fprintf(stderr, "%s: adding interface #%d: %s\n", __func__, if_count - 1, if_name);
#endif
		}
	}

	fclose(h_file);
	return if_count;
}

// Store the string address of the given interface (device) in buffer.
// Return the length of the address string or -1 on error.
int if_get_address(if_ctx *p_ctx, const char *device, char *buffer, size_t size)
{
	struct ifreq ifr;
	struct sockaddr_in sa;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device || !buffer || !size) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFADDR, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
	}

	memset(buffer, 0, size);
	snprintf(buffer, size, inet_ntoa(inaddrr(ifr_addr.sa_data)));

	return strlen(buffer);
}

// Store the string address of the given interface netmask in buffer.
// Return the length of the address string or -1 on error.
int if_get_netmask(if_ctx *p_ctx, const char *device, char *buffer, size_t size)
{
	struct ifreq ifr;
	struct sockaddr_in sa;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device || !buffer || !size) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFNETMASK, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
	}

	memset(buffer, 0, size);

	if(strcmp("255.255.255.255", inet_ntoa(inaddrr(ifr_addr.sa_data))))
		snprintf(buffer, size, inet_ntoa(inaddrr(ifr_addr.sa_data)));

	return strlen(buffer);
}

// Store the string address of the given interface broadcast in buffer.
// Return the length of the address string or -1 on error.
int if_get_broadcast(if_ctx *p_ctx, const char *device, char *buffer, size_t size)
{
	short flags;
	struct ifreq ifr;
	struct sockaddr_in sa;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device || !buffer || !size) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if((if_get_flags(p_ctx, device, &flags)) == -1)
		return -1;

    if(!(flags & IFF_BROADCAST)) return 0;

	if(ioctl(p_ctx->sd, SIOCGIFBRDADDR, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
	}

	memset(buffer, 0, size);

	if(strcmp("0.0.0.0", inet_ntoa(inaddrr(ifr_addr.sa_data))))
		snprintf(buffer, size, inet_ntoa(inaddrr(ifr_addr.sa_data)));

	return strlen(buffer);
}

// Store the string address of the given interface's hardwar address,
// (if there is one) in buffer.
// Return the length of the address string or -1 on error.
int if_get_hwaddress(if_ctx *p_ctx, const char *device, char *buffer, size_t size)
{
	struct ifreq ifr;
	unsigned char *byte;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device || !buffer || !size) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFHWADDR, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
	}

	switch(ifr.ifr_hwaddr.sa_family)
	{
	case ARPHRD_NETROM:
	case ARPHRD_ETHER:
	case ARPHRD_PPP:
	case ARPHRD_EETHER:
	case ARPHRD_IEEE802:
		// These have hardware addresses...
		break;

	default:
		return 0;
	}

	byte = (unsigned char *)&ifr.ifr_addr.sa_data;

	memset(buffer, 0, size);
	snprintf(buffer, size,
		"%2.2x:%2.2x:%2.2x:%2.2x:%2.2x:%2.2x",
		byte[0], byte[1], byte[2], byte[3], byte[4], byte[5]);

	return strlen(buffer);
}

// Retrieve flags for device.  Return -1 on error.
int if_get_flags(if_ctx *p_ctx, const char *device, short *flags)
{
	struct ifreq ifr;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFFLAGS, (char *)&ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
	}

	memcpy(flags, &ifr.ifr_flags, sizeof(short));

	return 0;
}

// Retrieve MTU for device.  Return -1 on error.
int if_get_mtu(if_ctx *p_ctx, const char *device, int *mtu)
{
	struct ifreq ifr;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device || !mtu) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFMTU, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
    }

	memcpy(mtu, &ifr.ifr_mtu, sizeof(int));

	return 0;
}

// Retrieve metric for device.  Return -1 on error.
int if_get_metric(if_ctx *p_ctx, const char *device, int *metric)
{
	struct ifreq ifr;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device || !metric) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFMETRIC, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
    }

	memcpy(metric, &ifr.ifr_metric, sizeof(int));

	return 0;
}

// Determine if the device has link (if supported).
// Return 0 for no link, 1 for link detect, and -1 on error.
int if_link_detect(if_ctx *p_ctx, const char *device)
{
	struct ifreq ifr;
	struct ethtool_value ev;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	ev.data = 0;
	ev.cmd = 0x0000000a;

	ifr.ifr_data = (caddr_t)&ev;

	if(ioctl(p_ctx->sd, SIOCETHTOOL, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
    }

	return (int)ev.data;
}

// Store the device's negotiated link speed (if supported).
// Return 0 for no link, 1 for link detect, and -1 on error.
int if_get_speed(if_ctx *p_ctx, const char *device, unsigned short *speed)
{
	struct ifreq ifr;
	struct ethtool_cmd ec;

	if(!p_ctx)
	{
		IF_ERROR_STR(p_ctx, "Invalid context handle")
		return -1;
	}

	if(!device || !speed) return -1;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	ec.cmd = 0x00000001;
	ifr.ifr_data = (caddr_t)&ec;

	if(ioctl(p_ctx->sd, SIOCETHTOOL, &ifr) == -1)
	{
		IF_ERROR(p_ctx)
		return -1;
    }

	memcpy(speed, &ec.speed, sizeof(unsigned short));

	return 0;
}

#ifdef IFCONFIG_TEST

/* {{{ proto int main(int argc, char *argv[])
   Used for testing... */
int main(int argc, char *argv[])
{
	short flags;
	char buffer[1024];
	struct ifreq *p_ifr;
	if_ctx *p_ctx = if_init();
	int i, mtu, metric, link, count;

	if(!p_ctx) return 1;

	if((count = if_list(p_ctx)) != -1)
	{
		for(i = 0; i < count; i++)
		{
			if(if_get_address(p_ctx, p_ctx->interfaces[i], buffer, 1024) > 0)
				fprintf(stderr, "%s: address: %s\n", p_ctx->interfaces[i], buffer);

			if(if_get_netmask(p_ctx, p_ctx->interfaces[i], buffer, 1024) > 0)
				fprintf(stderr, "%s: netmask: %s\n", p_ctx->interfaces[i], buffer);

			if(if_get_broadcast(p_ctx, p_ctx->interfaces[i], buffer, 1024) > 0)
				fprintf(stderr, "%s: broadcast: %s\n", p_ctx->interfaces[i], buffer);

			if(if_get_hwaddress(p_ctx, p_ctx->interfaces[i], buffer, 1024) > 0)
				fprintf(stderr, "%s: hwaddress: %s\n", p_ctx->interfaces[i], buffer);

			if(!if_get_mtu(p_ctx, p_ctx->interfaces[i], &mtu))
				fprintf(stderr, "%s: mtu: %u\n", p_ctx->interfaces[i], mtu);

			if(!if_get_metric(p_ctx, p_ctx->interfaces[i], &metric))
				fprintf(stderr, "%s: metric: %u\n", p_ctx->interfaces[i], metric + 1);

			if(!if_get_flags(p_ctx, p_ctx->interfaces[i], &flags))
			{
				if((flags & IFF_UP))
					fprintf(stderr, "%s: flags: Interface is (up) running.\n", p_ctx->interfaces[i]);
				if((flags & IFF_BROADCAST))
					fprintf(stderr, "%s: flags: Valid broadcast address set.\n", p_ctx->interfaces[i]);
				if((flags & IFF_DEBUG))
					fprintf(stderr, "%s: flags: Internal debugging flag.\n", p_ctx->interfaces[i]);
				if((flags & IFF_LOOPBACK))
					fprintf(stderr, "%s: flags: Interface is a loopback interface.\n", p_ctx->interfaces[i]);
				if((flags & IFF_POINTOPOINT))
					fprintf(stderr, "%s: flags: Interface is a point-to-point link.\n", p_ctx->interfaces[i]);
				if((flags & IFF_RUNNING))
					fprintf(stderr, "%s: flags: Resources allocated.\n", p_ctx->interfaces[i]);
				if((flags & IFF_NOARP))
					fprintf(stderr, "%s: flags: No ARP protocol, level 2 destination address not set.\n", p_ctx->interfaces[i]);
				if((flags & IFF_PROMISC))
					fprintf(stderr, "%s: flags: Interface is in promiscuous mode.\n", p_ctx->interfaces[i]);
				if((flags & IFF_NOTRAILERS))
					fprintf(stderr, "%s: flags: Avoid use of trailers.\n", p_ctx->interfaces[i]);
				if((flags & IFF_ALLMULTI))
					fprintf(stderr, "%s: flags: Receive all multicast packets.\n", p_ctx->interfaces[i]);
				if((flags & IFF_MASTER))
					fprintf(stderr, "%s: flags: Master of a load balancing bundle.\n", p_ctx->interfaces[i]);
				if((flags & IFF_SLAVE))
					fprintf(stderr, "%s: flags: Slave of a load balancing bundle.\n", p_ctx->interfaces[i]);
				if((flags & IFF_MULTICAST))
					fprintf(stderr, "%s: flags: Supports multicast.\n", p_ctx->interfaces[i]);
				if((flags & IFF_PORTSEL))
					fprintf(stderr, "%s: flags: Is able to select media type via ifmap.\n", p_ctx->interfaces[i]);
				if((flags & IFF_AUTOMEDIA))
					fprintf(stderr, "%s: flags: Auto media selection active.\n", p_ctx->interfaces[i]);
				if((flags & IFF_DYNAMIC))
					fprintf(stderr, "%s: flags: The addresses are lost when the interface goes down.\n", p_ctx->interfaces[i]);
			}

			if((link = if_link_detect(p_ctx, p_ctx->interfaces[i])) != -1)
			{
				unsigned short speed;

				fprintf(stderr, "%s: link detected: %s\n", p_ctx->interfaces[i], (link) ? "yes" : "no");

				if(if_get_speed(p_ctx, p_ctx->interfaces[i], &speed) != -1)
					fprintf(stderr, "%s: interface speed: %u\n", p_ctx->interfaces[i], speed);
			}
		}
	}

	if_free(p_ctx);

	return 0;
}
/* }}} */

#else

/* {{{ proto handleifconfig_()
   */
//PHP_FUNCTION(ifconfig_)
//{
//}
/* }}} */

/* {{{ proto resource ifconfig_init(void)
   Create and return a module context handle. */
PHP_FUNCTION(ifconfig_init)
{
	if_ctx *p_ctx = if_init();

	if(!p_ctx) RETURN_FALSE;

	ZEND_REGISTER_RESOURCE(return_value, p_ctx, le_ifconfig);
}
/* }}} */

/* {{{ proto array ifconfig_list(handle context)
   */
PHP_FUNCTION(ifconfig_list)
{
	int i, count;
	zval **context;
	struct ifreq *p_ifr;
	if_ctx *p_ctx = NULL;

	if(ZEND_NUM_ARGS() != 1 ||
		zend_get_parameters_ex(1, &context) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	if((count = if_list(p_ctx)) == -1) RETURN_FALSE;

	array_init(return_value);

	for(i = 0; i < count; i++)
		add_index_string(return_value, i, p_ctx->interfaces[i], 1);
}
/* }}} */

/* {{{ proto string ifconfig_address(handle context, string device)
   */
PHP_FUNCTION(ifconfig_address)
{
	char string[256];
	if_ctx *p_ctx = NULL;
	zval **context, **device;
	int str_len = sizeof(string);

	if(ZEND_NUM_ARGS() != 2)
	{
		WRONG_PARAM_COUNT;
	}

	if(zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);
	str_len = if_get_address(p_ctx, Z_STRVAL_PP(device), string, str_len);

	if(str_len == -1) RETURN_FALSE;

	RETURN_STRINGL(string, str_len, 1);
}
/* }}} */

/* {{{ proto string ifconfig_netmask(handle context, string device)
   */
PHP_FUNCTION(ifconfig_netmask)
{
	char string[256];
	if_ctx *p_ctx = NULL;
	zval **context, **device;
	int str_len = sizeof(string);

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);
	str_len = if_get_netmask(p_ctx, Z_STRVAL_PP(device), string, str_len);

	if(str_len == -1) RETURN_FALSE;

	//RETURN_STRINGL(string, str_len, 1);
	RETURN_STRING(string, 1);
}
/* }}} */

/* {{{ proto string ifconfig_broadcast(handle context, string device)
   */
PHP_FUNCTION(ifconfig_broadcast)
{
	char string[256];
	if_ctx *p_ctx = NULL;
	zval **context, **device;
	int str_len = sizeof(string);

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);
	str_len = if_get_broadcast(p_ctx, Z_STRVAL_PP(device), string, str_len);

	if(str_len == -1) RETURN_FALSE;

	RETURN_STRINGL(string, str_len, 1);
}
/* }}} */

/* {{{ proto string ifconfig_hwaddress(handle context, string device)
   */
PHP_FUNCTION(ifconfig_hwaddress)
{
	char string[256];
	if_ctx *p_ctx = NULL;
	zval **context, **device;
	int str_len = sizeof(string);

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);
	str_len = if_get_hwaddress(p_ctx, Z_STRVAL_PP(device), string, str_len);

	if(str_len == -1) RETURN_FALSE;

	RETURN_STRINGL(string, str_len, 1);
}
/* }}} */

/* {{{ proto long ifconfig_hwaddress(handle context, string device)
   */
PHP_FUNCTION(ifconfig_flags)
{
	short flags;
	if_ctx *p_ctx = NULL;
	zval **context, **device;

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);

	if(if_get_flags(p_ctx, Z_STRVAL_PP(device), &flags) == -1)
		RETURN_FALSE;

	RETURN_LONG((long)flags);
}
/* }}} */

/* {{{ proto long ifconfig_mtu(handle context, string device)
   */
PHP_FUNCTION(ifconfig_mtu)
{
	int mtu;
	if_ctx *p_ctx = NULL;
	zval **context, **device;

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);

	if(if_get_mtu(p_ctx, Z_STRVAL_PP(device), &mtu) == -1)
		RETURN_FALSE;

	RETURN_LONG(mtu);
}
/* }}} */

/* {{{ proto long ifconfig_metric(handle context, string device)
   */
PHP_FUNCTION(ifconfig_metric)
{
	int metric;
	if_ctx *p_ctx = NULL;
	zval **context, **device;

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);

	if(if_get_metric(p_ctx, Z_STRVAL_PP(device), &metric) == -1)
		RETURN_FALSE;

	RETURN_LONG(metric);
}
/* }}} */

/* {{{ proto int ifconfig_link(handle context, string device)
   */
PHP_FUNCTION(ifconfig_link)
{
	if_ctx *p_ctx = NULL;
	zval **context, **device;

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);

	RETURN_LONG((long)if_link_detect(p_ctx, Z_STRVAL_PP(device)));
}
/* }}} */

/* {{{ proto long ifconfig_speed(handle context, string device)
   */
PHP_FUNCTION(ifconfig_speed)
{
	unsigned short speed;
	if_ctx *p_ctx = NULL;
	zval **context, **device;

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &device) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)

	convert_to_string_ex(device);

	if(if_get_speed(p_ctx, Z_STRVAL_PP(device), &speed) == -1)
		RETURN_FALSE;

	RETURN_LONG((long)speed);
}
/* }}} */

/* {{{ proto void ifconfig_debug(handle context, bool enable)
   */
PHP_FUNCTION(ifconfig_debug)
{
	if_ctx *p_ctx = NULL;
	zval **context, **enable;

	if(ZEND_NUM_ARGS() != 2 ||
		zend_get_parameters_ex(2, &context, &enable) == FAILURE)
	{
		WRONG_PARAM_COUNT;
	}

	ZEND_FETCH_RESOURCE(p_ctx, if_ctx *, context, -1, le_ifconfig_name, le_ifconfig)
	convert_to_boolean_ex(enable);

	if(Z_LVAL_PP(enable)) p_ctx->flags |= IF_DEBUG;
	else p_ctx->flags &= ~IF_DEBUG;
}
/* }}} */

static void _php_if_free(zend_rsrc_list_entry *rsrc TSRMLS_DC)
{
	if_ctx *p_ctx = (if_ctx *)rsrc->ptr; if_free(p_ctx);
}

#endif // IFCONFIG_TEST


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
