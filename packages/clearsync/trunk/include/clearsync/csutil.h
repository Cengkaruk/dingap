// ClearSync: system synchronization daemon.
// Copyright (C) 2011 ClearFoundation <http://www.clearfoundation.com>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

#ifndef _CSUTIL_H
#define _CSUTIL_H

class csCriticalSection
{
public:
    csCriticalSection();
    virtual ~csCriticalSection();

    static void Lock(void);
    static void Unlock(void);

protected:
    static csCriticalSection *instance;
    static pthread_mutex_t *mutex;
};

class csRegEx
{
public:
	csRegEx(const char *expr, int nmatch = 0, int flags = REG_EXTENDED);
	virtual ~csRegEx();

	int Execute(const char *subject);
	const char *GetMatch(int match);

protected:
	regex_t regex;
	size_t nmatch;
	regmatch_t *match;
	char **matches;
};

long csGetPageSize(void);
int csExecute(const string &command);

class csSHA1
{
public:
    csSHA1();
    virtual ~csSHA1();

    void Reset(void);
    bool Result(unsigned *message_digest_array);

    void Input(const unsigned char *message_array, unsigned length);
    void Input(const char *message_array, unsigned length);
    void Input(unsigned char message_element);
    void Input(char message_element);
    csSHA1& operator<<(const char *message_array);
    csSHA1& operator<<(const unsigned char *message_array);
    csSHA1& operator<<(const char message_element);
    csSHA1& operator<<(const unsigned char message_element);

protected:
    void ProcessMessageBlock(void);
    void PadMessage(void);
    inline unsigned CircularShift(int bits, unsigned word);

    unsigned H[5];
    unsigned length_low;
    unsigned length_high;
    unsigned char message_block[64];
    int message_block_index;
    bool computed;
    bool corrupted;
};

#endif // _CSUTIL_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
