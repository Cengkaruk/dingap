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

#ifndef _EZIOEMUL_H
#define _EZIOEMUL_H

using namespace std;

// EZIO font class
class ezioFont {
public:
	ezioFont(Uint8 width, Uint8 height,
		Uint8 length, const Uint8 *data);
	~ezioFont();

	void Render(SDL_Surface *bg,
		SDL_Surface *pixel_on, SDL_Surface *pixel_off);

	SDL_Surface *GetGlyph(Uint8 glyph);
	void SetGlyph(Uint8 glyph, const Uint8 *data);

protected:
	Uint8 width, height, length;
	const Uint8 *data;
	SDL_Surface *bg, *pixel_on, *pixel_off;
	map<Uint8, SDL_Surface *> keymap;
};

// EZIO supported models
enum ezioModel {
	EZIO_INVALID = 0,
	EZIO_300 = 300,
};

// EZIO device configuration base class
class ezioDevice {
public:
	ezioDevice();
	~ezioDevice();

	ezioModel GetModel(void) { return model; };
	Sint32 GetPanelWidth(void) { return lcd_width; };
	Sint32 GetPanelHeight(void) { return lcd_height; };
	Sint32 GetCharacterWidth(void) { return chr_width; };
	Sint32 GetCharacterHeight(void) { return chr_height; };
	Sint32 GetCharactersPerLine(void) { return cpl; };
	ezioFont *GetFont(void) { return font; };

	virtual void CreatePort(int &fd_master, int &fd_slave)
	{
		// TODO: throw...
	};

	void OnButtonUp(ezioButton button)
	{
		SDL_LockMutex(button_mutex);
		this->button[button] = false;
		SDL_UnlockMutex(button_mutex);
	};
	void OnButtonDown(ezioButton button)
	{
		SDL_LockMutex(button_mutex);
		this->button[button] = true;
		SDL_UnlockMutex(button_mutex);
	};
	ezioButton GetButton(void)
	{
		ezioButton b = EZIO_BUTTON_NONE;
		SDL_LockMutex(button_mutex);
		for (map<ezioButton, bool>::iterator i = button.begin();
			i != button.end(); i++) {
			if (i->second == false) continue;
			b = i->first;
			break;
		}
		if (b != EZIO_BUTTON_NONE)
			button[b] = false;
		SDL_UnlockMutex(button_mutex);
		return b;
	};

protected:
	string name;
	ezioModel model;
	Sint32 baud_rate;
	Sint32 lcd_width;
	Sint32 lcd_height;
	Sint32 chr_width;
	Sint32 chr_height;
	Sint32 cpl;
	ezioFont *font;
	SDL_mutex *button_mutex;
	map<ezioButton, bool> button;
};

// EZIO-300 configuration
class ezioDevice300 : public ezioDevice
{
public:
	ezioDevice300();

	void CreatePort(int &fd_master, int &fd_slave);
};

// EZIO emulator events
enum ezioEvent {
	EZIO_EVENT_ERROR,
	EZIO_EVENT_INPUT,
	EZIO_EVENT_BLINK,
};

struct ezioEventInput {
	ezioCommand cmd;
	size_t length;
	Uint8 *data;
	Uint32 param;
};

// EZIO emulator class
class ezioEmulator
{
public:
	ezioEmulator(ezioModel model);
	~ezioEmulator();

	void Run(void);
	bool IsRunning(void) { return !terminate; };
	int GetPortDescriptor(void) { return fd_master; };
	void SendInput(void);
	bool GetDeviceInit(void) { return device_init; };
	void SetDeviceInit(bool init) { device_init = init; };
	ezioDevice *GetDevice(void) { return device; };

protected:
	SDL_Surface *view;
	SDL_Surface *lcd_panel;
	SDL_Surface *pixel_on;
	SDL_Surface *pixel_off;
	SDL_Surface *chr_off;
	SDL_Surface *chr_on;
	SDL_Surface *chr_unknown;
	SDL_Surface *chr_uscore;
	SDL_Color bg;
	SDL_Color bg_chr;
	SDL_Color fg_chr;
	ezioDevice *device;
	int fd_master;
	int fd_slave;
	Sint32 px_size;
	Sint32 px_chr_width;
	Sint32 px_chr_height;
	Sint32 px_chr_space;
	Sint32 px_lcd_width;
	Sint32 px_lcd_height;
	bool terminate;
	Uint8 *display;
	size_t display_size;
	Sint32 cp;
	Sint32 sd;
	ezioCursorType ct;
	bool cvis;
	bool blink;
	Uint32 cbt;
	bool device_init;

	void CreateSurfaces(void);
	void UpdateDisplay(Uint32 delay = 5);
	void ClearDisplay(void);
	void OnEventInput(struct ezioEventInput *input);
	void OnEventBlink(void);
};

#endif // _EZIOEMUL_H
// vi: ts=4
