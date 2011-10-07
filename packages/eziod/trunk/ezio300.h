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

// EZIO-300: 16x2 character LCD module with 4 buttons
// http://www.portwell.com/products/detail.asp?CUSTCHAR1=EZIO-300

#ifndef _EZIO300_H
#define _EZIO300_H

using namespace std;

// EZIO-300 LCD width
#define EZIO300_WIDTH			16
// EZIO-300 LCD height
#define EZIO300_HEIGHT			2
// EZIO-300 LCD characters-per-line
#define EZIO300_CPL				40
// EZIO-300 input buffer size
#define EZIO300_INPUT_SIZE		255

// EZIO display state
struct ezioState
{
	char *display;
	uint8_t cx;
	uint8_t cy;
	ezioCursorType ct;
};

// EZIO-300 icon data
struct ezioIconData
{
	uint8_t width;
	uint8_t height;
	uint8_t length;
	uint8_t *data;
};

// EZIO-300 device class
class ezio300
{
public:
	ezio300(const char *device,
		struct ezioIconData *icon_data = NULL) throw(ezioException);
	~ezio300();

	ezioButton ReadKey(uint32_t timeout = 0) throw(ezioException);
	ezioButton ReadInteger(int32_t &value,
		int32_t min_value = 0, int32_t max_value = 255,
		int32_t width = 3) throw(ezioException);
	ezioButton ReadIpAddress(const char *prompt, char ip[16]) throw(ezioException);
	int32_t ReadChoice(const char *prompt, map<int32_t, const char *> &choices) throw(ezioException);

	void Write(ezioCommand command, uint8_t param = 0) throw(ezioException);
	void WriteText(const char *text) throw(ezioException);
	void WriteText(uint8_t x, uint8_t y, const char *text) throw(ezioException);

	void MoveCursor(uint8_t x = 0, uint8_t y = 0) throw(ezioException);
	void ClearEOL(void) throw(ezioException);

	void LoadIcon(ezioIcon icon, const uint8_t *data, uint8_t icon_height);

	void SaveState(void);
	void RestoreState(void);

protected:
	int fd_ezio;
	string device;
	uint8_t width;
	uint8_t height;
	uint8_t cpl;
	uint8_t input[EZIO300_INPUT_SIZE];
	stack<struct ezioState *> state;

	struct ezioState *GetState(void) { return state.top(); };
};

#endif // _EZIO300_H
// vi: ts=4
