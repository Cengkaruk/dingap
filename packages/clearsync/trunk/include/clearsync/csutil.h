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
    csRegEx(const char *expr, size_t nmatch = 0, int flags = REG_EXTENDED);
    virtual ~csRegEx();

    int Execute(const char *subject);
    const char *GetMatch(size_t match);

protected:
    regex_t regex;
    regmatch_t *match;
    size_t nmatch;
    char **matches;
};

long csGetPageSize(void);

int csExecute(const string &command);
int csExecute(const string &command, vector<string> &output);

void csHexDump(FILE *fh, const void *data, uint32_t length);

uid_t csGetUserId(const string &user);
gid_t csGetGroupId(const string &group);

void csSHA1(const string &filename, uint8_t *digest);

#endif // _CSUTIL_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
