// ClearSync: Audit plugin.
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

#include <clearsync/csplugin.h>

#define _MIN_UID                500
#define _MAX_UID                65535

class csAudit
{
public:
    csAudit() { };
    virtual ~csAudit() { };

    virtual void Sample(void) = 0;

protected:
};

class csAuditUsers : public csAudit
{
public:
    csAuditUsers(uid_t minuid, uid_t maxuid)
        : csAudit(), minuid(minuid), maxuid(maxuid), users(0L) { };
    virtual ~csAuditUsers() { };

    virtual void Sample(void) { };

protected:
    uid_t minuid;
    uid_t maxuid;
    long users;
};

class csAuditNeighbor : public csAudit
{
public:
    enum Type {
        MAC_Addr,
        IP4_Addr,
        IP6_Addr,

        Unknown
    };

    csAuditNeighbor(Type type) : csAudit(), type(type) { };
    virtual ~csAuditNeighbor() { };

    virtual void Sample(void) { };

protected:
    Type type;
};

class csInterval
{
public:
    csInterval(const string &name);
    virtual ~csInterval();

    cstimer_id_t Set(time_t interval, csEventClient *parent);
    void Start(time_t value);

    inline void AddAudit(csAudit *audit) { this->audit.push_back(audit); };

    bool operator!=(const string &name) {
        return (name != this->name);
    };

    inline string GetName(void) const { return name; };
    inline time_t GetInterval(void) {
        if (timer) return timer->GetInterval(); };
    inline time_t GetRemaining(void) {
        if (timer) return timer->GetRemaining(); };

    void Sample(void);

protected:
    string name;
    csTimer *timer;
    vector<csAudit *> audit;
    static cstimer_id_t timer_id;
};

cstimer_id_t csInterval::timer_id = 0;

csInterval::csInterval(const string &name)
    : name(name), timer(NULL) { }

csInterval::~csInterval()
{
    if (timer != NULL) delete timer;
    for (vector<csAudit *>::iterator i = audit.begin();
        i != audit.end(); i++) delete (*i);
}

cstimer_id_t csInterval::Set(time_t interval, csEventClient *parent)
{
    cstimer_id_t id;
    csCriticalSection::Lock();
    id = timer_id++;
    csCriticalSection::Unlock();

    if (timer != NULL) delete timer;
    timer = new csTimer(id, interval, interval, parent);

    return id;
}

void csInterval::Start(time_t value)
{
    timer->SetValue(value);
    timer->Start();
}

void csInterval::Sample(void)
{
    for (vector<csAudit *>::iterator i = audit.begin();
        i != audit.end(); i++) (*i)->Sample();
}

class csPluginConf;
class csPluginXmlParser : public csXmlParser
{
public:
    virtual void ParseElementOpen(csXmlTag *tag);
    virtual void ParseElementClose(csXmlTag *tag);
};

class csPluginAudit;
class csPluginConf : public csConf
{
public:
    csPluginConf(csPluginAudit *parent,
        const char *filename, csPluginXmlParser *parser)
        : csConf(filename, parser), parent(parent) { };

    virtual void Reload(void);

protected:
    friend class csPluginXmlParser;

    csPluginAudit *parent;
};

void csPluginConf::Reload(void)
{
    csConf::Reload();
    parser->Parse();
}

class csPluginAudit : public csPlugin
{
public:
    csPluginAudit(const string &name,
        csEventClient *parent, size_t stack_size);
    virtual ~csPluginAudit();

    virtual void SetConfigurationFile(const string &conf_filename);

    virtual void *Entry(void);

protected:
    friend class csPluginXmlParser;

    csPluginConf *conf;
    map<cstimer_id_t, csInterval *> interval;
};

csPluginAudit::csPluginAudit(const string &name,
    csEventClient *parent, size_t stack_size)
    : csPlugin(name, parent, stack_size), conf(NULL)
{
    csLog::Log(csLog::Debug, "%s: Initialized.", name.c_str());
}

csPluginAudit::~csPluginAudit()
{
    Join();
    if (conf) delete conf;
}

void csPluginAudit::SetConfigurationFile(const string &conf_filename)
{
    if (conf == NULL) {
        csPluginXmlParser *parser = new csPluginXmlParser();
        conf = new csPluginConf(this, conf_filename.c_str(), parser);
        parser->SetConf(dynamic_cast<csConf *>(conf));
        conf->Reload();
    }
}

void *csPluginAudit::Entry(void)
{
    cstimer_id_t id;
    map<cstimer_id_t, csInterval *>::iterator i;

    csLog::Log(csLog::Info, "%s: Running.", name.c_str());

    for (i = interval.begin(); i != interval.end(); i++) {
        unsigned long value = (unsigned long)i->second->GetInterval();
        GetStateVar(i->second->GetName(), value);
        i->second->Start((time_t)value);
    }

    for (bool run = true ; run; ) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            csLog::Log(csLog::Info, "%s: Terminated.", name.c_str());
            run = false;
            break;

        case csEVENT_TIMER:
            id = static_cast<csTimerEvent *>(event)->GetTimer()->GetId();
            i = interval.find(id);
            if (i == interval.end()) {
                csLog::Log(csLog::Error, "Event from unknown timer: %ld", id);
                delete static_cast<csTimerEvent *>(event)->GetTimer();
            }
            i->second->Sample();
            break;
        }

        delete event;
    }

    for (i = interval.begin(); i != interval.end(); i++) {
        unsigned long value = (unsigned long)i->second->GetRemaining();
        SetStateVar(i->second->GetName(), value);
    }

    return NULL;
}

void csPluginXmlParser::ParseElementOpen(csXmlTag *tag)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);
    if ((*tag) == "interval") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->ParamExists("name"))
            ParseError("parameter missing: " + tag->GetName());

        csInterval *interval = NULL;
        map<cstimer_id_t, csInterval *>::iterator i;
        string name = tag->GetParamValue("name");
        for (i = _conf->parent->interval.begin();
            i != _conf->parent->interval.end(); i++) {
            if (*(i->second) != name) continue;
            interval = i->second;
            break;
        }

        if (interval != NULL)
            ParseError("interval exists: " + name);

        interval = new csInterval(name);
        tag->SetData(static_cast<void *>(interval));
    }
    else if ((*tag) == "users") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());

        if (!tag->ParamExists("interval"))
            ParseError("parameter missing: " + tag->GetName());

        csInterval *interval = NULL;
        map<cstimer_id_t, csInterval *>::iterator i;
        string name = tag->GetParamValue("interval");
        for (i = _conf->parent->interval.begin();
            i != _conf->parent->interval.end(); i++) {
            if (*(i->second) != name) continue;
            interval = i->second;
            break;
        }

        if (interval == NULL)
            ParseError("interval not found: " + name);
        
        uid_t minuid = _MIN_UID, maxuid = _MAX_UID;
        if (tag->ParamExists("min-uid"))
            minuid = (uid_t)atoi(tag->GetParamValue("min-uid").c_str());
        if (tag->ParamExists("max-uid"))
            maxuid = (uid_t)atoi(tag->GetParamValue("max-uid").c_str());

        csAuditUsers *users = new csAuditUsers(minuid, maxuid);
        interval->AddAudit(dynamic_cast<csAudit *>(users));
    }
    else if ((*tag) == "neighbor") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());

        if (!tag->ParamExists("interval"))
            ParseError("parameter missing: " + tag->GetName());

        csInterval *interval = NULL;
        map<cstimer_id_t, csInterval *>::iterator i;
        string name = tag->GetParamValue("interval");
        for (i = _conf->parent->interval.begin();
            i != _conf->parent->interval.end(); i++) {
            if (*(i->second) != name) continue;
            interval = i->second;
            break;
        }

        if (interval == NULL)
            ParseError("interval not found: " + name);

        if (!tag->ParamExists("type"))
            ParseError("parameter missing: " + tag->GetName());

        csAuditNeighbor::Type type = csAuditNeighbor::Unknown;
        if (!strcasecmp(tag->GetParamValue("type").c_str(), "mac"))
            type = csAuditNeighbor::MAC_Addr;
        else if (!strcasecmp(tag->GetParamValue("type").c_str(), "ip4"))
            type = csAuditNeighbor::IP4_Addr;
        else if (!strcasecmp(tag->GetParamValue("type").c_str(), "ip6"))
            type = csAuditNeighbor::IP6_Addr;

        if (type == csAuditNeighbor::Unknown)
            ParseError("invalid neighbor type: " + tag->GetParamValue("type"));

        csAuditNeighbor *neigh = new csAuditNeighbor(type);
        interval->AddAudit(dynamic_cast<csAudit *>(neigh));
    }
}

void csPluginXmlParser::ParseElementClose(csXmlTag *tag)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "interval") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        string text = tag->GetText();
        if (!text.size())
            ParseError("missing value for tag: " + tag->GetName());

        time_t value = (time_t)atol(text.c_str());
        csInterval *interval = static_cast<csInterval *>(tag->GetData());

        try {
            cstimer_id_t id = interval->Set(value, _conf->parent);
            _conf->parent->interval[id] = interval;
        }
        catch (csException &e) {
            delete interval;
        }
    }
}

csPluginInit(csPluginAudit);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
