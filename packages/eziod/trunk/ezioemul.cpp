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

#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <stdint.h>
#include <termios.h>
#include <pty.h>
#include <fcntl.h>
#include <errno.h>
#include <string.h>
#include <ctype.h>

#include <string>
#include <map>

#include <SDL.h>

#include "ezio.h"
#include "ezioex.h"
#include "ezioemul.h"
#include "font5x8.cpp"
#include "font6x12.cpp"

extern int errno;

#if SDL_BYTEORDER == SDL_BIG_ENDIAN
static const Uint32 rmask = 0xff000000;
static const Uint32 gmask = 0x00ff0000;
static const Uint32 bmask = 0x0000ff00;
static const Uint32 amask = 0x000000ff;
#else
static const Uint32 rmask = 0x000000ff;
static const Uint32 gmask = 0x0000ff00;
static const Uint32 bmask = 0x00ff0000;
static const Uint32 amask = 0xff000000;
#endif

static void ezioPostInput(ezioCommand cmd, Uint32 length, Uint8 *data, Uint32 param)
{
	SDL_Event event;
	struct ezioEventInput *input = new struct ezioEventInput;

	input->cmd = cmd;
	input->length = length;
	input->data = data;
	input->param = param;

	event.type = SDL_USEREVENT;
	event.user.code = EZIO_EVENT_INPUT;
	event.user.data1 = (void *)input;
	event.user.data2 = NULL;

	SDL_PushEvent(&event);
}

static Uint32 ezioPostBlink(Uint32 interval, void *param)
{
	SDL_Event event;

	event.type = SDL_USEREVENT;
	event.user.code = EZIO_EVENT_BLINK;
	event.user.data1 = event.user.data2 = NULL;

	SDL_PushEvent(&event);

	return interval;
}

static void ezioPostError(const char *prefix, const char *error)
{
	SDL_Event event;

	event.type = SDL_USEREVENT;
	event.user.code = EZIO_EVENT_ERROR;
	event.user.data1 = strdup(prefix);
	event.user.data2 = strdup(error);

	SDL_PushEvent(&event);
}

static void ezioParseInput(ezioEmulator *emulator, string &data)
{
	int fd;

	if (data.length() >= 4) {
		if ((Uint8)data[0] == EZIO_OUTPUT && (Uint8)data[1] == EZIO_INIT &&
			(Uint8)data[2] == EZIO_OUTPUT && (Uint8)data[3] == EZIO_INIT) {
			emulator->SetDeviceInit(false);
			ezioPostInput(EZIO_INIT, 0, NULL, 0);
			data.erase(0, 4);
		}
	}
	if (data.length() >= 3 && !emulator->GetDeviceInit() &&
		(Uint8)data[0] == EZIO_OUTPUT &&
		(Uint8)data[1] >= EZIO_ICON_BANK0 &&
		(Uint8)data[1] <= (EZIO_ICON_BANK7 +
			emulator->GetDevice()->GetCharacterHeight()) &&
		(Uint8)data[2] != EZIO_OUTPUT) {
		data.erase(0, 3);
	}

	if (!emulator->GetDeviceInit()) {
		if (data.length() >= 2 && (Uint8)data[0] == EZIO_OUTPUT &&
			(Uint8)data[1] == EZIO_CLEAR) {
			emulator->SetDeviceInit(true);
			ezioPostInput((ezioCommand)data[1], 0, NULL, 0);
			data.erase(0, 2);
		}
		return;
	}

	if (data.length() >= 2) {
		if ((Uint8)data[0] == EZIO_OUTPUT) {
			switch ((Uint8)data[1]) {
			case EZIO_HIDE:
			case EZIO_SHOW_BLOCK:
			case EZIO_SHOW_USCORE:
			case EZIO_BLANK:
			case EZIO_STOP:
			case EZIO_CLEAR:
			case EZIO_HOME:
			case EZIO_MOVE_LEFT:
			case EZIO_MOVE_RIGHT:
			case EZIO_SCROLL_LEFT:
			case EZIO_SCROLL_RIGHT:
				ezioPostInput((ezioCommand)data[1], 0, NULL, 0);
				data.erase(0, 2);
				break;
			case EZIO_READ:
				data.erase(0, 2);
				emulator->SendInput();
				break;
			}
		}
	}

	if (data.length() >= 2) {
		if ((Uint8)data[1] >= EZIO_MOVE_ROW0 &&
			(Uint8)data[1] <= EZIO_MOVE_ROW0 + 0xf) {
			ezioPostInput(EZIO_MOVE_ROW0, 0, NULL,
				((Uint8)data[1]) - EZIO_MOVE_ROW0);
			data.erase(0, 2);
		} else if ((Uint8)data[1] >= EZIO_MOVE_ROW1 &&
			(Uint8)data[1] <= EZIO_MOVE_ROW1 + 0xf) {
			ezioPostInput(EZIO_MOVE_ROW1, 0, NULL,
				((Uint8)data[1]) - EZIO_MOVE_ROW1);
			data.erase(0, 2);
		}
	}

	// Look for next EZIO_OUTPUT
	size_t len = 0;
	for ( ; len < data.length(); len++) {
		if ((Uint8)data[len] != EZIO_OUTPUT) continue;
		break;
	}

	if (!len) return;

	Uint8 *buffer = new Uint8[len];
	data.copy((char *)buffer, len);
	data.erase(0, len);

	ezioPostInput(EZIO_TEXT, len, buffer, 0);
}

static Sint32 ezioReadPort(void *param)
{
	ezioEmulator *emulator = (ezioEmulator *)param;

	fd_set fds;
	struct timeval tv;
	int r;
	int fd = emulator->GetPortDescriptor();
	Uint8 buffer[255];
	ssize_t len;
	string data;

	while(emulator->IsRunning()) {
		FD_ZERO(&fds);
		FD_SET(fd, &fds);

		tv.tv_sec = 0;
		tv.tv_usec = 10000;
		//tv.tv_usec = 250000;

		r = select(fd + 1, &fds, NULL, NULL, &tv);

		switch (r) {
		case -1:
			ezioPostError("select", strerror(errno));
			return -1;
		case 0:
			if (data.length()) ezioParseInput(emulator, data);
			break;
		default:
			len = read(fd, buffer, sizeof(buffer));

			if (len < 0) {
				ezioPostError("read", strerror(errno));
				return -1;
			}
			else if (len > 0) {
				data.append((const char *)buffer, len);
				ezioParseInput(emulator, data);
			}
		}
	}

	return 0;
}

ezioFont::ezioFont(Uint8 width, Uint8 height,
	Uint8 length, const Uint8 *data)
	: width(width), height(height), length(length), data(data),
	bg(NULL), pixel_on(NULL), pixel_off(NULL)
{
	if (!width || !height || !length || !data)
		throw ezioException(EZIOEX_EMUL_FONT_INIT);
}

ezioFont::~ezioFont()
{
	for (map<Uint8, SDL_Surface *>::iterator i = keymap.begin();
		i != keymap.end(); i++) SDL_FreeSurface(i->second);
}

SDL_Surface *ezioFont::GetGlyph(Uint8 glyph)
{
	map<Uint8, SDL_Surface *>::iterator i = keymap.find(glyph);
	if (i == keymap.end()) return NULL;
	return i->second;
}

void ezioFont::SetGlyph(Uint8 glyph, const Uint8 *data)
{
	if (!data || !length || !bg || !pixel_on || !pixel_off)
		throw ezioException(EZIOEX_EMUL_FONT_SET_GLYPH);
	SDL_Surface *surface = GetGlyph(glyph);
	if (surface != NULL) {
		SDL_FreeSurface(surface);
		keymap[glyph] = NULL;
	}

	surface = SDL_CreateRGBSurface(SDL_SWSURFACE,
		bg->w, bg->h, 32, rmask, gmask, bmask, amask);
	SDL_SetAlpha(surface, 0, 0);
	SDL_BlitSurface(bg, NULL, surface, NULL);

	SDL_Rect rect;
	rect.x = rect.y = 0;
	rect.w = pixel_on->w;
	rect.h = pixel_on->h;

	for (Sint32 y = 0; y < height; y++) {
		rect.x = 0;
		rect.w = pixel_on->w;
		rect.h = pixel_on->h;
		Uint8 bits = (Uint32)data[y];
		for (Sint32 x = 0; x < width; x++) {
			if ((bits & 0x80 ) != 0)
				SDL_BlitSurface(pixel_on, NULL, surface, &rect);
			bits = bits << 1;
			rect.x += pixel_on->w + 1;
		}
		rect.y += pixel_on->h + 1;
	}

	keymap[glyph] = surface;
}

void ezioFont::Render(SDL_Surface *bg,
	SDL_Surface *pixel_on, SDL_Surface *pixel_off)
{
	if (!data || !length || !bg || !pixel_on || !pixel_off)
		throw ezioException(EZIOEX_EMUL_FONT_RENDER);

	this->bg = bg;
	this->pixel_on = pixel_on;
	this->pixel_off = pixel_off;

	Uint32 p = 0, c = 0;
	for (Uint8 i = 0; i < 255 && c < length; i++) {
		if (c < length && data[p] == i) {
			SetGlyph(i, data + p + 1);
			p += height + 1; c++;
		}
	}
}

ezioDevice::ezioDevice()
	: model(EZIO_INVALID), baud_rate(0), lcd_width(0), lcd_height(0),
	chr_width(0), chr_height(0), cpl(0), font(NULL)
{
	button_mutex = SDL_CreateMutex();
}

ezioDevice::~ezioDevice()
{
	if (font) delete font;
	if (button_mutex) SDL_DestroyMutex(button_mutex);
}

ezioDevice300::ezioDevice300()
	: ezioDevice()
{
	name = "EZIO-300";
	model = EZIO_300;
	baud_rate = 2400;
	lcd_width = 16;
	lcd_height = 2;
	chr_width = 5;
	chr_height = 8;
	//chr_width = 6;
	//chr_height = 12;
	cpl = 40;
	button[EZIO_BUTTON_UP] = false;
	button[EZIO_BUTTON_DOWN] = false;
	button[EZIO_BUTTON_ENTER] = false;
	button[EZIO_BUTTON_ESC] = false;
	font = new ezioFont(chr_width, chr_height,
		font_5x8_length, font_5x8);
		//font_6x12_length, font_6x12);
}

void ezioDevice300::CreatePort(int &fd_master, int &fd_slave)
{
#if 0
	struct termios tio;
	switch (baud_rate) {
	case 2400:
		cfsetospeed(&tio, B2400);
		cfsetispeed(&tio, B2400);
		break;
	default:
		throw ezioException(EZIOEX_INVALID_BAUD);
	}

	// Set input modes 
	tio.c_iflag &= ~(IGNBRK | BRKINT | PARMRK | INPCK | ISTRIP |
		INLCR | IGNCR | ICRNL | IUCLC | IXON | IXANY | IXOFF |
		IMAXBEL);
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

	if (openpty(&fd_master, &fd_slave, tty, &tio, NULL) < 0)
#endif
	char tty[1024];

	if (openpty(&fd_master, &fd_slave, tty, NULL, NULL) < 0)
		throw ezioException(EZIOEX_EMUL_OPENPTY);

	printf("%s: created port: %s\n", name.c_str(), tty);
}

ezioEmulator::ezioEmulator(ezioModel model)
	: view(NULL), device(NULL), lcd_panel(NULL),
	pixel_on(NULL), pixel_off(NULL), chr_on(NULL), chr_off(NULL),
	chr_uscore(NULL), fd_master(-1), fd_slave(-1), px_size(4),
	px_chr_width(0), px_chr_height(0), px_chr_space(2),
	px_lcd_width(0), px_lcd_height(0),
	terminate(false), cp(0), sd(0), blink(false),
	cvis(false), ct(EZIO_CT_BLOCK), device_init(false)
{
	switch (model) {
	case EZIO_300:
		device = new ezioDevice300;
		display_size =
			device->GetCharactersPerLine() * device->GetPanelHeight();
		// Allocate display buffer
		display = new Uint8[display_size];
		memset(display, ' ', display_size);
		display[0] = '*';
		display[1] = '*';
		break;
	default:
		throw ezioException(EZIOEX_EMUL_INVALID_MODEL);
	}

	// Create psuedo serial port
	device->CreatePort(fd_master, fd_slave);

	// Calculate some panel/character dimentions
	px_chr_width = (device->GetCharacterWidth() * (px_size / 2)) +
		(device->GetCharacterWidth() - 1);
	px_chr_height = (device->GetCharacterHeight() * (px_size / 2)) +
		(device->GetCharacterHeight() - 1);

	px_lcd_width = px_chr_width * device->GetPanelWidth() +
		((device->GetPanelWidth() - 1) * px_chr_space);
	px_lcd_height = px_chr_height * device->GetPanelHeight() +
		((device->GetPanelHeight() - 1) * px_chr_space);

	// Initialize SDL
	if (SDL_Init(SDL_INIT_VIDEO | SDL_INIT_TIMER |
		SDL_INIT_EVENTTHREAD) < 0)
		throw ezioException(EZIOEX_SDL_INIT);

	// Create view
	if (!(view = SDL_SetVideoMode(px_lcd_width, px_lcd_height,
		0, 0))) throw ezioException(EZIOEX_SDL_CREATE_VIEW);

	// Set caption
	SDL_WM_SetCaption("EZIOemul v" PACKAGE_VERSION, "EZIOemul");

	// Start blink timer
	cbt = 400;
	SDL_AddTimer(cbt, ezioPostBlink, (void *)this);
}

ezioEmulator::~ezioEmulator()
{
	if (fd_master > -1) close(fd_master);
	if (fd_slave > -1) close(fd_slave);
	if (device) delete device;
	if (lcd_panel) SDL_FreeSurface(lcd_panel);
	if (pixel_on) SDL_FreeSurface(pixel_on);
	if (pixel_off) SDL_FreeSurface(pixel_off);
	if (chr_on) SDL_FreeSurface(chr_on);
	if (chr_off) SDL_FreeSurface(chr_off);
	if (chr_uscore) SDL_FreeSurface(chr_uscore);
	if (display) delete [] display;
	SDL_Quit();
}

void ezioEmulator::Run(void)
{
	CreateSurfaces();

	SDL_Thread *thread_input = SDL_CreateThread(ezioReadPort,
		(void *)this);

	SDL_Event event;
	struct ezioEventInput *input;

	while (!terminate && SDL_WaitEvent(&event) > 0) {
		if (event.type == SDL_QUIT || (event.type == SDL_KEYDOWN &&
			event.key.keysym.sym == SDLK_q)) break;
		else if (event.type == SDL_KEYUP) {
			switch (event.key.keysym.sym) {
			case SDLK_UP:
				device->OnButtonUp(EZIO_BUTTON_UP);
				break;
			case SDLK_DOWN:
				device->OnButtonUp(EZIO_BUTTON_DOWN);
				break;
			case SDLK_RETURN:
				device->OnButtonUp(EZIO_BUTTON_ENTER);
				break;
			case SDLK_ESCAPE:
				device->OnButtonUp(EZIO_BUTTON_ESC);
				break;
			}
		}
		else if (event.type == SDL_KEYDOWN) {
			switch (event.key.keysym.sym) {
			case SDLK_UP:
				device->OnButtonDown(EZIO_BUTTON_UP);
				break;
			case SDLK_DOWN:
				device->OnButtonDown(EZIO_BUTTON_DOWN);
				break;
			case SDLK_RETURN:
				device->OnButtonDown(EZIO_BUTTON_ENTER);
				break;
			case SDLK_ESCAPE:
				device->OnButtonDown(EZIO_BUTTON_ESC);
				break;
			}
		}
		else if (event.type == SDL_USEREVENT) {
			switch (event.user.code) {
			case EZIO_EVENT_INPUT:
				input = (struct ezioEventInput *)event.user.data1;
				OnEventInput(input);
				if (input->length && input->data) delete [] input->data;
				delete input;
				break;
			case EZIO_EVENT_BLINK:
				OnEventBlink();
				break;
			case EZIO_EVENT_ERROR:
				terminate = true;
				printf("%s: %s\n",
					(const char *)event.user.data1,
					(const char *)event.user.data2);
				free(event.user.data1);
				free(event.user.data2);
				break;
			default:
				throw ezioException(EZIOEX_EMUL_UNHANDLED_EVENT);
			}
		}
	}

	terminate = true;
	Sint32 thread_result;
	SDL_WaitThread(thread_input, &thread_result);
}

void ezioEmulator::SendInput(void)
{
	Uint8 buffer[2];
	buffer[0] = EZIO_INPUT; 
	buffer[1] = device->GetButton();
	if (write(fd_master, buffer,
		sizeof(buffer)) != sizeof(buffer))
		throw ezioException(EZIOEX_WRITE);
}

void ezioEmulator::CreateSurfaces(void)
{
	// Define the colours we'll use
	bg.r = 73; bg.g = 84; bg.b = 149;
	bg_chr.r = 84; bg_chr.g = 97; bg_chr.b = 172;
	fg_chr.r = 250; fg_chr.g = 250; fg_chr.b = 255;

	// Create panel, pixel, and character surfaces
	lcd_panel = SDL_CreateRGBSurface(SDL_SWSURFACE,
		px_lcd_width, px_lcd_height, 32, rmask, gmask, bmask, amask);
	pixel_on = SDL_CreateRGBSurface(SDL_SWSURFACE,
		px_size / 2, px_size / 2, 32, rmask, gmask, bmask, amask);
	pixel_off = SDL_CreateRGBSurface(SDL_SWSURFACE,
		px_size / 2, px_size / 2, 32, rmask, gmask, bmask, amask);
	chr_on = SDL_CreateRGBSurface(SDL_SWSURFACE,
		px_chr_width, px_chr_height, 32, rmask, gmask, bmask, amask);
	chr_off = SDL_CreateRGBSurface(SDL_SWSURFACE,
		px_chr_width, px_chr_height, 32, rmask, gmask, bmask, amask);
	chr_uscore = SDL_CreateRGBSurface(SDL_SWSURFACE,
		px_chr_width, px_size / 2, 32, rmask, gmask, bmask, amask);

	SDL_SetAlpha(lcd_panel, 0, 0);
	SDL_SetAlpha(pixel_on, 0, 0);
	SDL_SetAlpha(pixel_off, 0, 0);
	SDL_SetAlpha(chr_on, 0, 0);
	SDL_SetAlpha(chr_off, 0, 0);
	SDL_SetAlpha(chr_uscore, 0, 0);

	SDL_FillRect(lcd_panel, NULL,
		SDL_MapRGB(lcd_panel->format, bg.r, bg.g, bg.b));
	SDL_FillRect(pixel_on, NULL,
		SDL_MapRGB(pixel_on->format, fg_chr.r, fg_chr.g, fg_chr.b));
	SDL_FillRect(pixel_off, NULL,
		SDL_MapRGB(pixel_off->format, bg_chr.r, bg_chr.g, bg_chr.b));
	SDL_FillRect(chr_on, NULL,
		SDL_MapRGB(chr_on->format, bg.r, bg.g, bg.b));
	SDL_FillRect(chr_off, NULL,
		SDL_MapRGB(chr_off->format, bg.r, bg.g, bg.b));
	SDL_FillRect(chr_uscore, NULL,
		SDL_MapRGB(chr_uscore->format, bg.r, bg.g, bg.b));

	// Create on/off pixel surfaces
	SDL_Rect rect;

	for (rect.y = 0;
		rect.y < px_chr_height; rect.y += (px_size / 2 + 1)) {
		for (rect.x = 0;
			rect.x < px_chr_width; rect.x += (px_size / 2 + 1)) {
			rect.w = rect.h = (px_size / 2);
			SDL_BlitSurface(pixel_on, NULL, chr_on, &rect);
		}
	}
	for (rect.y = 0;
		rect.y < px_chr_height; rect.y += (px_size / 2 + 1)) {
		for (rect.x = 0;
			rect.x < px_chr_width; rect.x += (px_size / 2 + 1)) {
			rect.w = rect.h = (px_size / 2);
			SDL_BlitSurface(pixel_off, NULL, chr_off, &rect);
		}
	}

	// Create underscore
	rect.y = 0;
	for (rect.x = 0;
		rect.x < px_chr_width; rect.x += (px_size / 2 + 1)) {
		rect.w = rect.h = (px_size / 2);
		SDL_BlitSurface(pixel_on, NULL, chr_uscore, &rect);
	}

	// Create font glyph surfaces
	device->GetFont()->Render(chr_off, pixel_on, pixel_off);

	// Create unknown character
	// TODO: throw: fatal if not found
	chr_unknown = device->GetFont()->GetGlyph('?');

	// Clear display
	UpdateDisplay(0);
}

void ezioEmulator::UpdateDisplay(Uint32 delay)
{
	SDL_Rect rect;

	Uint32 chr = 0;
	if (sd > 0) chr = display_size - sd;
	else if (sd < 0) chr = abs(sd);

	for (rect.y = 0;
		rect.y < px_lcd_height; rect.y += (px_chr_height + 1)) {
		for (rect.x = 0;
			rect.x < px_lcd_width; rect.x += (px_chr_width + 1)) {
			SDL_Surface *glyph = device->GetFont()->GetGlyph(display[chr]);
			if (!glyph) glyph = chr_unknown;
			rect.w = px_chr_width;
			rect.h = px_chr_height;
			SDL_BlitSurface(glyph, NULL, lcd_panel, &rect);
			if (cvis && blink && cp == chr) {
				if (ct == EZIO_CT_BLOCK)
					SDL_BlitSurface(chr_on, NULL, lcd_panel, &rect);
				else if (ct == EZIO_CT_USCORE) {
					SDL_Rect uscore;
					uscore.x = rect.x;
					uscore.y = px_chr_height - chr_uscore->h;
					uscore.w = chr_uscore->w;
					uscore.h = chr_uscore->h;
					SDL_BlitSurface(chr_uscore, NULL, lcd_panel, &uscore);
				}
			}
			if (delay) {
				SDL_BlitSurface(lcd_panel, NULL, view, NULL);
				SDL_UpdateRect(view, 0, 0, 0, 0);
				SDL_Delay(delay);
			}
			rect.x += (px_chr_space - 1);
			if (++chr == display_size) chr = 0;
		}
		rect.y += (px_chr_space - 1);
		chr += device->GetCharactersPerLine() - device->GetPanelWidth();
		if (chr >= display_size) chr = chr - display_size;
	}

	if (!delay) {
		SDL_BlitSurface(lcd_panel, NULL, view, NULL);
		SDL_UpdateRect(view, 0, 0, 0, 0);
	}
}

void ezioEmulator::ClearDisplay(void)
{
	SDL_Rect rect;
	Uint8 *chr = display;
	memset(display, ' ',
		device->GetCharactersPerLine() * device->GetPanelHeight());

	for (rect.y = 0;
		rect.y < px_lcd_height; rect.y += (px_chr_height + 1)) {
		for (rect.x = 0;
			rect.x < px_lcd_width; rect.x += (px_chr_width + 1)) {
			rect.w = px_chr_width;
			rect.h = px_chr_height;
			SDL_BlitSurface(chr_off, NULL, lcd_panel, &rect);
			rect.x += (px_chr_space - 1);
		}
		rect.y += (px_chr_space - 1);
	}

	SDL_BlitSurface(lcd_panel, NULL, view, NULL);
	SDL_UpdateRect(view, 0, 0, 0, 0);
}

void ezioEmulator::OnEventInput(struct ezioEventInput *input)
{
	char *text;
	switch (input->cmd) {
	case EZIO_TEXT:
		text = new char[input->length + 1];
		memset(text, 0, input->length + 1);
		memcpy(text, (char *)input->data, input->length);
		printf("text len %d: \"%s\"\n", input->length, text);
		delete [] text;

		memcpy(display, input->data,
			(input->length > display_size) ? display_size : input->length);
		UpdateDisplay();
		break;
	case EZIO_CLEAR:
		printf("clear\n");
		ClearDisplay();
		break;
	case EZIO_HOME:
		printf("home\n");
		cp = 0;
		UpdateDisplay(0);
		break;
	case EZIO_BLANK:
		printf("blank\n");
		cvis = false;
		break;
	case EZIO_HIDE:
		printf("hide\n");
		cvis = false;
		UpdateDisplay(0);
		break;
	case EZIO_SHOW_BLOCK:
		printf("show block\n");
		cvis = true;
		ct = EZIO_CT_BLOCK;
		UpdateDisplay(0);
		break;
	case EZIO_SHOW_USCORE:
		printf("show underscore\n");
		cvis = true;
		ct = EZIO_CT_USCORE;
		UpdateDisplay(0);
		break;
	case EZIO_MOVE_LEFT:
		printf("move cursor left\n");
		if (cp > 0) cp--;
		if (cvis) UpdateDisplay(0);
		break;
	case EZIO_MOVE_RIGHT:
		printf("move cursor right\n");
		if (cp < device->GetCharactersPerLine() - 1) cp++;
		if (cvis)
		if (cvis) UpdateDisplay(0);
		break;
	case EZIO_SCROLL_LEFT:
		sd--;
		printf("scroll delta: %d\n", sd);
		UpdateDisplay(0);
		break;
	case EZIO_SCROLL_RIGHT:
		sd++;
		printf("scroll delta: %d\n", sd);
		UpdateDisplay(0);
		break;
	case EZIO_INIT:
		printf("init\n");
		cvis = false; cp = sd = 0;
		ClearDisplay();
		break;
	case EZIO_STOP:
		printf("stop\n");
		break;
	case EZIO_MOVE_ROW1:
		cp = device->GetCharactersPerLine() + input->param;
		printf("move row1: %u, cp: %d\n", input->param, cp);
		if (cvis) UpdateDisplay(0);
		break;
	case EZIO_MOVE_ROW0:
		cp = input->param;
		printf("move row0: %u, cp: %d\n", input->param, cp);
		if (cvis) UpdateDisplay(0);
		break;
	default:
		printf("unhandled cmd: %02x, length: %d\n",
			(Uint8)input->cmd, input->length);
		throw ezioException(EZIOEX_EMUL_UNHANDLED_CMD);
	}
}

void ezioEmulator::OnEventBlink(void)
{
	blink = (blink) ? false : true;
	if (cvis) UpdateDisplay(0);
}

int main(int argc, char *argv[])
{
	ezioEmulator emulator(EZIO_300);
	emulator.Run();

	return 0;
}

// vi: ts=4
