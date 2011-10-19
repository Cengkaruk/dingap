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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <string>
#include <exception>
#include <iostream>
#include <map>
#include <stack>

#include <stdlib.h>
#include <unistd.h>
#include <stdio.h>
#include <stdint.h>
#include <termios.h>
#include <fcntl.h>
#include <errno.h>
#include <sys/time.h>
#include <string.h>

#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

extern int errno;

#include "ezio.h"
#include "ezioex.h"
#include "ezio300.h"

// EZIO command/response prefix
static uint8_t ezioInPrefix = EZIO_INPUT;
static uint8_t ezioOutPrefix = EZIO_OUTPUT;

ezio300::ezio300(const char *device, struct ezioIconData *icon_data)
	throw(ezioException)
	: fd_ezio(-1), device(device),
	width(EZIO300_WIDTH), height(EZIO300_HEIGHT), cpl(EZIO300_CPL)
{
	struct termios tio;
	int fd = open(device, O_RDWR);
	if (fd < 0) throw ezioException(EZIOEX_DEVICE_OPEN);

	// Get existing port settings
	if (tcgetattr(fd, &tio) < 0) throw ezioException(EZIOEX_TCGETATTR);

	cfsetospeed(&tio, B2400);
	cfsetispeed(&tio, B2400);

	// Set input modes 
	tio.c_iflag &= ~(IGNBRK | BRKINT | PARMRK | INPCK | ISTRIP | INLCR |
		IGNCR | ICRNL | IUCLC | IXON | IXANY | IXOFF | IMAXBEL);
	tio.c_iflag |= IGNPAR;

	// Set output modes 
	tio.c_oflag &= ~(OPOST | OLCUC | ONLCR | OCRNL | ONOCR | ONLRET |
		OFILL | OFDEL | NLDLY | CRDLY | TABDLY | BSDLY | VTDLY |
		FFDLY);
	tio.c_oflag |= NL0 | CR0 | TAB0 | BS0 | VT0 | FF0;

	// Set control modes
	tio.c_cflag &= ~(CSIZE | PARENB | CRTSCTS | PARODD | HUPCL);
	tio.c_cflag |= CREAD | CS8 | CSTOPB | CLOCAL;

	// Set local modes 
	tio.c_lflag &= ~(ISIG | ICANON | IEXTEN | ECHO | FLUSHO | PENDIN);
	tio.c_lflag |= NOFLSH;

	// Set new port settings
	if (tcsetattr(fd,
		TCSANOW, &tio) < 0) throw ezioException(EZIOEX_TCSETATTR);

	fd_ezio = fd;

	// Allocate default state
	struct ezioState *ds = new ezioState;
	ds->cx = ds->cy = 0;
	ds->ct = EZIO_CT_NONE;

	// Allocate display buffer
	ds->display = new char[cpl * height];
	memset(ds->display, ' ', cpl * height);

	state.push(ds);

	// Initialize device
	Write(EZIO_INIT);
	Write(EZIO_INIT);

	// Load icons if set
	if (icon_data->length && icon_data->data) {
		const uint8_t *data = icon_data->data;
		for (int32_t i = 0; i < icon_data->length; i++) {
			ezioIcon icon;
			switch (data[0]) {
			case EZIO_ICON0:
				icon = EZIO_ICON0;
				break;
			case EZIO_ICON1:
				icon = EZIO_ICON1;
				break;
			case EZIO_ICON2:
				icon = EZIO_ICON2;
				break;
			case EZIO_ICON3:
				icon = EZIO_ICON3;
				break;
			case EZIO_ICON4:
				icon = EZIO_ICON4;
				break;
			case EZIO_ICON5:
				icon = EZIO_ICON5;
				break;
			case EZIO_ICON6:
				icon = EZIO_ICON6;
				break;
			case EZIO_ICON7:
				icon = EZIO_ICON7;
				break;
			default:
				throw ezioException(EZIOEX_INVALID_ICON);
			}
			LoadIcon(icon, ++data, icon_data->height);
			data += icon_data->height;
		}
		Write(EZIO_CLEAR);
	}
}

ezio300::~ezio300()
{
	if (fd_ezio > -1) {
		close(fd_ezio);
		fd_ezio = -1;
	}

	while (!state.empty()) {
		struct ezioState *s = state.top();
		if (!s) continue;
		delete [] s->display;
		delete s;
		state.pop();
	}
}

ezioButton ezio300::ReadKey(uint32_t timeout) throw(ezioException)
{
	struct timeval tv_base;
	gettimeofday(&tv_base, NULL);

	while(true) {
		Write(EZIO_READ);
		memset(input, 0, sizeof(input));
		ssize_t len = ezioRead(fd_ezio, input, sizeof(input));
		if (len < 0) throw ezioException(EZIOEX_INPUT_ERROR);
		if (len < 2) throw ezioException(EZIOEX_INPUT_LENGTH);
		if (input[0] != ezioInPrefix) {
            if (input[0] == 0x02 && input[1] == 0x05) {
                // XXX: Newer panel revisions (V000) send us this
			    continue;
            }
			throw ezioException(EZIOEX_INPUT_INVALID);
		}

		switch (input[1]) {
		case EZIO_BUTTON_ALT0:
		case EZIO_BUTTON_ALT1:
		case EZIO_BUTTON_ALT2:
		case EZIO_BUTTON_ALT3:
		case EZIO_BUTTON_ALT4:
		case EZIO_BUTTON_ALT5:
		case EZIO_BUTTON_ALT6:
		case EZIO_BUTTON_ESC:
		case EZIO_BUTTON_ALT8:
		case EZIO_BUTTON_ALT9:
		case EZIO_BUTTON_ALTA:
		case EZIO_BUTTON_ENTER:
		case EZIO_BUTTON_ALTC:
		case EZIO_BUTTON_DOWN:
		case EZIO_BUTTON_UP:
			return (ezioButton)input[1];
		case EZIO_BUTTON_NONE:
			break;
		default:
			throw ezioException(EZIOEX_INPUT_UNEXPECTED);
		}

		if (timeout == 0) continue;

		struct timeval tv;
		gettimeofday(&tv, NULL);
		if ((tv.tv_sec - tv_base.tv_sec) * 1000000 +
            tv.tv_usec >= (time_t)timeout) break;
	}

	return EZIO_BUTTON_NONE;
}

ezioButton ezio300::ReadInteger(int32_t &value,
	int32_t min_value, int32_t max_value, int32_t width) throw(ezioException)
{
	if (width < 1)
		throw ezioException(EZIOEX_INVALID_INPUT_WIDTH);

	ezioButton button;
	char buffer[width + 1];
	char format[16];
	sprintf(format, "%%%dd", width);
	while (true) {
		snprintf(buffer, width + 1, format, value);
		WriteText(buffer);
		button = ReadKey();
		switch (button) {
		case EZIO_BUTTON_UP:
			if (value < max_value) value++;
			else value = min_value;
			break;
		case EZIO_BUTTON_DOWN:
			if (value > min_value) value--;
			else value = max_value;
			break;
		case EZIO_BUTTON_ESC:
		case EZIO_BUTTON_ENTER:
			return button;
        default:
            break;
		}
	}

	return EZIO_BUTTON_NONE;
}

ezioButton ezio300::ReadIpAddress(const char *prompt, char ip[16]) throw(ezioException)
{
	SaveState();

	int32_t o = 0, value, octet[4];

	struct in_addr addr;
	if (inet_aton(ip, &addr) == 0)
		octet[0] = octet[1] = octet[2] = octet[3] = 0;
	else {
		octet[0] = addr.s_addr & 0x000000ff;
		octet[1] = (addr.s_addr >> 8) & 0x000000ff;
		octet[2] = (addr.s_addr >> 16) & 0x000000ff;
		octet[3] = (addr.s_addr >> 24) & 0x000000ff;
	}

	ezioButton button;

	map<int32_t, const char *> choices;
	choices[0] = "No";
	choices[1] = "Yes";

	Write(EZIO_CLEAR);
	WriteText(0, 0, prompt);
	sprintf(ip, "%3d.%3d.%3d.%3d", octet[0], octet[1], octet[2], octet[3]);
	WriteText(1, 1, ip);
	MoveCursor(1, 1);
	Write(EZIO_SHOW_BLOCK);

	while (true) {
		value = octet[o];
		button = ReadInteger(value, 0, 255, 3);
		while (ReadKey(250000) != EZIO_BUTTON_NONE);
		if (button == EZIO_BUTTON_ENTER) {
			octet[o] = value;
			if (++o == 4) {
				if (ReadChoice("Save changes?", choices) == 1) {
					sprintf(ip, "%d.%d.%d.%d",
						octet[0], octet[1], octet[2], octet[3]);
					break;
				}
				o = 0;
			}
			MoveCursor(o * 4 + 1, 1);
		} else if (button == EZIO_BUTTON_ESC) {
			if (ReadChoice("Discard changes?", choices) == 1) break;
		}
	}

	RestoreState();
	return button;
}

int32_t ezio300::ReadChoice(const char *prompt, map<int32_t, const char *> &choices) throw(ezioException)
{
	if (choices.size() < 2)
		throw ezioException(EZIOEX_INVALID_CHOICE_LEN);

	SaveState();

	Write(EZIO_HIDE);
	Write(EZIO_CLEAR);
	WriteText(0, 0, prompt);
	WriteText(0, 1, "> ");

	while (ReadKey(250000) != EZIO_BUTTON_NONE);

	map<int32_t, const char *>::iterator i = choices.begin();

	while (true) {
		MoveCursor(2, 1);
		ClearEOL();
		WriteText(i->second);

		switch (ReadKey()) {
		case EZIO_BUTTON_UP:
			if (i != choices.begin()) i--;
			else { i = choices.end(); i--; }
			break;
		case EZIO_BUTTON_DOWN:
			if (++i == choices.end())
				i = choices.begin();
			break;
		case EZIO_BUTTON_ENTER:
			RestoreState();
			return i->first;
		case EZIO_BUTTON_ESC:
			RestoreState();
			return -1;
        default:
            break;
		}
	}

	return 0;
}

void ezio300::Write(ezioCommand command, uint8_t param) throw(ezioException)
{
	struct ezioState *s = state.top();

	switch(command) {
	case EZIO_HIDE:
		s->ct = EZIO_CT_NONE;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
	case EZIO_SHOW_BLOCK:
		s->ct = EZIO_CT_BLOCK;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
	case EZIO_SHOW_USCORE:
		s->ct = EZIO_CT_USCORE;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
	case EZIO_BLANK:
	case EZIO_STOP:
	case EZIO_READ:
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
    default:
        break;
	}

	switch (command) {
	case EZIO_INIT:
	case EZIO_CLEAR:
		memset(s->display, ' ', cpl * height);
	case EZIO_HOME:
		s->cx = s->cy = 0;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
	case EZIO_MOVE_LEFT:
		if (s->cx == 0) return;
		s->cx--;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
	case EZIO_MOVE_RIGHT:
		if (s->cx == width - 1) return;
		s->cx++;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
	case EZIO_SCROLL_LEFT:
	case EZIO_SCROLL_RIGHT:
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		return;
    default:
        break;
	}

	switch (command) {
	case EZIO_MOVE_ROW0:
		if (param > width - 1) return;
		s->cx = param; s->cy = 0;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		param += (uint8_t)command;
		if (ezioWrite(fd_ezio, &param, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		break;
	case EZIO_MOVE_ROW1:
		if (param > width - 1) return;
		s->cx = param; s->cy = 1;
		if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		param += (uint8_t)command;
		if (ezioWrite(fd_ezio, &param, sizeof(uint8_t)) !=
			sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
		break;
	default:
		throw ezioException(EZIOEX_INVALID_COMMAND);
	}
}

void ezio300::WriteText(const char *text) throw(ezioException)
{
	if (text == NULL) throw ezioException(EZIOEX_INVALID_TEXT);

	struct ezioState *s = state.top();

	// Calculate length and offset
	size_t len = strlen(text);
	if (len == 0) return;
	if (len > cpl * height) len = cpl * height;
	size_t offset = 0;
	if (s->cy > 0) offset = s->cy * cpl;
	if (s->cx > 0) offset += s->cx;
	if (offset + len > cpl * height) len = (offset + len) - cpl * height;
	if (len == 0) return;

	// Update display buffer
	memcpy(s->display + offset, text, len);

	// Move cursor to home position
	uint8_t command = EZIO_HOME;
	if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
		sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
	if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
		sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);

	// Write display buffer
	if (ezioWrite(fd_ezio, s->display, cpl * height) != cpl * height)
		throw ezioException(EZIOEX_WRITE);

	// Restore previous cursor position
	MoveCursor(s->cx, s->cy);
}

void ezio300::WriteText(uint8_t x, uint8_t y, const char *text) throw(ezioException)
{
	MoveCursor(x, y);
	WriteText(text);
}

void ezio300::MoveCursor(uint8_t x, uint8_t y) throw(ezioException)
{
	ezioCommand command;
	switch (y) {
	case 0:
		command = EZIO_MOVE_ROW0;
		break;
	case 1:
		command = EZIO_MOVE_ROW1;
		break;
	default:
		throw ezioException(EZIOEX_INVALID_ROW);
	}
	if (x > width - 1) throw ezioException(EZIOEX_INVALID_COL);
	Write(command, x);
}

void ezio300::ClearEOL(void) throw(ezioException)
{
	struct ezioState *s = state.top();

	// Calculate length and offset
	size_t offset = 0;
	if (s->cy > 0) offset = s->cy * cpl;
	if (s->cx > 0) offset += s->cx;
	size_t len = cpl * height - offset;
	if (len == 0) return;

	// Update display buffer
	memset(s->display + offset, ' ', len);

	// Move cursor to home position
	uint8_t command = EZIO_HOME;
	if (ezioWrite(fd_ezio, &ezioOutPrefix, sizeof(uint8_t)) !=
		sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);
	if (ezioWrite(fd_ezio, &command, sizeof(uint8_t)) !=
		sizeof(uint8_t)) throw ezioException(EZIOEX_WRITE);

	// Write display buffer
	if (ezioWrite(fd_ezio, s->display, cpl * height) != cpl * height)
		throw ezioException(EZIOEX_WRITE);

	// Restore previous cursor position
	MoveCursor(s->cx, s->cy);
}

void ezio300::LoadIcon(ezioIcon icon, const uint8_t *data, uint8_t icon_height)
{
	uint8_t bank;

	switch (icon) {
	case EZIO_ICON0:
		bank = EZIO_ICON_BANK0;
		break;
	case EZIO_ICON1:
		bank = EZIO_ICON_BANK1;
		break;
	case EZIO_ICON2:
		bank = EZIO_ICON_BANK2;
		break;
	case EZIO_ICON3:
		bank = EZIO_ICON_BANK3;
		break;
	case EZIO_ICON4:
		bank = EZIO_ICON_BANK4;
		break;
	case EZIO_ICON5:
		bank = EZIO_ICON_BANK5;
		break;
	case EZIO_ICON6:
		bank = EZIO_ICON_BANK6;
		break;
	case EZIO_ICON7:
		bank = EZIO_ICON_BANK7;
		break;
	default:
		throw ezioException(EZIOEX_INVALID_ICON);
	}

	for (uint8_t i = 0; i < icon_height; i++, bank++) {
		if (ezioWrite(fd_ezio, &ezioOutPrefix,
			sizeof(uint8_t)) != sizeof(uint8_t))
			throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &bank,
			sizeof(uint8_t)) != sizeof(uint8_t))
			throw ezioException(EZIOEX_WRITE);
		if (ezioWrite(fd_ezio, &data[i],
			sizeof(uint8_t)) != sizeof(uint8_t))
			throw ezioException(EZIOEX_WRITE);
	}
}

void ezio300::SaveState(void)
{
	struct ezioState *s = new struct ezioState;
	memcpy(s, GetState(), sizeof(struct ezioState));
	s->display = new char[cpl * height];
	memcpy(s->display, GetState()->display, cpl * height);
	state.push(s);
}

void ezio300::RestoreState(void)
{
	struct ezioState *s = state.top();
	if (s == NULL) throw ezioException(EZIOEX_STATE_RESTORE);
	delete [] s->display;
	delete s;
	state.pop();

	s = state.top();
	MoveCursor(s->cx, s->cy);
	if (s->ct == EZIO_CT_NONE) Write(EZIO_HIDE);
	else if (s->ct == EZIO_CT_BLOCK)
		Write(EZIO_SHOW_BLOCK);
	else
		Write(EZIO_SHOW_USCORE);
}

// vi: ts=4
