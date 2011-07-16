// TODO: Program name/short-description
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
#include <vector>
#include <map>

#include <stdint.h>
#include <string.h>
#include <errno.h>
#include <pthread.h>
#include <expat.h>

#include <clearsync/csexception.h>
#include <clearsync/csconf.h>
#include <clearsync/cslog.h>
#include <clearsync/csevent.h>
#include <clearsync/csthread.h>

static void *cs_thread_entry(void *param)
{
    csThread *thread = reinterpret_cast<csThread *>(param);
    return thread->Entry();
}

csThread::csThread(size_t stack_size)
    : csEventClient()
{
    memset(&id, 0xff, sizeof(pthread_t));

    int rc;
    if ((rc = pthread_attr_init(&attr)) != 0)
        throw csException(rc, "pthread_attr_init");
    if ((rc = pthread_attr_setstacksize(&attr, stack_size)) != 0)
        throw csException(rc, "pthread_attr_setstacksize");
}

void csThread::Start(void)
{
    int rc;
    if ((rc = pthread_create(&id, &attr,
        &cs_thread_entry, (void *)this)) != 0) {
        memset(&id, 0xff, sizeof(pthread_t));
        throw csException(rc, "pthread_create");
    }
}

void csThread::Join(void)
{
    int rc;
    if ((rc = pthread_attr_destroy(&attr)) != 0)
        csLog::Log(csLog::Error, "pthread_attr_destroy: %s", strerror(rc));

    pthread_t id_invalid;
    memset(&id_invalid, 0xff, sizeof(pthread_t));
    if (memcmp(&id, &id_invalid, sizeof(pthread_t)) &&
        (rc = pthread_join(id, NULL)) != 0)
        csLog::Log(csLog::Error, "pthread_join: %s", strerror(rc));
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
