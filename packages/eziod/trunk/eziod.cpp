///////////////////////////////////////////////////////////////////////////////
//
// Copyright (C) 2010 ClearFoundation
// http://www.clearfoundation.com
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

#include <string>
#include <map>
#include <stack>

#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <syslog.h>
#include <getopt.h>
#include <time.h>
#include <signal.h>
#include <errno.h>

#include <sys/types.h>
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <sys/param.h>
#include <sys/statvfs.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <linux/if_arp.h>
#include <linux/sockios.h>

#include <sensors/sensors.h>

extern "C" {
#include "lua.h"
#include "lauxlib.h"
#include "lualib.h"
};

#include "ezio.h"
#include "ezioex.h"
#include "ezio300.h"
#include "icon5x8.cpp"

#ifndef _EZIOD_CONF
#define _EZIOD_CONF				"/etc/eziod.conf"
#endif
#ifndef _EZIOD_SCRIPT_DIR
#define _EZIOD_SCRIPT_DIR		"/usr/share/eziod"
#endif
#ifndef _EZIOD_WEBCONFIG
#define _EZIOD_WEBCONFIG		"/usr/webconfig/bin/php"
#endif
#define _EZIOD_SENSORS_CONF		"/etc/sensors.conf"
#define inaddrr(x)				(*(struct in_addr *) &ifr.x[sizeof(sa.sin_port)])

#ifdef _EZIO_HEAVY_DEBUG
static void hex_dump(FILE *fh, const void *data, uint32_t length)
{
	uint8_t c, *p = (uint8_t *)data;
	char bytestr[4] = { 0 };
	char addrstr[10] = { 0 };
	char hexstr[16 * 3 + 5] = { 0 };
	char charstr[16 * 1 + 5] = { 0 };

	for (uint32_t n = 1; n <= length; n++) {
		if (n % 16 == 1) {
			// Store address for this line
			snprintf(addrstr, sizeof(addrstr),
				"%.5x", (uint32_t)(p - (uint8_t *)data));
		}
            
		c = *p;
		if (isprint(c) == 0) c = '.';

		// Store hex str (for left side)
		snprintf(bytestr, sizeof(bytestr), "%02X ", *p);
		strncat(hexstr, bytestr, sizeof(hexstr) - strlen(hexstr) - 1);

		// Store char str (for right side)
		snprintf(bytestr, sizeof(bytestr), "%c", c);
		strncat(charstr, bytestr, sizeof(charstr) - strlen(charstr) - 1);

		if(n % 16 == 0) { 
			// Line completed
			fprintf(fh, "%5.5s:  %-49.49s %s\n", addrstr, hexstr, charstr);
			hexstr[0] = 0;
			charstr[0] = 0;
		} else if(n % 8 == 0) {
			// Half line: add whitespaces
			strncat(hexstr, " ", sizeof(hexstr) - strlen(hexstr) -1);
		}
		// Next byte...
		p++;
	}

	if (strlen(hexstr) > 0) {
		// Print rest of buffer if not empty
		fprintf(fh, "%5.5s:  %-49.49s %s\n", addrstr, hexstr, charstr);
	}
}

ssize_t ezioRead(int fd, void *buf, size_t count)
{
	fprintf(stderr, "%2d < %3d = ", fd, count);
	ssize_t bytes = read(fd, buf, count);
	fprintf(stderr, "%ld\n", bytes);
	if (bytes > 0) hex_dump(stderr, buf, bytes);
	return bytes;
}

ssize_t ezioWrite(int fd, const void *buf, size_t count)
{
	fprintf(stderr, "%2d > %3d = ", fd, count);
	ssize_t bytes = write(fd, buf, count);
	fprintf(stderr, "%ld\n", bytes);
	if (bytes > 0) hex_dump(stderr, buf, bytes);
	return bytes;
}
#endif

extern int errno;
static ezio300 *ezio;
static lua_State *lua;
static bool term = false;
static int last_signal = 0;
static const sensors_chip_name *sensors_chip;
static bool debug = false;

static int webconfig_request(lua_State *L, const char *script, int fields)
{
	int buffer_len = getpagesize();
	char *token, buffer[buffer_len];

	snprintf(buffer, buffer_len, "%s -f %s/%s",
		_EZIOD_WEBCONFIG, _EZIOD_SCRIPT_DIR, script);

	FILE *ph = popen(buffer, "r");
	if (!ph) {
		lua_pushnil(L);
		return 1;
	}

	int tokens = 0; char *newline = NULL;
	while (!feof(ph) && tokens == 0) {
		if (!fgets(buffer, buffer_len, ph)) break;
		if (!memchr(buffer, '|', buffer_len)) continue;
		token = strtok(buffer, "|");
		if (!token) continue;
		if ((newline = (char *)memchr(token, '\n', strlen(token))))
			*newline = 0;
		lua_pushstring(L, token);
		for (tokens++ ;; tokens++) {
			token = strtok(NULL, "|");
			if (!token) break;
			if ((newline = (char *)memchr(token, '\n', strlen(token))))
				*newline = 0;
			lua_pushstring(L, token);
		}
	}

	if (pclose(ph) != 0 || tokens != fields) {
		if (tokens) lua_pop(L, tokens);
		lua_pushnil(L);
		return 1;
	}

	return tokens;
}

static int lua_echo(lua_State *L)
{
	const char *text = luaL_checkstring(L, 1);

	syslog(LOG_NOTICE, "%s", text);

	return 0;
}

static int lua_debug(lua_State *L)
{
	const char *text = luaL_checkstring(L, 1);

	if (debug) syslog(LOG_DEBUG, "%s", text);

	return 0;
}

static int lua_execute(lua_State *L)
{
	int rc = 0;
	const char *command = luaL_checkstring(L, 1);

	FILE *ph = popen(command, "r");
	rc = pclose(ph);

	if(debug) syslog(LOG_DEBUG, "%s = %d", command, rc);

	lua_pushnumber(L, rc);
	return 1;
}

static int lua_sleep(lua_State *L)
{
	int v = (int)luaL_checknumber(L, 1);
	sleep(v);
	return 0;
}

static int lua_time(lua_State *L)
{
	lua_pushnumber(L, time(NULL));
	return 1;
}

static int lua_localtime(lua_State *L)
{
	time_t now = time(NULL);
	struct tm *tm_now = localtime(&now);
	char local_time[16 + 1], local_date[16 + 1];
	strftime(local_time, sizeof(local_time), "%X", tm_now);
	strftime(local_date, sizeof(local_date), "%x", tm_now);

	lua_pushstring(L, local_time);
	lua_pushstring(L, local_date);
	return 2;
}

static int lua_getmtime(lua_State *L)
{
	const char *path = luaL_checkstring(L, 1);
	struct stat path_stat;

	if (stat(path, &path_stat) != 0) {
		lua_pushnil(L);
		return 1;
	}

	lua_pushnumber(L, path_stat.st_mtime);
	return 1;
}

static int lua_getifaddr(lua_State *L)
{
	const char *device = luaL_checkstring(L, 1);
	struct ifreq ifr;
	struct sockaddr_in sa;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	int sd;
	if((sd = socket(AF_INET, SOCK_DGRAM, IPPROTO_IP)) == -1)
		return 0;
	if(ioctl(sd, SIOCGIFADDR, &ifr) == -1) {
		close(sd);
		return 0;
	}
	close(sd);

	lua_pushstring(L, inet_ntoa(inaddrr(ifr_addr.sa_data)));
	return 1;
}

static int lua_getifnetmask(lua_State *L)
{
	const char *device = luaL_checkstring(L, 1);
	struct ifreq ifr;
	struct sockaddr_in sa;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	int sd;
	if((sd = socket(AF_INET, SOCK_DGRAM, IPPROTO_IP)) == -1)
		return 0;
	if(ioctl(sd, SIOCGIFNETMASK, &ifr) == -1) {
		close(sd);
		return 0;
	}
	close(sd);

	lua_pushstring(L, inet_ntoa(inaddrr(ifr_addr.sa_data)));
	return 1;
}

static int lua_getifup(lua_State *L)
{
	const char *device = luaL_checkstring(L, 1);
	struct ifreq ifr;
	struct sockaddr_in sa;

	memset(&ifr, '\0', sizeof(struct ifreq));
	strncpy(ifr.ifr_name, device, IFNAMSIZ - 1);

	int sd;
	if((sd = socket(AF_INET, SOCK_DGRAM, IPPROTO_IP)) == -1)
		return 0;
	if(ioctl(sd, SIOCGIFFLAGS, (char *)&ifr) == -1) {
		close(sd);
		lua_pushboolean(L, 0);
		return 1;
	}
	close(sd);

	if (ifr.ifr_flags & IFF_UP) lua_pushboolean(L, 1);
	else lua_pushboolean(L, 0);
	return 1;
}

static int lua_terminate(lua_State *L)
{
	if (!term) lua_pushboolean(L, 0);
	else lua_pushboolean(L, 1);
	return 1;
}

static int lua_get_sensor(lua_State *L)
{
	int32_t feature = (int32_t)luaL_checknumber(L, 1);
	double value = 0.0;
	if (sensors_chip != NULL)
		sensors_get_feature(*sensors_chip, feature, &value);

	lua_pushnumber(L, value);
	return 1;
}

static int lua_statvfs(lua_State *L)
{
	const char *path = luaL_checkstring(L, 1);
	struct statvfs stats;

	if (statvfs(path, &stats) != 0) {
		lua_pushnil(L);
		lua_pushnil(L);
		return 2;
    }

	fsblkcnt_t ktotal = ((unsigned long long)stats.f_frsize *
		(unsigned long long)stats.f_blocks) / 1024ul;
	fsblkcnt_t kfree = ((unsigned long long)stats.f_frsize *
		(unsigned long long)stats.f_bfree) / 1024ul;
	fsblkcnt_t kavail = ((unsigned long long)stats.f_frsize *
		(unsigned long long)stats.f_bavail) / 1024ul;
	fsblkcnt_t kused = ktotal - kfree;

	lua_pushnumber(L, ktotal);
	lua_pushnumber(L, kused);

	return 2;
}

static int lua_get_dyndnsinfo(lua_State *L)
{
	return webconfig_request(L, "get_dyndnsinfo.php", 3);
}

static int lua_clear(lua_State *L)
{
	ezio->Write(EZIO_CLEAR);
	return 0;
}

static int lua_clear_eol(lua_State *L)
{
	ezio->ClearEOL();
	return 0;
}

static int lua_blank(lua_State *L)
{
	ezio->Write(EZIO_BLANK);
	return 0;
}

static int lua_cursor_hide(lua_State *L)
{
	ezio->Write(EZIO_HIDE);
	return 0;
}

static int lua_cursor_show_block(lua_State *L)
{
	ezio->Write(EZIO_SHOW_BLOCK);
	return 0;
}

static int lua_cursor_show_uscore(lua_State *L)
{
	ezio->Write(EZIO_SHOW_USCORE);
	return 0;
}

static int lua_cursor_home(lua_State *L)
{
	ezio->Write(EZIO_HOME);
	return 0;
}

static int lua_cursor_move(lua_State *L)
{
	int32_t x = (int32_t)luaL_checknumber(L, 1);
	int32_t y = (int32_t)luaL_checknumber(L, 2);

	ezio->MoveCursor(x, y);
	return 0;
}

static int lua_cursor_left(lua_State *L)
{
	ezio->Write(EZIO_MOVE_LEFT);
	return 0;
}

static int lua_cursor_right(lua_State *L)
{
	ezio->Write(EZIO_MOVE_RIGHT);
	return 0;
}

static int lua_scroll_left(lua_State *L)
{
	ezio->Write(EZIO_SCROLL_LEFT);
	return 0;
}

static int lua_scroll_right(lua_State *L)
{
	ezio->Write(EZIO_SCROLL_RIGHT);
	return 0;
}

static int lua_read_button(lua_State *L)
{
	uint32_t timeout = (uint32_t)luaL_checknumber(L, 1);

	ezioButton button = ezio->ReadKey(timeout);

	lua_pushnumber(L, button);
	return 1;
}

static int lua_read_number(lua_State *L)
{
	int32_t value = (int32_t)luaL_checknumber(L, 1);
	int32_t min = (int32_t)luaL_checknumber(L, 2);
	int32_t max = (int32_t)luaL_checknumber(L, 3);
	int32_t width = (int32_t)luaL_checknumber(L, 4);

	ezioButton button = ezio->ReadInteger(value, min, max, width);
	lua_pushnumber(L, button);
	return 1;
}

static int lua_read_ipaddress(lua_State *L)
{
	const char *prompt = luaL_checkstring(L, 1);
	const char *ip = luaL_checkstring(L, 2);

	char _ip[16];
	strncpy(_ip, ip, sizeof(_ip));

	ezioButton button = ezio->ReadIpAddress(prompt, _ip);

	if (button == EZIO_BUTTON_ESC)
		lua_pushnil(L);
	else lua_pushstring(L, _ip);

	return 1;
}

static int lua_read_choice(lua_State *L)
{
	const char *prompt = luaL_checkstring(L, 1);
	luaL_checktype(L, 2, LUA_TTABLE);

	map<int32_t, const char *> choices;

	lua_pushnil(L);
	while (lua_next(L, 2) != 0) {
		choices[(int32_t)luaL_checknumber(L, -2)] = luaL_checkstring(L, -1);
		lua_pop(L, 1);
	}

	lua_pushnumber(L, ezio->ReadChoice(prompt, choices));
	return 1;
}

static int lua_write_text(lua_State *L)
{
	const char *text = luaL_checkstring(L, 1);
	ezio->WriteText(text);
	return 0;
}

static int lua_write_text_xy(lua_State *L)
{
	int32_t x = (int32_t)luaL_checknumber(L, 1);
	int32_t y = (int32_t)luaL_checknumber(L, 2);
	const char *text = luaL_checkstring(L, 3);

	ezio->WriteText(x, y, text);
	return 0;
}

static int lua_save_state(lua_State *L)
{
	ezio->SaveState();
}

static int lua_restore_state(lua_State *L)
{
	ezio->RestoreState();
}

static void signal_handler(int signum)
{
	last_signal = signum;
	switch (signum) {
	case SIGINT:
	case SIGTERM:
	case SIGHUP:
		term = true;
	}
}

int main(int argc, char *argv[])
{
	char short_options[] = "hc:dp:";
	struct option long_options[] = {
		{ "help", no_argument, NULL, 'h' },
		{ "config", required_argument, NULL, 'c' },
		{ "debug", no_argument, NULL, 'd' },
		{ "port", required_argument, NULL, 'p' },
		{ 0, 0, 0, 0 }
	};

	char *config = strdup(_EZIOD_CONF);
	char *port = strdup(_EZIO_PORT);

	for (;;) {
		int32_t c, index;
		c = getopt_long(argc, argv,
			short_options, long_options, &index);

		if (c == -1) break;

		switch (c) {
		case 0:
			break;

		case 'h':
			fprintf(stderr, "Usage: %s "
				"[-c|--config <config>] [-p|--port <port>] "
				"[-d|--debug]\n", argv[0]);
			return 0;

		case 'c':
			if (config) free(config);
			config = strdup(optarg);
			break;

		case 'd':
			debug = true;
			break;

		case 'p':
			if (port) free(port);
			port = strdup(optarg);
		}
	}

	FILE *sensors_conf = fopen(_EZIOD_SENSORS_CONF, "r");
	if (!sensors_conf) {
		fprintf(stderr, "%s: %s\n", _EZIOD_SENSORS_CONF, strerror(errno));
		return (EXIT_FAILURE);
	}
	if (sensors_init(sensors_conf) != 0) {
		fprintf(stderr, "%s: %s\n", "sensors_init", strerror(errno));
		return (EXIT_FAILURE);
	}

	int chip = 0;
	sensors_chip = sensors_get_detected_chips(&chip);

	signal(SIGINT, signal_handler);
	signal(SIGTERM, signal_handler);
	signal(SIGHUP, signal_handler);

	if (!debug) {
		if (daemon(0, 0) != 0) {
			fprintf(stderr, "%s: %s\n", "daemon", strerror(errno));
			return (EXIT_FAILURE);
		}
		openlog("eziod", LOG_PID, LOG_DAEMON);
	} else openlog("eziod", LOG_PID | LOG_PERROR, LOG_DAEMON);

	lua = lua_open();
	luaL_openlibs(lua);

	lua_pushnumber(lua, EZIO_BUTTON_ALT0);
	lua_setglobal(lua, "EZIO_BUTTON_ALT0");

	lua_pushnumber(lua, EZIO_BUTTON_ALT1);
	lua_setglobal(lua, "EZIO_BUTTON_ALT1");

	lua_pushnumber(lua, EZIO_BUTTON_ALT2);
	lua_setglobal(lua, "EZIO_BUTTON_ALT2");

	lua_pushnumber(lua, EZIO_BUTTON_ALT3);
	lua_setglobal(lua, "EZIO_BUTTON_ALT3");

	lua_pushnumber(lua, EZIO_BUTTON_ALT4);
	lua_setglobal(lua, "EZIO_BUTTON_ALT4");

	lua_pushnumber(lua, EZIO_BUTTON_ALT5);
	lua_setglobal(lua, "EZIO_BUTTON_ALT5");

	lua_pushnumber(lua, EZIO_BUTTON_ALT6);
	lua_setglobal(lua, "EZIO_BUTTON_ALT6");

	lua_pushnumber(lua, EZIO_BUTTON_ESC);
	lua_setglobal(lua, "EZIO_BUTTON_ESC");

	lua_pushnumber(lua, EZIO_BUTTON_ALT8);
	lua_setglobal(lua, "EZIO_BUTTON_ALT8");

	lua_pushnumber(lua, EZIO_BUTTON_ALT9);
	lua_setglobal(lua, "EZIO_BUTTON_ALT9");

	lua_pushnumber(lua, EZIO_BUTTON_ALTA);
	lua_setglobal(lua, "EZIO_BUTTON_ALTA");

	lua_pushnumber(lua, EZIO_BUTTON_ENTER);
	lua_setglobal(lua, "EZIO_BUTTON_ENTER");

	lua_pushnumber(lua, EZIO_BUTTON_ALTC);
	lua_setglobal(lua, "EZIO_BUTTON_ALTC");

	lua_pushnumber(lua, EZIO_BUTTON_DOWN);
	lua_setglobal(lua, "EZIO_BUTTON_DOWN");

	lua_pushnumber(lua, EZIO_BUTTON_UP);
	lua_setglobal(lua, "EZIO_BUTTON_UP");

	lua_pushnumber(lua, EZIO_BUTTON_NONE);
	lua_setglobal(lua, "EZIO_BUTTON_NONE");

	lua_pushcfunction(lua, lua_debug);
	lua_setglobal(lua, "debug");

	lua_pushcfunction(lua, lua_echo);
	lua_setglobal(lua, "echo");

	lua_pushcfunction(lua, lua_execute);
	lua_setglobal(lua, "execute");

	lua_pushcfunction(lua, lua_sleep);
	lua_setglobal(lua, "sleep");

	lua_pushcfunction(lua, lua_time);
	lua_setglobal(lua, "time");

	lua_pushcfunction(lua, lua_localtime);
	lua_setglobal(lua, "localtime");

	lua_pushcfunction(lua, lua_getmtime);
	lua_setglobal(lua, "getmtime");

	lua_pushcfunction(lua, lua_getifaddr);
	lua_setglobal(lua, "getifaddr");

	lua_pushcfunction(lua, lua_getifnetmask);
	lua_setglobal(lua, "getifnetmask");

	lua_pushcfunction(lua, lua_getifup);
	lua_setglobal(lua, "getifup");

	lua_pushcfunction(lua, lua_terminate);
	lua_setglobal(lua, "terminate");

	lua_pushcfunction(lua, lua_get_sensor);
	lua_setglobal(lua, "get_sensor");

	lua_pushcfunction(lua, lua_statvfs);
	lua_setglobal(lua, "statvfs");

	lua_pushcfunction(lua, lua_get_dyndnsinfo);
	lua_setglobal(lua, "get_dyndnsinfo");

	lua_pushcfunction(lua, lua_clear);
	lua_setglobal(lua, "clear");

	lua_pushcfunction(lua, lua_clear_eol);
	lua_setglobal(lua, "clear_eol");

	lua_pushcfunction(lua, lua_blank);
	lua_setglobal(lua, "blank");

	lua_pushcfunction(lua, lua_cursor_hide);
	lua_setglobal(lua, "cursor_hide");

	lua_pushcfunction(lua, lua_cursor_show_block);
	lua_setglobal(lua, "cursor_show_block");

	lua_pushcfunction(lua, lua_cursor_show_uscore);
	lua_setglobal(lua, "cursor_show_uscore");

	lua_pushcfunction(lua, lua_cursor_home);
	lua_setglobal(lua, "cursor_home");

	lua_pushcfunction(lua, lua_cursor_move);
	lua_setglobal(lua, "cursor_move");

	lua_pushcfunction(lua, lua_cursor_left);
	lua_setglobal(lua, "cursor_left");

	lua_pushcfunction(lua, lua_cursor_right);
	lua_setglobal(lua, "cursor_right");

	lua_pushcfunction(lua, lua_scroll_left);
	lua_setglobal(lua, "scroll_left");

	lua_pushcfunction(lua, lua_scroll_right);
	lua_setglobal(lua, "scroll_right");

	lua_pushcfunction(lua, lua_read_button);
	lua_setglobal(lua, "read_button");

	lua_pushcfunction(lua, lua_read_number);
	lua_setglobal(lua, "read_number");

	lua_pushcfunction(lua, lua_read_ipaddress);
	lua_setglobal(lua, "read_ipaddress");

	lua_pushcfunction(lua, lua_read_choice);
	lua_setglobal(lua, "read_choice");

	lua_pushcfunction(lua, lua_write_text);
	lua_setglobal(lua, "write_text");

	lua_pushcfunction(lua, lua_write_text_xy);
	lua_setglobal(lua, "write_text_xy");

	lua_pushcfunction(lua, lua_save_state);
	lua_setglobal(lua, "save_state");

	lua_pushcfunction(lua, lua_restore_state);
	lua_setglobal(lua, "restore_state");

	try {
		struct ezioIconData icon_data = {
			icon_5x8_width,
			icon_5x8_height,
			icon_5x8_length,
			icon_5x8
		};
		ezio = new ezio300(port, &icon_data);
		//ezio = new ezio300(port);
		ezio->Write(EZIO_HIDE);
		ezio->Write(EZIO_CLEAR);

		while (true) {
			if(luaL_loadfile(lua, config) || lua_pcall(lua, 0, 0, 0)) {
				syslog(LOG_ERR, "Error: %s", lua_tostring(lua, -1));
				break;
			}
			if (last_signal != SIGHUP) break;
			syslog(LOG_NOTICE, "Reloading configuration.");
			term = false;
		}

		ezio->Write(EZIO_STOP);

	} catch (ezioException &e) {
		syslog(LOG_ERR, "Exception: %s", e.what());
	}

	sensors_cleanup();
	lua_close(lua);
	delete ezio;
	closelog();

	if (config) free(config);
	if (port) free(port);

	return (EXIT_SUCCESS);
}

// vi: ts=4
