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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <string>
#include <stdexcept>

#include <unistd.h>
#include <stdio.h>
#include <string.h>
#include <pthread.h>
#include <errno.h>
#include <regex.h>

#include <clearsync/csexception.h>
#include <clearsync/csutil.h>

csCriticalSection *csCriticalSection::instance = NULL;
pthread_mutex_t *csCriticalSection::mutex = NULL;

csCriticalSection::csCriticalSection()
{
    if (instance != NULL)
        throw csException(EEXIST, "csCriticalSection");
    mutex = new pthread_mutex_t;
    pthread_mutex_init(mutex, NULL);
    instance = this;
}

csCriticalSection::~csCriticalSection()
{
    if (instance != this) return;
    pthread_mutex_destroy(mutex);
    delete mutex;
    mutex = NULL;
}

void csCriticalSection::Lock(void)
{
    if (instance && mutex)
        pthread_mutex_lock(mutex);
}

void csCriticalSection::Unlock(void)
{
    if (instance && mutex)
        pthread_mutex_unlock(mutex);
}

csRegEx::csRegEx(const char *expr, int nmatch, int flags)
	: match(NULL), nmatch(nmatch), matches(NULL)
{
	if (!nmatch) flags |= REG_NOSUB;
	if (regcomp(&regex, expr, flags) != 0)
		throw csException("Regular expression compilation error");
	if (nmatch) {
		match = new regmatch_t[nmatch];
		matches = new char *[nmatch];
		for (int i = 0; i < nmatch; i++) matches[i] = NULL;
	}
}

csRegEx::~csRegEx()
{
	regfree(&regex);
	if (nmatch && match) delete [] match;
	for (int i = 0; i < nmatch; i++) {
		if (matches[i]) delete [] matches[i];
	}
	if (matches) delete [] matches;
}

int csRegEx::Execute(const char *subject)
{
	if (!subject)
		throw csException("Invalid regular expression subject");
	int rc = regexec(&regex, subject, nmatch, match, 0);
	for (int i = 0; i < nmatch; i++) {
		if (matches[i]) delete [] matches[i];
		matches[i] = NULL;
	}
	if (rc == 0) {
		for (int i = 0; i < nmatch; i++) {
			int len = match[i].rm_eo - match[i].rm_so;
			char *buffer = new char[len + 1];
			memset(buffer, 0, len + 1);
			memcpy(buffer, subject + match[i].rm_so, len);
			matches[i] = buffer;
		}
	}
	return rc;
}

const char *csRegEx::GetMatch(int match)
{
	if (match < 0 || match >= nmatch)
		throw csException("Invalid regular expression match offset");
	if (this->match[match].rm_so == -1) return NULL;
	return matches[match];
}

long csGetPageSize(void)
{
#ifdef HAVE_GETPAGESIZE
    // TODO: sysconf
    return getpagesize();
#else
    return 4096;
#endif
}

int csExecute(const string &command)
{
    FILE *ph = popen(command.c_str(), "r");
    if (ph == NULL) return errno;
    char buffer[::csGetPageSize()];
    while (!feof(ph))
        fgets(buffer, ::csGetPageSize(), ph);
    return pclose(ph);
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
