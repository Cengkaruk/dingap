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

#include <stdexcept>
#include <string>
#include <vector>
#include <map>

#include <stdint.h>
#include <string.h>
#include <errno.h>
#include <signal.h>
#include <time.h>

#include <clearsync/csexception.h>
#include <clearsync/cslog.h>
#include <clearsync/csutil.h>
#include <clearsync/csevent.h>
#include <clearsync/cstimer.h>

pthread_mutex_t *csTimer::signal_id_mutex = NULL;
map<int, bool> csTimer::signal_id;

csTimer::csTimer(cstimer_id_t timer_id,
    time_t value, time_t interval, csEventClient *target)
    : sigrt_id(-1), timer_id(timer_id), target(target)
{
    csCriticalSection::Lock();

    if (signal_id_mutex == NULL) {
        signal_id_mutex = new pthread_mutex_t;
        pthread_mutex_init(signal_id_mutex, NULL);
    
        for (int sigid = SIGRTMIN; sigid <= SIGRTMAX; sigid++)
            signal_id[sigid] = false;
    }

    csCriticalSection::Unlock();

    pthread_mutex_lock(signal_id_mutex);

    for (int sigid = SIGRTMIN; sigid <= SIGRTMAX; sigid++) {
        if (signal_id[sigid] == true) continue;
        sigrt_id = sigid;
        signal_id[sigid] = true;
        break;
    }

    pthread_mutex_unlock(signal_id_mutex);

    if (sigrt_id == -1)
        throw csException("No available real-time signal");

    memset(&sev, 0, sizeof(struct sigevent));
    sev.sigev_notify = SIGEV_SIGNAL;
    sev.sigev_signo = sigrt_id;
    sev.sigev_value.sival_ptr = reinterpret_cast<void *>(this);

    if (timer_create(CLOCK_REALTIME, &sev, &id) < 0)
        throw csException(errno, "timer_create");

    struct timespec ts_now;
    if (clock_gettime(CLOCK_REALTIME, &ts_now) < 0)
        throw csException(errno, "clock_gettime");

    it_spec.it_value.tv_sec = value;
    it_spec.it_value.tv_nsec = 0;
    it_spec.it_interval.tv_sec = interval;
    it_spec.it_interval.tv_nsec = 0;

    if (timer_settime(id, 0, &it_spec, NULL) , 0)
        throw csException(errno, "timer_settime");

    csLog::Log(csLog::Debug,
        "Created timer: %lu [0x%08x], %s, value: %ld, interval: %ld",
        timer_id, (unsigned long)id,
        strsignal(sigrt_id), value, interval);
}

csTimer::~csTimer()
{
    memset(&it_spec, 0, sizeof(struct itimerspec));
    timer_settime(id, 0, &it_spec, NULL);

    if (sigrt_id != -1) {
        pthread_mutex_lock(signal_id_mutex);
        signal_id[sigrt_id] = false;
        pthread_mutex_unlock(signal_id_mutex);
    }
}

time_t csTimer::GetRemaining(void)
{
    struct itimerspec it_remaining;
    timer_gettime(id, &it_remaining);
    // TODO: inaccurate...
    return it_remaining.it_value.tv_sec;
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
