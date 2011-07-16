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

#ifdef _HAVE_CONFIG_H
#include "config.h"
#endif

#include <string>
#include <stdexcept>

#include <unistd.h>
#include <string.h>
#include <pthread.h>
#include <errno.h>

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

long csGetPageSize(void)
{
    // TODO: ...
    return getpagesize();
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
