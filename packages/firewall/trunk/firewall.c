///////////////////////////////////////////////////////////////////////////////
//
// main.c
// cc-firewall implementation.
// Copyright (C) 2005-2008 Point Clark Networks
// Copyright (C) 2009-2011 ClearFoundation
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

#include <string.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <sys/types.h>
#include <sys/time.h>
#include <linux/types.h>
#include <unistd.h>
#include <getopt.h>
#include <assert.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include <errno.h>
#include <syslog.h>
#include <signal.h>

#ifndef FIREWALL_IPV6
#include <iptables.h>
#define FIREWALL_NFPROTO NFPROTO_IPV4
#else
#include <ip6tables.h>
#define do_command do_command6
#define iptables_globals ip6tables_globals
#define iptc_builtin ip6tc_builtin
#define iptc_commit ip6tc_commit
#define iptc_create_chain ip6tc_create_chain
#define iptc_delete_chain ip6tc_delete_chain
#define iptc_first_chain ip6tc_first_chain
#define iptc_flush_entries ip6tc_flush_entries
#define iptc_free ip6tc_free
#define iptc_init ip6tc_init
#define iptc_next_chain ip6tc_next_chain
#define iptc_set_policy ip6tc_set_policy
#define iptc_strerror ip6tc_strerror
#define ipt_chainlabel ip6t_chainlabel
#define ipt_counters ip6t_counters
#define FIREWALL_NFPROTO NFPROTO_IPV6
#endif

#include <lua.h>
#include <lauxlib.h>
#include <lualib.h>

#include "ifconfig.h"

// Debug, pretend, and strict mode flags
static int debug = 0;
static int pretend = 0;
static int strict = 0;

// Global return code, set by eprintf()
static int grc = 0;

// Network interface context handle
static if_ctx *IFC = NULL;

// Lua context handle
static lua_State *LUA = NULL;

// Iptables command argument count and array
static int iptc_argc = 0;
static char *iptc_argv[255];

// Pointer to source code (firewall.lua) filename
static char *source = NULL;

// Display or log an error.  If strict mode is enabled,
// an error will terminate further Lua execution.
static int eprintf(const char *format, ...)
{
    int rc = 0;
    va_list ap;
    char buffer[256];

    grc = 1;

    va_start(ap, format);

    vsnprintf(buffer, sizeof(buffer), format, ap);

    if(strict)
        rc = luaL_error(LUA, buffer);
    else
    {
        lua_Debug lD;
        char prefix[256];

        lua_getstack(LUA, 1, &lD);
        lua_getinfo(LUA, "l", &lD);
        snprintf(prefix, sizeof(prefix),
            "Error: %s:%d: %s", source, lD.currentline, buffer);
        syslog(LOG_ERR, prefix);
    }

    va_end(ap);

    return rc;
}

// Add argument to iptables command line
static int argv_add(const char *arg)
{
    if(arg && (iptc_argc + 1) < sizeof(iptc_argv) / sizeof(char *))
    {
        iptc_argv[iptc_argc] = strdup(arg);
        iptc_argc++;

        return 1;
    }

    return 0;
}

// Free iptables command line array
static void argv_free(void)
{
    int i;
    for(i = 0; i < iptc_argc; i++) free(iptc_argv[i]);
    iptc_argc = 0;
}

// Pre-parse an iptables command line string.
// Ripped from iptables-restore.c with a few modifications.
static int argv_parse(const char *table, char *buffer)
{
    char *c;
    int quote = 0;
    char *parse_start = buffer;
    char *param_start = buffer;

    argv_free();

    argv_add(iptables_globals.program_name);
    argv_add("-t");
    argv_add(table);
            
    for(c = parse_start; *c; c++)
    {
        if(*c == '"')
        {
            // Quote cannot be true if there was no previous character.
            // Thus, c - 1 has to be within bounds.
            if(quote && *(c - 1) != '\\')
            {
                quote = 0; *c = ' ';
            }
            else
            {
                quote = 1; param_start++;
            }
        } 

        if(*c == ' ' || !*(c + 1))
        {
            char param_buffer[1024];
            int param_len = c - param_start;

            if(quote) continue;

            if(!*(c + 1)) param_len++;

            if(!param_len)
            {
                // two spaces?
                param_start++;
                continue;
            }
                    
            // end of one parameter
            strncpy(param_buffer, param_start, param_len);
            *(param_buffer + param_len) = '\0';

            argv_add(param_buffer);
            param_start += param_len + 1;
        }
    }

    return 0;
}

// An iptables table name and context handle.
struct table_t
{
    char *name;
#ifndef FIREWALL_IPV6
    struct iptc_handle *handle;
#else
    struct ip6tc_handle *handle;
#endif
};

// Global list of 'built-in' iptables tables (filter, mangle, nat).
static struct table_t *tables = NULL;

// Look-up iptables table name, returns -1 if not found.
static int find_table(const char *name)
{
    int i;

    for(i = 0; tables[i].name; i++)
        if(!strncmp(tables[i].name, name, 32)) return i;

    return -1;
}

// All exported Lua functions start with __lua.  See main() below for their
// name mappings into Lua.

// Binary AND operator.
// Lua doesn't have one of these nor binary OR.
static int __lua_b_and(lua_State *L)
{
    double a = luaL_checknumber(L, 1);
    double b = luaL_checknumber(L, 2);

    lua_pushnumber(L, ((unsigned)a & (unsigned)b));

    return 1;
}

// Binary OR operator.
static int __lua_b_or(lua_State *L)
{
    double a = luaL_checknumber(L, 1);
    double b = luaL_checknumber(L, 2);

    lua_pushnumber(L, ((unsigned)a | (unsigned)b));

    return 1;
}

// Convert string IP address (x.x.x.x) to binary IP address in host byte order.
static int __lua_iptc_ip2bin(lua_State *L)
{
    struct in_addr addr;
    const char *ip = luaL_checkstring(L, 1);

    if(inet_aton(ip, &addr) == 0)
        return eprintf("Invalid IP address: %s", ip);

    lua_pushnumber(L, ntohl(addr.s_addr));
    return 1;
}

// Convert binary IP address to string IP address.
static int __lua_iptc_bin2ip(lua_State *L)
{
    struct in_addr addr;
    double bin = luaL_checknumber(L, 1);

    addr.s_addr = htonl(bin);
    lua_pushstring(L, inet_ntoa(addr));
    return 1;
}

// Return the corresponding string that matches the numeric protocol (if any).
static int __lua_p_name(lua_State *L)
{
    struct protoent *pe;
    const char *proto = luaL_checkstring(L, 1);

    if((pe = getprotobynumber(atoi(proto))))
        lua_pushstring(L, pe->p_name);
    else
        lua_pushstring(L, proto);

    return 1;
}

// Get host by name.
static int __lua_gethostbyname(lua_State *L)
{
    struct sockaddr_in sa;
    const char *host = luaL_checkstring(L, 1);

    sa.sin_addr.s_addr = inet_addr(host);

    if(sa.sin_addr.s_addr == INADDR_NONE)
    {
        struct hostent *he;

        if((he = gethostbyname(host)))
        {
            memcpy(&sa.sin_addr, he->h_addr, he->h_length);
            lua_pushstring(L, inet_ntoa(sa.sin_addr));
        }
        else lua_pushnil(L);
    }
    else lua_pushstring(L, inet_ntoa(sa.sin_addr));

    return 1;
}

// Informational message 'echo'.
static int __lua_echo(lua_State *L)
{
    const char *text = luaL_checkstring(L, 1);

    syslog(LOG_NOTICE, text);

    return 0;
}

// Debug message 'echo'.
static int __lua_debug(lua_State *L)
{
    const char *text = luaL_checkstring(L, 1);

    if(!debug) return 0;

    syslog(LOG_DEBUG, text);

    return 0;
}

// Execute a system command.
static int __lua_execute(lua_State *L)
{
    FILE *h;
    int rc = 0;
    const char *command = luaL_checkstring(L, 1);

    if(!pretend && (h = popen(command, "r"))) rc = pclose(h);

    if(pretend) syslog(LOG_DEBUG, command);
    else if(debug) syslog(LOG_DEBUG, "%s = %d", command, rc);

    lua_pushnumber(L, rc);
    return 1;
}

// Initialize built-in iptable tables (filter, mangle, nat).
static int __lua_iptc_init(lua_State *L)
{
    int i;

    if(!tables)
    {
        tables = calloc(4, sizeof(struct table_t));

        tables[0].name = strdup("filter");
        tables[1].name = strdup("mangle");
        tables[2].name = strdup("nat");
    }

    for(i = 0; tables[i].name; i++)
    {
        if(tables[i].handle) continue;

        tables[i].handle = iptc_init(tables[i].name);

        if(!tables[i].handle)
        {
            return eprintf("Unable to initialize table: %s: %s.",
                tables[i].name, iptc_strerror(errno));
        }
    }

    return 0;
}

// Create a new iptables chain.
static int __lua_iptc_create_chain(lua_State *L)
{
    int i, r;
    const char *table = luaL_checkstring(L, 1);
    const char *chain = luaL_checkstring(L, 2); 

    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    r = iptc_create_chain(chain, tables[i].handle);
    if(!r) return eprintf("Unable to create chain: %s: %s", table, chain);

    lua_pushnumber(L, (r) ? 0 : -1);
    return 1;
}

// Delete existing iptables chain.
static int __lua_iptc_delete_chain(lua_State *L)
{
    int i, r;
    const char *table = luaL_checkstring(L, 1);
    const char *chain = luaL_checkstring(L, 2); 

    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    r = iptc_delete_chain(chain, tables[i].handle);
    if(!r) return eprintf("Unable to delete chain: %s: %s", table, chain);

    lua_pushnumber(L, 0);
    return 1;
}

// Delete all rules from existing iptables chain (flush).
static int __lua_iptc_flush_chain(lua_State *L)
{
    int i;
    const char *table = luaL_checkstring(L, 1);
    const char *chain = luaL_checkstring(L, 2); 

    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    iptc_flush_entries(chain, tables[i].handle);

    return 0;
}

// Recursive delete of all iptables chains.
static int __lua_iptc_flush_all_chains(lua_State *L)
{
    int i, r;
    const char *chain;
    const char *table = luaL_checkstring(L, 1);

    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    chain = iptc_first_chain(tables[i].handle);

    while(chain)
    {
        r = iptc_flush_entries(chain, tables[i].handle);
        if(!r) return eprintf("Unable to flush chain: %s: %s", table, chain);
        chain = iptc_next_chain(tables[i].handle);
    }

    return 0;
}

// Commit table changes.
static int __lua_iptc_commit(lua_State *L)
{
    int i, r;
    const char *table = luaL_checkstring(L, 1);

    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    if(pretend) return 0;

    r = iptc_commit(tables[i].handle);
    tables[i].handle = NULL;

    if(!r)
    {
        return eprintf("Unable to commit: %s: %s.",
            table, iptc_strerror(errno));
    }

    return 0;
}

// Iptables command.  Essentially the same as the command line version.
static int __lua_iptables(lua_State *L)
{
    int i, r;
    char *rule_copy = NULL;
    const char *table = luaL_checkstring(L, 1);
    const char *rule = luaL_checkstring(L, 2);

    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    if(debug) syslog(LOG_DEBUG, "iptables -t %s %s", table, rule);

    rule_copy = strdup(rule);
    argv_parse(table, rule_copy);

    r = do_command(iptc_argc, iptc_argv, &iptc_argv[2], &tables[i].handle);
    if(!r) return eprintf("iptables -t %s %s", table, rule);

    argv_free();
    free(rule_copy);

    return 0;
}

// Set default policy for an iptables table.
static int __lua_iptc_set_policy(lua_State *L)
{
    int i, r;
    struct ipt_counters counters;
    const char *table = luaL_checkstring(L, 1);
    const char *chain = luaL_checkstring(L, 2);
    const char *policy = luaL_checkstring(L, 3);

    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    memset(&counters, 0, sizeof(struct ipt_counters));

    r = iptc_set_policy(chain, policy, &counters, tables[i].handle);
    if(!r)
    {
        return eprintf("Unable to set policy to %s: %s: %s",
            policy, table, chain);
    }

    return 0;
}

// Delete all user-defined iptables chains.
static int __lua_iptc_delete_user_chains(lua_State *L)
{
    int i;
    const char *chain;
    const char *table = luaL_checkstring(L, 1);
    char *chains;
    unsigned int c, count = 0;
    
    i = find_table(table);

    if(i == -1)
        return eprintf("Invalid table: %s", table);

    if(!tables[i].handle)
        return eprintf("Invalid table: %s", table);

    chain = iptc_first_chain(tables[i].handle);

    while(chain)
    {
        count++;
        chain = iptc_next_chain(tables[i].handle);
    }
    
    chains = malloc(sizeof(ipt_chainlabel) * count);

    c = 0;
    chain = iptc_first_chain(tables[i].handle);

    while(chain)
    {
        strcpy(chains + c * sizeof(ipt_chainlabel), chain);
        c++;
        chain = iptc_next_chain(tables[i].handle);
    }

    for(c = 0; c < count; c++)
    {
        if(iptc_builtin(chains + c * sizeof(ipt_chainlabel), tables[i].handle))
            continue;

        iptc_delete_chain(chains + c * sizeof(ipt_chainlabel), tables[i].handle);
    }

    free(chains);

// XXX: Nope!  Must copy chain names (as above, from iptables.c).
#if 0
    chain = iptc_first_chain(tables[i].handle);

    while(chain)
    {
        fprintf(stderr, "chain \"%s\", [%d]0x%08x\n", chain, i, tables[i].handle);

        if(!iptc_builtin(chain, tables[i].handle))
        {
            fprintf(stderr, "iptc_delete_chain: %d\n",
                iptc_delete_chain(chain, tables[i].handle));
        }

        chain = iptc_next_chain(tables[i].handle);
    }
#endif

    return 0;
}

// Return list of all network interfaces.
static int __lua_if_list(lua_State *L)
{
    int i, count;

    if((count = if_list(IFC)) > 0)
    {
        lua_newtable(L);

        for(i = 0; i < count; i++)
        {
            lua_pushnumber(L, i + 1);
            lua_pushstring(L, IFC->interfaces[i]);
            lua_settable(L, -3);
        }

        return 1;
    }

    return 0;
}

// Return list of all PPPoE interfaces.
static int __lua_if_list_pppoe(lua_State *L)
{
    int i, count;

    if((count = if_list_pppoe(IFC)) > 0)
    {
        lua_newtable(L);

        for(i = 0; i < count; i++)
        {
            lua_pushnumber(L, i + 1);
            lua_pushstring(L, IFC->pppoe[i]);
            lua_settable(L, -3);
        }

        return 1;
    }

    return 0;
}

// Return IP address of selected network interface.
static int __lua_if_address(lua_State *L)
{
    char *buffer;
    size_t size = 32;
    const char *ifn = luaL_checkstring(L, 1);

    buffer = calloc(size, sizeof(char));

    if(if_get_address(IFC, ifn, buffer, size) == -1)
    {
        // XXX: No longer is this a fatal error...  The firewall should
        // be able to trap this...
        syslog(LOG_DEBUG, IFC->last_error);

        free(buffer);
        return 0;
    }

    lua_pushstring(L, buffer);

    free(buffer);
    return 1;
}

// Return destination IP address of selected network interface (PPP).
static int __lua_if_dst_address(lua_State *L)
{
    char *buffer;
    size_t size = 32;
    const char *ifn = luaL_checkstring(L, 1);

    buffer = calloc(size, sizeof(char));

    if(if_get_dst_address(IFC, ifn, buffer, size) == -1)
    {
        free(buffer);
        return eprintf(IFC->last_error);
    }

    lua_pushstring(L, buffer);

    free(buffer);
    return 1;
}

// Return netmask of selected network interface.
static int __lua_if_netmask(lua_State *L)
{
    char *buffer;
    size_t size = 32;
    const char *ifn = luaL_checkstring(L, 1);

    buffer = calloc(size, sizeof(char));

    if(if_get_netmask(IFC, ifn, buffer, size) == -1)
    {
        free(buffer);
        return eprintf(IFC->last_error);
    }

    lua_pushstring(L, buffer);

    free(buffer);
    return 1;
}

// Calculate network address from IP address and netmask.
static int __lua_if_network(lua_State *L)
{
    char *buffer;
    size_t size = 32;
    const char *ip = luaL_checkstring(L, 1);
    const char *mask = luaL_checkstring(L, 2);

    buffer = calloc(size, sizeof(char));

    if(if_get_network(IFC, ip, mask, buffer, size) == -1)
    {
        free(buffer);
        return eprintf(IFC->last_error);
    }

    lua_pushstring(L, buffer);

    free(buffer);
    return 1;
}

// Calculate network prefix from IP address and netmask.
static int __lua_if_prefix(lua_State *L)
{
    char *buffer;
    size_t size = 4;
    const char *mask = luaL_checkstring(L, 1);

    buffer = calloc(size, sizeof(char));

    if(if_get_prefix(IFC, mask, buffer, size) == -1)
    {
        free(buffer);
        return eprintf(IFC->last_error);
    }

    lua_pushstring(L, buffer);

    free(buffer);
    return 1;
}

// Return true if network interface is 'up'.
static int __lua_if_isup(lua_State *L)
{
    short flags;
    const char *ifn = luaL_checkstring(L, 1);

    if(if_get_flags(IFC, ifn, &flags) == -1)
        return eprintf(IFC->last_error);

    if(flags & IFF_UP)
        lua_pushnumber(L, 1);
    else
        lua_pushnumber(L, 0);

    return 1;
}

// Return true if interface is a PPP device.
static int __lua_if_isppp(lua_State *L)
{
    int ppp;
    short flags;
    const char *ifn = luaL_checkstring(L, 1);

    if((ppp = if_isppp(IFC, ifn)) == -1)
        return eprintf(IFC->last_error);

    lua_pushnumber(L, ppp);

    return 1;
}

// Clean-up upon shutdown.
static void exit_handler(void)
{
    int i;

    if(tables)
    {
        for(i = 0; tables[i].name; i++)
        {
            free(tables[i].name);
            if(tables[i].handle)
                iptc_free(tables[i].handle);
        }

        free(tables);
    }

    argv_free();

    if(IFC) if_free(IFC);
    if(LUA) lua_close(LUA);

}

// cc-firewall command line arguments.
static struct option options[] =
{
    { "help", 0, 0, 'h' },
    { "version", 0, 0, 'V' },
    { "debug", 0, 0, 'd'},
    { "pretend", 0, 0, 'p'},
    { "strict", 0, 0, 's'},
    { 0 }
};

// cc-firewall; fire it up!
int main(int argc, char *argv[])
{
    int c;
    float seconds;
    struct timeval tva, tvb;

    iptables_globals.program_name = "firewall";
    if (xtables_init_all(&iptables_globals, FIREWALL_NFPROTO) < 0) {
        fprintf(stderr, "%s: Failed to initialize xtables\n", argv[0]);
        exit(1);
    }
#if defined(ALL_INCLUSIVE) || defined(NO_SHARED_LIBS)
    init_extensions();
#endif
    // Command-line processing
    while((c = getopt_long(argc, argv, "hVdps", options, NULL)) != -1)
    {
        switch(c)
        {
        case 'p':
            pretend = 1;
            // fall through to enable debug also...
        case 'd':
            debug = 1;
            break;
        case 's':
            strict = 1;
            break;
        case 'V':
            fprintf(stderr, "%s v%s\n",
                iptables_globals.program_name, iptables_globals.program_version);
            fprintf(stderr, "Copyright (C) 2005-2008 Point Clark Networks\n");
            fprintf(stderr, "Copyright (C) 2009-2011 ClearFoundation\n");
            exit(1);
        case 'h':
        default:
            fprintf(stderr, "%s [-h|--help]\n", argv[0]);
            fprintf(stderr, "%s [-V|--version]\n", argv[0]);
            fprintf(stderr, "%s [-s|--strict] [-d|--debug] [-p|--pretend] <configuration>\n", argv[0]);
            exit(1);
        }
    }

    // Can only be run by the superuser (root)
    if(getuid() != 0 && geteuid() != 0)
    {
        fprintf(stderr, "%s: %s.\n", argv[0], strerror(EPERM));
        exit(1);
    }

    // Must be passed a Lua script filename to execute
    if(optind != argc - 1)
    {
        // No script filename supplied
        fprintf(stderr, "%s: Required argument missing.\n", iptables_globals.program_name);
        exit(1);
    }

    // Pre-load ip_tables kernel module
    //iptables_insmod("ip_tables", NULL);

    // Install exit handler
    atexit(exit_handler);

    // Initialize ifconfig context
    IFC = if_init();

    // Initialize Lua state
    LUA = lua_open();

    // Load Lua libraries
    luaL_openlibs(LUA);

    // Utility bindings
    lua_pushcfunction(LUA, __lua_b_and);
    lua_setglobal(LUA, "b_and");

    lua_pushcfunction(LUA, __lua_b_or);
    lua_setglobal(LUA, "b_or");

    lua_pushcfunction(LUA, __lua_iptc_ip2bin);
    lua_setglobal(LUA, "ip2bin");

    lua_pushcfunction(LUA, __lua_iptc_bin2ip);
    lua_setglobal(LUA, "bin2ip");

    lua_pushcfunction(LUA, __lua_p_name);
    lua_setglobal(LUA, "p_name");

    lua_pushcfunction(LUA, __lua_gethostbyname);
    lua_setglobal(LUA, "gethostbyname");

    lua_pushcfunction(LUA, __lua_debug);
    lua_setglobal(LUA, "debug");

    lua_pushcfunction(LUA, __lua_echo);
    lua_setglobal(LUA, "echo");

    lua_pushcfunction(LUA, __lua_execute);
    lua_setglobal(LUA, "execute");

    // Iptables bindings
    lua_pushcfunction(LUA, __lua_iptc_init);
    lua_setglobal(LUA, "iptc_init");

    lua_pushcfunction(LUA, __lua_iptc_create_chain);
    lua_setglobal(LUA, "iptc_create_chain");

    lua_pushcfunction(LUA, __lua_iptc_delete_chain);
    lua_setglobal(LUA, "iptc_delete_chain");

    lua_pushcfunction(LUA, __lua_iptc_delete_user_chains);
    lua_setglobal(LUA, "iptc_delete_user_chains");

    lua_pushcfunction(LUA, __lua_iptc_flush_chain);
    lua_setglobal(LUA, "iptc_flush_chain");

    lua_pushcfunction(LUA, __lua_iptc_flush_all_chains);
    lua_setglobal(LUA, "iptc_flush_all_chains");

    lua_pushcfunction(LUA, __lua_iptables);
    lua_setglobal(LUA, "iptables");

    lua_pushcfunction(LUA, __lua_iptc_set_policy);
    lua_setglobal(LUA, "iptc_set_policy");

    lua_pushcfunction(LUA, __lua_iptc_commit);
    lua_setglobal(LUA, "iptc_commit");

    // Network interface bindings (ifconfig.o)
    lua_pushcfunction(LUA, __lua_if_list);
    lua_setglobal(LUA, "if_list");

    lua_pushcfunction(LUA, __lua_if_list_pppoe);
    lua_setglobal(LUA, "if_list_pppoe");

    lua_pushcfunction(LUA, __lua_if_address);
    lua_setglobal(LUA, "if_address");

    lua_pushcfunction(LUA, __lua_if_dst_address);
    lua_setglobal(LUA, "if_dst_address");

    lua_pushcfunction(LUA, __lua_if_netmask);
    lua_setglobal(LUA, "if_netmask");

    lua_pushcfunction(LUA, __lua_if_network);
    lua_setglobal(LUA, "ip_network");

    lua_pushcfunction(LUA, __lua_if_prefix);
    lua_setglobal(LUA, "ip_prefix");

    lua_pushcfunction(LUA, __lua_if_isup);
    lua_setglobal(LUA, "if_isup");

    lua_pushcfunction(LUA, __lua_if_isppp);
    lua_setglobal(LUA, "if_isppp");

    // Open syslog
    {
        int f;
        char *facility = getenv("FW_FACILITY");

        if(facility == NULL)
            f = LOG_LOCAL0;
        else if(strncasecmp(facility, "local0", 6) == 0)
            f = LOG_LOCAL0;
        else if(strncasecmp(facility, "local1", 6) == 0)
            f = LOG_LOCAL1;
        else if(strncasecmp(facility, "local2", 6) == 0)
            f = LOG_LOCAL2;
        else if(strncasecmp(facility, "local3", 6) == 0)
            f = LOG_LOCAL3;
        else if(strncasecmp(facility, "local4", 6) == 0)
            f = LOG_LOCAL4;
        else if(strncasecmp(facility, "local5", 6) == 0)
            f = LOG_LOCAL5;
        else if(strncasecmp(facility, "local6", 6) == 0)
            f = LOG_LOCAL6;
        else
            f = LOG_LOCAL0;

        if(!debug)
            openlog(iptables_globals.program_name, 0, f);
        else
            openlog(iptables_globals.program_name, LOG_PERROR, f);
    }

    gettimeofday(&tva, NULL); 
    
    // Load, compile, and execute supplied Lua script
    if(luaL_loadfile(LUA, (source = argv[optind])) || lua_pcall(LUA, 0, 0, 0))
    {
        syslog(LOG_ERR, "Error: %s\n", lua_tostring(LUA, -1));
        return 1;
    }

    gettimeofday(&tvb, NULL); 

    if(!pretend)
    {
        seconds = tvb.tv_sec - tva.tv_sec;
        seconds += (tvb.tv_usec - tva.tv_usec) / 1000000.0f;
        syslog(LOG_INFO, "Execution time: %.03fs\n", seconds);
    }

    closelog();

    return grc;
}

// vi: ts=4
