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

#ifndef _CSEVENT_H
#define _CSEVENT_H

using namespace std;

// Reserved event IDs
#define csEVENT_QUIT            0x0000
#define csEVENT_RELOAD          0x0001
#define csEVENT_TIMER           0x0002
#define csEVENT_PLUGIN          0x0003

// Broadcast event client type
#define _CS_EVENT_BROADCAST     ((csEventClient *)-1)

typedef unsigned long csevent_id_t;
typedef unsigned long csevent_flag_t;

class csEventClient;
class csEvent
{
public:
    enum Flags
    {
        None = 0x00,
        Exclusive = 0x01,
        HighPriority = 0x02,
        Sticky = 0x04
    };

    csEvent(csevent_id_t id, csevent_flag_t flags = csEvent::None);
    virtual ~csEvent() { };

    virtual csEvent *Clone(void);

    inline csevent_id_t GetId(void) const { return id; };
    inline csevent_flag_t GetFlags(void) const { return flags; };
    inline csEventClient *GetSource(void) const { return src; };
    inline csEventClient *GetTarget(void) const { return dst; };
    inline void SetSource(csEventClient *src) { this->src = src; };
    inline void SetTarget(csEventClient *dst) { this->dst = dst; };

    inline bool IsExclusive(void) {
        return (bool)(flags & csEvent::Exclusive);
    };
    inline bool IsHighPriority(void) {
        return (bool)(flags & csEvent::HighPriority);
    };
    inline bool IsSticky(void) { return (bool)(flags & csEvent::Sticky); };

    inline void SetExclusive(bool enable = true) {
        if (enable) flags |= csEvent::Exclusive;
        else flags &= ~csEvent::Exclusive;
    };
    inline void SetHighPriority(bool enable = true) {
        if (enable) flags |= csEvent::HighPriority;
        else flags &= ~csEvent::HighPriority;
    };
    inline void SetSticky(bool enable = true) {
        if (enable) flags |= csEvent::Sticky;
        else flags &= ~csEvent::Sticky;
    };

protected:
    csevent_id_t id;
    csevent_flag_t flags;
    csEventClient *src;
    csEventClient *dst;
};

class csEventPlugin : public csEvent
{
public:
    csEventPlugin(const string &type);

    virtual csEvent *Clone(void);

    bool GetValue(const string &key, string &value);
    void SetValue(const string &key, const string &value) {
        key_value[key] = value;
    };

protected:
    map<string, string> key_value;
};

class csEventClient
{
public:
    csEventClient();
    virtual ~csEventClient();

    void EventPush(csEvent *event, csEventClient *src);
    void EventDispatch(csEvent *event, csEventClient *dst);
    inline void EventBroadcast(csEvent *event) {
        EventDispatch(event, _CS_EVENT_BROADCAST);
    };
    bool IsEventsEnabled(void) { return event_enable; };
    inline void EventsEnable(bool enable = true) { event_enable = enable; };

protected:
    csEvent *EventPop(void);
    csEvent *EventPopWait(time_t wait_ms = 0);

    pthread_mutex_t event_queue_mutex;
    pthread_cond_t event_condition;
    pthread_mutex_t event_condition_mutex;

    bool event_enable;

    vector<csEvent *> event_queue;

    static vector<csEventClient *> event_client;
    static pthread_mutex_t *event_client_mutex;
};

#endif // _CSEVENT_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
