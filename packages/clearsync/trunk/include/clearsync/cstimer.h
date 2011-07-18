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

#ifndef _CSTIMER_H
#define _CSTIMER_H

using namespace std;

typedef unsigned long cstimer_id_t;

class csTimer
{
public:
    csTimer(cstimer_id_t timer_id,
        time_t value, time_t interval, csEventClient *target = NULL);
    virtual ~csTimer();

    inline cstimer_id_t GetId(void) { return timer_id; };
    void Start(void);
    void Stop(void);
    void SetValue(time_t value);
    void SetInterval(time_t value);
    void Extend(time_t value);
    time_t GetRemaining(void);
    inline csEventClient *GetTarget(void) { return target; };

protected:
    timer_t id;
    int sigrt_id;
    cstimer_id_t timer_id;
    struct sigevent sev;
    struct itimerspec it_spec;
    csEventClient *target;

    static pthread_mutex_t *signal_id_mutex;
    static map<int, bool> signal_id;
};

class csTimerEvent : public csEvent
{
public:
    csTimerEvent(csTimer *timer)
        : csEvent(csEVENT_TIMER), timer(timer) { };

    inline csTimer *GetTimer(void) { return timer; };

protected:
    csTimer *timer;
};

#endif // _CSTIMER_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
