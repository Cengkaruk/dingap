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

#ifndef _EZIOEX_H
#define _EZIOEX_H

using namespace std;

// EZIO exceptions
enum ezioError {
	EZIOEX_DEVICE_OPEN,
	EZIOEX_TCGETATTR,
	EZIOEX_TCSETATTR,
	EZIOEX_READ,
	EZIOEX_WRITE,
	EZIOEX_INVALID_BAUD,
	EZIOEX_INVALID_COMMAND,
	EZIOEX_INVALID_ROW,
	EZIOEX_INVALID_COL,
	EZIOEX_INVALID_TEXT,
	EZIOEX_INVALID_INPUT_WIDTH,
	EZIOEX_INVALID_CHOICE_LEN,
	EZIOEX_INVALID_ICON,
	EZIOEX_INPUT_ERROR,
	EZIOEX_INPUT_LENGTH,
	EZIOEX_INPUT_INVALID,
	EZIOEX_INPUT_UNEXPECTED,
	EZIOEX_STATE_RESTORE,
#ifdef _EZIOEMUL
	EZIOEX_SDL_INIT,
	EZIOEX_SDL_CREATE_VIEW,
	EZIOEX_EMUL_FONT_INIT,
	EZIOEX_EMUL_FONT_SET_GLYPH,
	EZIOEX_EMUL_FONT_RENDER,
	EZIOEX_EMUL_OPENPTY,
	EZIOEX_EMUL_INVALID_MODEL,
	EZIOEX_EMUL_UNHANDLED_EVENT,
	EZIOEX_EMUL_UNHANDLED_CMD,
#endif
};

// EZIO exception class, strings
class ezioException : public exception
{
public:
	ezioException(ezioError eid) throw() : eid(eid) { };
	virtual const char *what() const throw()
	{
		switch (eid) {
		case EZIOEX_DEVICE_OPEN:
			return "Error opening EZIO device";
		case EZIOEX_TCGETATTR:
			return "Error while retrieving port settings";
		case EZIOEX_TCSETATTR:
			return "Error while configuring port settings";
		case EZIOEX_READ:
			return "Error while reading from device";
		case EZIOEX_WRITE:
			return "Error while writing to device";
		case EZIOEX_INVALID_BAUD:
			return "Invalid baud rate";
		case EZIOEX_INVALID_COMMAND:
			return "Invalid command";
		case EZIOEX_INVALID_ROW:
			return "Invalid cursor row specified";
		case EZIOEX_INVALID_COL:
			return "Invalid cursor column specified";
		case EZIOEX_INVALID_TEXT:
			return "Invalid text pointer specified";
		case EZIOEX_INVALID_INPUT_WIDTH:
			return "Invalid input width specified";
		case EZIOEX_INVALID_CHOICE_LEN:
			return "Invalid number of choices specified";
		case EZIOEX_INVALID_ICON:
			return "Invalid icon specified";
		case EZIOEX_INPUT_ERROR:
			return "Error reading button input";
		case EZIOEX_INPUT_LENGTH:
			return "Invalid button input length";
		case EZIOEX_INPUT_INVALID:
			return "Invalid button input data";
		case EZIOEX_INPUT_UNEXPECTED:
			return "Unexpected button input data";
		case EZIOEX_STATE_RESTORE:
			return "Error restoring state";
#ifdef _EZIOEMUL
		case EZIOEX_SDL_INIT:
			return "Error initializing SDL";
		case EZIOEX_SDL_CREATE_VIEW:
			return "Error creating SDL view";
		case EZIOEX_EMUL_FONT_INIT:
			return "Invalid font initialization";
		case EZIOEX_EMUL_FONT_SET_GLYPH:
			return "Invalid glyph initialization";
		case EZIOEX_EMUL_FONT_RENDER:
			return "Error rendering glyphs";
		case EZIOEX_EMUL_OPENPTY:
			return "Error creating PTY";
		case EZIOEX_EMUL_INVALID_MODEL:
			return "Invalid EZIO model";
		case EZIOEX_EMUL_UNHANDLED_EVENT:
			return "Unhandled event";
		case EZIOEX_EMUL_UNHANDLED_CMD:
			return "Unhandled command";
#endif
		default:
			return "Unknown exception occured";
		}
	};
	ezioError GetErrorId(void) { return eid; };

protected:
	ezioError eid;
};

#endif // _EZIOEX_H
// vi: ts=4
