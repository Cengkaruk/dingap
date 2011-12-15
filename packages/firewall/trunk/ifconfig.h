///////////////////////////////////////////////////////////////////////////////
//
// ifconfig.h
// Network interface utility library declarations.
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

#ifndef IFCONFIG_H
#define IFCONFIG_H

#define IF_DEBUG                0x0001

#define IF_FREE(p)              free(p)
#define IF_MALLOC(n)            malloc(n)
#define IF_STRDUP(p)            strdup(p)

#define MAX_INTERFACES          1024
#define MAX_ERROR_STR           1024
#define PROC_NET_DEV            "/proc/net/dev"
#define SYSCONF_NET_SCRIPTS     "/etc/sysconfig/network-scripts"
#define inaddrr(x)              (*(struct in_addr *) &ifr.x[sizeof(sa.sin_port)])

// Context handle
typedef struct IF_CTX_t
{
    int sd;
    char *last_error;
    unsigned short flags;
    char *interfaces[MAX_INTERFACES];
    char *pppoe[MAX_INTERFACES];
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
    __uint32_t cmd;
    __uint32_t supported;
    __uint32_t advertising;
    __uint16_t speed;
    __uint8_t duplex;
    __uint8_t port;
    __uint8_t phy_address;
    __uint8_t transceiver;
    __uint8_t autoneg;
    __uint32_t maxtxpkt;
    __uint32_t maxrxpkt;
    __uint32_t reserved[4];
};

// Create a context and socket.
// Return a context handle or -1 on error.
if_ctx *if_init(void);

// Free context, close socket.
void if_free(if_ctx *p_ctx);

// Return version string
char *if_version(char *buffer, size_t size);

// Retrieve an array of all interfaces.
// Return the number of interfaces found or -1 on error.
int if_list(if_ctx *p_ctx);

// Retrieve an array of all PPPoE interfaces.
// Return the number of interfaces found or -1 on error.
int if_list_pppoe(if_ctx *p_ctx);

// Is this a PPP device?
int if_isppp(if_ctx *p_ctx, const char *device);

// Store the string address of the given interface (device) in buffer.
// Return the length of the address string or -1 on error.
int if_get_address(if_ctx *p_ctx, const char *device, char *buffer, size_t size);

// Store the string address of the given interface (device) in buffer.
// Return the length of the address string or -1 on error.
int if_get_dst_address(if_ctx *p_ctx, const char *device, char *buffer, size_t size);

// Store the string address of the given interface netmask in buffer.
// Return the length of the address string or -1 on error.
int if_get_netmask(if_ctx *p_ctx, const char *device, char *buffer, size_t size);

// Store the string network address of the given ip and netmask in buffer.
// Return the length of the address string or -1 on error.
int if_get_network(if_ctx *p_ctx, const char *ip, const char *netmask, char *buffer, size_t size);

// Store the string prefix of the given netmask address in buffer.
// Return the length of the address string or -1 on error.
int if_get_prefix(if_ctx *p_ctx, const char *netmask, char *buffer, size_t size);

// Store the string address of the given interface broadcast in buffer.
// Return the length of the address string or -1 on error.
int if_get_broadcast(if_ctx *p_ctx, const char *device, char *buffer, size_t size);

// Store the string address of the given interface's hardwar address,
// (if there is one) in buffer.
// Return the length of the address string or -1 on error.
int if_get_hwaddress(if_ctx *p_ctx, const char *device, char *buffer, size_t size);

// Retrieve flags for device.  Return -1 on error.
int if_get_flags(if_ctx *p_ctx, const char *device, short *flags);

// Retrieve MTU for device.  Return -1 on error.
int if_get_mtu(if_ctx *p_ctx, const char *device, int *mtu);

// Retrieve metric for device.  Return -1 on error.
int if_get_metric(if_ctx *p_ctx, const char *device, int *metric);

// Determine if the device has link (if supported).
// Return 0 for no link, 1 for link detect, and -1 on error.
int if_link_detect(if_ctx *p_ctx, const char *device);

// Store the device's negotiated link speed (if supported).
// Return 0 for no link, 1 for link detect, and -1 on error.
int if_get_speed(if_ctx *p_ctx, const char *device, unsigned short *speed);

#endif // IFCONFIG_H

// vi: ts=4
