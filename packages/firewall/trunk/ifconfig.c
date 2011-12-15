///////////////////////////////////////////////////////////////////////////////
//
// ifconfig.c
// Network interface utility functions.
// Copyright (C) 2005 Point Clark Networks
// $Id: $
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the
// Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful, but 
// WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
// or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program; if not, write to the Free Software Foundation, Inc.,
// 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <sys/ioctl.h>
#include <sys/param.h>
#include <sys/types.h>
#include <dirent.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <linux/if_arp.h>
#include <linux/sockios.h>
#include <errno.h>

#include "ifconfig.h"

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
	memset(p_ctx->interfaces, 0, sizeof(char *) * MAX_INTERFACES);

	p_ctx->last_error = IF_MALLOC(MAX_ERROR_STR);

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
	snprintf(buffer, size, "$Id: $");
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
    char *result = NULL;

	if(!p_ctx)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	memset(&ifc, 0, sizeof(struct ifconf));

	// Find out how many interfaces there are.
	if(ioctl(p_ctx->sd, SIOCGIFCONF, &ifc) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFCONF: %s", __func__, strerror(errno));
		return -1;
	}

	ifc.ifc_req = (struct ifreq *)IF_MALLOC(ifc.ifc_len);

	if(!ifc.ifc_req)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: malloc: %s", __func__, strerror(errno));
		return -1;
	}

	// Get the array of ifreq structures.
	if(ioctl(p_ctx->sd, SIOCGIFCONF, &ifc) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFCONF: %s", __func__, strerror(errno));
		IF_FREE(ifc.ifc_req);
		return -1;
	}

	if(ifc.ifc_len)
	{
		if_count = ifc.ifc_len / sizeof(struct ifreq);

		if(if_count > MAX_INTERFACES)
		{
			snprintf(p_ctx->last_error, MAX_ERROR_STR,
				"%s: Too many interfaces", __func__);
			IF_FREE(ifc.ifc_req);
			return -1;
		}

		p_ifr = ifc.ifc_req;

		for(i = 0; (char *)p_ifr < (char *)ifc.ifc_req + ifc.ifc_len; ++p_ifr, i++)
		{
			if(p_ctx->interfaces[i]) IF_FREE(p_ctx->interfaces[i]);

			p_ctx->interfaces[i] = IF_STRDUP(p_ifr->ifr_name);

#ifdef IFCONFIG_TEST
			fprintf(stderr, "%s: adding interface #%d: %s\n", __func__, i, p_ifr->ifr_name);
#endif
		}
	}

	h_file = fopen(PROC_NET_DEV, "r");

	if(!h_file)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: fopen(%s): %s", __func__, PROC_NET_DEV, strerror(errno));
		IF_FREE(ifc.ifc_req);
		return -1;
	}

	result = fgets(buffer, sizeof(buffer), h_file);
    if (result == NULL) {
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: fgets(%s): Unexpected error while reading: %s",
            __func__, PROC_NET_DEV,
            (feof(h_file)) ? "EOF" :
                (ferror(h_file) ? strerror(errno) : "Unknown"));
		IF_FREE(ifc.ifc_req);
		return -1;
	}
	result = fgets(buffer, sizeof(buffer), h_file);
    if (result == NULL) {
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: fgets(%s): Unexpected error while reading: %s",
            __func__, PROC_NET_DEV,
            (feof(h_file)) ? "EOF" :
                (ferror(h_file) ? strerror(errno) : "Unknown"));
		IF_FREE(ifc.ifc_req);
		return -1;
	}

	while(!feof(h_file))
	{
		char *offset;
		short exists; exists = 0;

		memset(buffer, 0, sizeof(buffer));
		result = fgets(buffer, sizeof(buffer), h_file);
        if (result == NULL && feof(h_file)) break;
        else if (result == NULL) {
            snprintf(p_ctx->last_error, MAX_ERROR_STR,
                "%s: fgets(%s): Unexpected error while reading: %s",
                __func__, PROC_NET_DEV, strerror(errno));
            IF_FREE(ifc.ifc_req);
            return -1;
        }

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
				snprintf(p_ctx->last_error, MAX_ERROR_STR,
					"%s: Too many interfaces", __func__);
				IF_FREE(ifc.ifc_req);
				return -1;
			}

			if(p_ctx->interfaces[if_count - 1])
				IF_FREE(p_ctx->interfaces[if_count - 1]);

			p_ctx->interfaces[if_count - 1] = IF_STRDUP(if_name);
#ifdef IFCONFIG_TEST
			fprintf(stderr, "%s: adding interface #%d: %s\n",
                __func__, if_count - 1, if_name);
#endif
		}
	}

	fclose(h_file);
	return if_count;
}

// Retrieve an array of all PPPoE interfaces.
// Return the number of interfaces found or -1 on error.
int if_list_pppoe(if_ctx *p_ctx)
{
	DIR *dir;
	struct dirent *de;
	int if_count = 0;
	char *buffer = NULL;

	if(chdir(SYSCONF_NET_SCRIPTS) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: chdir: %s: %s.", __func__,
			SYSCONF_NET_SCRIPTS, strerror(errno));
		return -1;
	}

	if(!(dir = opendir(SYSCONF_NET_SCRIPTS)))
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: opendir: %s: %s.", __func__,
			SYSCONF_NET_SCRIPTS, strerror(errno));
		return -1;
	}

	buffer = calloc(getpagesize(), sizeof(char));

	while((de = readdir(dir)))
	{
		FILE *ifcfg;

		if(strlen(de->d_name) < 9) continue;
		if(strncmp(de->d_name, "ifcfg-ppp", 9)) continue;

		if(!(ifcfg = fopen(de->d_name, "r"))) continue;

		while(!feof(ifcfg))
		{
			if(!(fgets(buffer, getpagesize() - 1, ifcfg))) break;
			if(strncmp(buffer, "ETH", 3) != 0) continue;

			if(++if_count > MAX_INTERFACES)
			{
				snprintf(p_ctx->last_error, MAX_ERROR_STR,
					"%s: Too many interfaces", __func__);
				return -1;
			}

			if(p_ctx->pppoe[if_count - 1])
				IF_FREE(p_ctx->pppoe[if_count - 1]);

			p_ctx->pppoe[if_count - 1] = IF_STRDUP(de->d_name + 6);
		}

		fclose(ifcfg);
	}

	free(buffer);
    closedir(dir);

	return if_count;
}

// Is this a PPP device?
int if_isppp(if_ctx *p_ctx, const char *device)
{
	struct ifreq ifr;

	if(!p_ctx)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__, "device");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFHWADDR, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFHWADDR: %s", __func__, strerror(errno));
		return -1;
	}

    return ((ifr.ifr_hwaddr.sa_family == ARPHRD_PPP));
}

// Store the string address of the given interface (device) in buffer.
// Return the length of the address string or -1 on error.
int if_get_address(if_ctx *p_ctx, const char *device, char *buffer, size_t size)
{
	struct ifreq ifr;
	struct sockaddr_in sa;

	if(!p_ctx)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !buffer || !size)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!buffer) ? "buffer" : (!size) ? "size" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFADDR, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFADDR: %s", __func__, strerror(errno));
		return -1;
	}

	memset(buffer, 0, size);
	strncpy(buffer, inet_ntoa(inaddrr(ifr_addr.sa_data)), size - 1);

	return strlen(buffer);
}

// Store the string destination address of the given interface (device) in
// buffer.  Return the length of the address string or -1 on error.
int if_get_dst_address(if_ctx *p_ctx, const char *device, char *buffer, size_t size)
{
	struct ifreq ifr;
	struct sockaddr_in sa;

	if(!p_ctx)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !buffer || !size)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!buffer) ? "buffer" : (!size) ? "size" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFHWADDR, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFHWADDR: %s", __func__, strerror(errno));
		return -1;
	}

	if(ifr.ifr_hwaddr.sa_family != ARPHRD_PPP)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Not a PPP device: %s", __func__, device);
		return -1;
    }

	if(ioctl(p_ctx->sd, SIOCGIFDSTADDR, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFDSTADDR: %s", __func__, strerror(errno));
		return -1;
	}

	memset(buffer, 0, size);
	strncpy(buffer, inet_ntoa(inaddrr(ifr_addr.sa_data)), size - 1);

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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !buffer || !size)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!buffer) ? "buffer" : (!size) ? "size" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFNETMASK, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFNETMASK: %s", __func__, strerror(errno));
		return -1;
	}

	memset(buffer, 0, size);

//	if(strcmp("255.255.255.255", inet_ntoa(inaddrr(ifr_addr.sa_data))))
	strncpy(buffer, inet_ntoa(inaddrr(ifr_addr.sa_data)), size - 1);

	return strlen(buffer);
}

// Store the string network address of the given ip and netmask in buffer.
// Return the length of the address string or -1 on error.
int if_get_network(if_ctx *p_ctx, const char *ip, const char *netmask, char *buffer, size_t size)
{
	struct in_addr in;
	unsigned char ip_quad[4];

	if(!p_ctx)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!ip || !netmask || !buffer || !size)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!ip) ? "ip" : (!netmask) ? "netmask" : (!buffer) ? "buffer" : (!size) ? "size" : "unknown");
		return -1;
	}

	if((in.s_addr = inet_addr(ip)) == INADDR_NONE)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid IP address: %s", __func__, ip);
		return -1;
	}

	ip_quad[0] = in.s_addr & 0x00ffffff;
	ip_quad[1] = (in.s_addr & 0xff00ffff) >> 8;
	ip_quad[2] = (in.s_addr & 0xffff00ff) >> 16;
	ip_quad[3] = (in.s_addr & 0xffffff00) >> 24;

	if((in.s_addr = inet_addr(netmask)) == INADDR_NONE)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid netmask: %s", __func__, netmask);
		return -1;
	}

	snprintf(buffer, size, "%u.%u.%u.%u",
		(unsigned int)(ip_quad[0] & in.s_addr & 0x00ffffff),
		(unsigned int)(ip_quad[1] & (in.s_addr & 0xff00ffff) >> 8),
		(unsigned int)(ip_quad[2] & (in.s_addr & 0xffff00ff) >> 16),
		(unsigned int)(ip_quad[3] & (in.s_addr & 0xffffff00) >> 24));

	return strlen(buffer);
}

#include <math.h>

// Store the string prefix of the given netmask address in buffer.
// Return the length of the address string or -1 on error.
int if_get_prefix(if_ctx *p_ctx, const char *netmask, char *buffer, size_t size)
{
	int i;
	struct in_addr in;

	if(!p_ctx)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!netmask || !buffer || !size)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!netmask) ? "netmask" : (!buffer) ? "buffer" : (!size) ? "size" : "unknown");
		return -1;
	}

	if((in.s_addr = inet_addr(netmask)) == INADDR_NONE)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid netmask: %s", __func__, netmask);
		return -1;
	}

	// Does this scare you?
	for(i = 0; i < sizeof(in.s_addr) * 8; i++)
		if((ntohl(in.s_addr) & (unsigned)pow(2, i))) break;

	snprintf(buffer, size, "%u", (unsigned int)(sizeof(in.s_addr) * 8 - i));

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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !buffer || !size)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!buffer) ? "buffer" : (!size) ? "size" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if((if_get_flags(p_ctx, device, &flags)) == -1) return -1;

    if(!(flags & IFF_BROADCAST)) return 0;

	if(ioctl(p_ctx->sd, SIOCGIFBRDADDR, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFBRDADDR: %s", __func__, strerror(errno));
		return -1;
	}

	memset(buffer, 0, size);

//	if(strcmp("0.0.0.0", inet_ntoa(inaddrr(ifr_addr.sa_data))))
	strncpy(buffer, inet_ntoa(inaddrr(ifr_addr.sa_data)), size - 1);

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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !buffer || !size)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!buffer) ? "buffer" : (!size) ? "size" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFHWADDR, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFHWADDR: %s", __func__, strerror(errno));
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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid parameter: %s", __func__, "device");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFFLAGS, (char *)&ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFFLAGS: %s", __func__, strerror(errno));
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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !mtu)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!mtu) ? "mtu" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFMTU, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFMTU: %s", __func__, strerror(errno));
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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !metric)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!metric) ? "metric" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	if(ioctl(p_ctx->sd, SIOCGIFMETRIC, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCGIFMETRIC: %s", __func__, strerror(errno));
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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid parameter: %s", __func__, "device");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	ev.data = 0;
	ev.cmd = 0x0000000a;

	ifr.ifr_data = (caddr_t)&ev;

	if(ioctl(p_ctx->sd, SIOCETHTOOL, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCETHTOOL: %s", __func__, strerror(errno));
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
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: Invalid context handle", __func__);
		return -1;
	}

	if(!device || !speed)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR, "%s: Invalid parameter: %s", __func__,
			(!device) ? "device" : (!speed) ? "speed" : "unknown");
		return -1;
	}

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	ec.cmd = 0x00000001;
	ifr.ifr_data = (caddr_t)&ec;

	if(ioctl(p_ctx->sd, SIOCETHTOOL, &ifr) == -1)
	{
		snprintf(p_ctx->last_error, MAX_ERROR_STR,
			"%s: SIOCETHTOOL: %s", __func__, strerror(errno));
		return -1;
    }

	memcpy(speed, &ec.speed, sizeof(unsigned short));

	return 0;
}

#ifdef IFCONFIG_TEST

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

#endif

// vi: ts=4
