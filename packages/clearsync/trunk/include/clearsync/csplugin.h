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

#ifndef _CSPLUGIN_H
#define _CSPLUGIN_H

using namespace std;

#ifndef _CS_INTERNAL

#include <stdexcept>
#include <string>
#include <vector>
#include <map>

#include <stdio.h>
#include <stdint.h>
#include <string.h>
#include <errno.h>
#include <pthread.h>
#include <syslog.h>
#include <signal.h>
#include <expat.h>
#include <regex.h>

#include <clearsync/csexception.h>
#include <clearsync/cslog.h>
#include <clearsync/csconf.h>
#include <clearsync/csevent.h>
#include <clearsync/csthread.h>
#include <clearsync/cstimer.h>
#include <clearsync/csutil.h>
#include <clearsync/csthread.h>

#endif // !_CS_INTERNAL

#define csPluginInit(class_name) \
    extern "C" { \
    csPlugin *csPluginInit(const string &name, \
        csEventClient *parent, size_t stack_size) { \
        class_name *p = new class_name(name, parent, stack_size); \
        if (p == NULL) return NULL; \
        return dynamic_cast<csPlugin *>(p); \
    } }

struct csPluginStateValue
{
    size_t length;
    uint8_t *value;
};

class csPlugin : public csThread
{
public:
    csPlugin(const string &name, csEventClient *parent, size_t stack_size);
    virtual ~csPlugin();

    virtual void *Entry(void) = 0;
    inline string GetName(void) { return name; };

    void SetStateFile(const string &state_file);
    virtual void SetConfigurationFile(const string &conf_filename) { };

    virtual void LoadState(void);
    virtual void SaveState(void);

    bool GetStateVar(const string &key, unsigned long &value);
    bool GetStateVar(const string &key, float &value);
    bool GetStateVar(const string &key, string &value);
    bool GetStateVar(const string &key, size_t &length, uint8_t *value);

    void SetStateVar(const string &key, const unsigned long &value);
    void SetStateVar(const string &key, const float &value);
    void SetStateVar(const string &key, const string &value);
    void SetStateVar(const string &key, size_t length, const uint8_t *value);

protected:
    void SetStateVar(const string &key, struct csPluginStateValue *var);

    string name;
    csEventClient *parent;
    FILE *fh_state;
    map<string, struct csPluginStateValue *> state;
};

#ifdef _CS_INTERNAL

class csPluginLoader
{
public:
    csPluginLoader(const string &so_name,
        const string &name, csEventClient *parent, size_t stack_size);
    virtual ~csPluginLoader();

    inline csPlugin *GetPlugin(void) { return plugin; };

protected:
    string so_name;
    void *so_handle;
    csPlugin *plugin;
};

#endif // _CS_INTERNAL

#endif // _CSPLUGIN_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
