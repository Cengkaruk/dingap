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

#ifndef _EZIO_H
#define _EZIO_H

using namespace std;

// EZIO command/response prefix
enum ezioPrefix {
	EZIO_INPUT = 0xfd,
	EZIO_OUTPUT = 0xfe,
};

// EZIO commands
enum ezioCommand {
	// Text
	EZIO_TEXT = 0x00,
	// Clear screen
	EZIO_CLEAR = 0x01,
	// Home cursor
	EZIO_HOME = 0x02,
	// Read button
	EZIO_READ = 0x06,
	// Blank display
	EZIO_BLANK = 0x08,
	// Hide cursor, display blanked characters
	EZIO_HIDE = 0x0c,
	// Show blinking block cursor
	EZIO_SHOW_BLOCK = 0x0d,
	// Show underline cursor
	EZIO_SHOW_USCORE = 0x0e,
	// Move cursor left
	EZIO_MOVE_LEFT = 0x10,
	// Move cursor right
	EZIO_MOVE_RIGHT = 0x14,
	// Scroll cursor left
	EZIO_SCROLL_LEFT = 0x18,
	// Scroll cursor right
	EZIO_SCROLL_RIGHT = 0x1c,
	// Initialize device
	EZIO_INIT = 0x28,
	// Stop sending data
	EZIO_STOP = 0x37,
	// Set cursor address; bottom row
	EZIO_MOVE_ROW1 = 0x40,
	// Set cursor address; top row
	EZIO_MOVE_ROW0 = 0x80,
};

// EZIO buttons
enum ezioButton {
	EZIO_BUTTON_ALT0 = 0xb0,
	EZIO_BUTTON_ALT1 = 0xb1,
	EZIO_BUTTON_ALT2 = 0xb2,
	EZIO_BUTTON_ALT3 = 0xb3,
	EZIO_BUTTON_ALT4 = 0xb4,
	EZIO_BUTTON_ALT5 = 0xb5,
	EZIO_BUTTON_ALT6 = 0xb6,
	EZIO_BUTTON_ESC = 0xb7,
	EZIO_BUTTON_ALT8 = 0xb8,
	EZIO_BUTTON_ALT9 = 0xb9,
	EZIO_BUTTON_ALTA = 0xba,
	EZIO_BUTTON_ENTER = 0xbb,
	EZIO_BUTTON_ALTC = 0xbc,
	EZIO_BUTTON_DOWN = 0xbd,
	EZIO_BUTTON_UP = 0xbe,
	EZIO_BUTTON_NONE = 0xbf,
};

// EZIO icons
enum ezioIcon {
	EZIO_ICON0,
	EZIO_ICON1,
	EZIO_ICON2,
	EZIO_ICON3,
	EZIO_ICON4,
	EZIO_ICON5,
	EZIO_ICON6,
	EZIO_ICON7,
};

// EZIO icon bank addresses
enum ezioIconBank {
	EZIO_ICON_BANK0 = 0x40,
	EZIO_ICON_BANK1 = 0x48,
	EZIO_ICON_BANK2 = 0x50,
	EZIO_ICON_BANK3 = 0x58,
	EZIO_ICON_BANK4 = 0x60,
	EZIO_ICON_BANK5 = 0x68,
	EZIO_ICON_BANK6 = 0x70,
	EZIO_ICON_BANK7 = 0x78,
};

// EZIO cursor types
enum ezioCursorType {
	EZIO_CT_NONE,
	EZIO_CT_USCORE,
	EZIO_CT_BLOCK,
};

#endif // _EZIO_H
// vi: ts=4
