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

#include <vector>
#include <string>
#include <stdexcept>

#include <stdio.h>
#include <stdint.h>
#include <pthread.h>
#include <string.h>
#include <errno.h>

#include <clearsync/csexception.h>
#include <clearsync/cslog.h>
#include <clearsync/csutil.h>
#include <clearsync/csevent.h>

csEvent::csEvent(csevent_id_t id, csevent_flag_t flags)
    : id(id), flags(flags), src(NULL), dst(NULL) { }

csEvent *csEvent::Clone(void)
{
    return new csEvent(*this);
}

vector<csEventClient *> csEventClient::event_client;
pthread_mutex_t *csEventClient::event_client_mutex = NULL;

csEventClient::csEventClient()
{
    pthread_mutex_init(&event_queue_mutex, NULL);
    pthread_cond_init(&event_condition, NULL);
    pthread_mutex_init(&event_condition_mutex, NULL);

    csCriticalSection::Lock();

    if (event_client_mutex == NULL) {
        event_client_mutex = new pthread_mutex_t;
        pthread_mutex_init(event_client_mutex, NULL);
    }

    csCriticalSection::Unlock();

    pthread_mutex_lock(event_client_mutex);
    event_client.push_back(this);
    pthread_mutex_unlock(event_client_mutex);
}

csEventClient::~csEventClient()
{
    pthread_mutex_lock(event_client_mutex);

    pthread_mutex_destroy(&event_queue_mutex);
    pthread_cond_destroy(&event_condition);
    pthread_mutex_destroy(&event_condition_mutex);

    for (vector<csEvent *>::iterator i = event_queue.begin();
        i != event_queue.end(); i++) delete (*i);
    event_queue.clear();

    for (vector<csEventClient *>::iterator i = event_client.begin();
        i != event_client.end(); i++) {
        if ((*i) != this) continue;
        event_client.erase(i);
        break;
    }

    csCriticalSection::Lock();

    size_t count = (size_t)event_client.size();

    pthread_mutex_unlock(event_client_mutex);

    if (count == 0) {
        pthread_mutex_destroy(event_client_mutex);
        delete event_client_mutex;
        event_client_mutex = NULL;
    }

    csCriticalSection::Unlock();
}

void csEventClient::EventPush(csEvent *event, csEventClient *src)
{
    pthread_mutex_lock(&event_queue_mutex);

    if (event->IsExclusive()) {
        vector<csEvent *>::iterator i;
        for (i = event_queue.begin(); i != event_queue.end(); i++) {
            if ((*i)->GetId() != event->GetId()) continue;
            delete (*i);
            event_queue.erase(i);
            break;
        }
    }

    event->SetSource(src);

    if (event->IsHighPriority())
        event_queue.insert(event_queue.begin(), event);
    else
        event_queue.push_back(event);

    pthread_cond_broadcast(&event_condition);
    pthread_mutex_unlock(&event_queue_mutex);
}

void csEventClient::EventDispatch(csEvent *event, csEventClient *dst)
{
    vector<csEventClient *>::iterator i;
    pthread_mutex_lock(event_client_mutex);

    event->SetTarget(dst);

    if (event->GetTarget() == _CS_EVENT_BROADCAST) {
        for (i = event_client.begin(); i != event_client.end(); i++)
            (*i)->EventPush(event->Clone(), this);
        delete event;
    }
    else {
        bool found = false;
        for (i = event_client.begin(); i != event_client.end(); i++) {
            if ((*i) != dst) continue;
            found = true;
            break;
        }
        if (found)
            dst->EventPush(event, this);
        else {
            csLog::Log(csLog::Warning,
                "Destination event client not found: %p", dst);
            delete event;
        }
    }

    pthread_mutex_unlock(event_client_mutex);
}

csEvent *csEventClient::EventPop(void)
{
    csEvent *event = NULL;
    pthread_mutex_lock(&event_queue_mutex);

    if (event_queue.size()) {
        event = event_queue.front();
        if (event->IsSticky())
            event = event->Clone();
        else
            event_queue.erase(event_queue.begin());
    }

    pthread_mutex_unlock(&event_queue_mutex);
    return event;
}

csEvent *csEventClient::EventPopWait(time_t wait_ms)
{
    int rc;
    csEvent *event = NULL;
    struct timespec ts_abstime;

    if (wait_ms > 0) {
        struct timespec delay;
        delay.tv_sec = wait_ms / 1000;
        delay.tv_nsec = (wait_ms - delay.tv_sec * 1000) * 1000 * 1000;

        struct timespec now;
        clock_gettime(CLOCK_REALTIME, &now);
        ts_abstime.tv_sec = now.tv_sec + delay.tv_sec;
        ts_abstime.tv_nsec = now.tv_nsec + delay.tv_nsec;
        if (ts_abstime.tv_nsec >= 1000000000L) {
            ts_abstime.tv_sec++;
            ts_abstime.tv_nsec = ts_abstime.tv_nsec - 1000000000L;
        }
    }

    for ( ;; ) {
        event = EventPop();
        if (event != NULL) break;

        pthread_mutex_lock(&event_condition_mutex);
        if (wait_ms == 0) {
            rc = pthread_cond_wait(&event_condition, &event_condition_mutex);
            pthread_mutex_unlock(&event_condition_mutex);
        }
        else {
            rc = pthread_cond_timedwait(
                &event_condition, &event_condition_mutex, &ts_abstime);
            pthread_mutex_unlock(&event_condition_mutex);
            if (rc == ETIMEDOUT) break;
        }
        if (rc != 0) throw csException(rc, "pthread_cond");
    }

    return event;
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
