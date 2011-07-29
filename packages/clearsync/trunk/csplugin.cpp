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
#include <sstream>

#include <sys/types.h>
#include <sys/stat.h>

#include <unistd.h>
#include <stdio.h>
#include <stdint.h>
#include <string.h>
#include <errno.h>
#include <pthread.h>
#include <dlfcn.h>

#include <clearsync/csexception.h>
#include <clearsync/cslog.h>
#include <clearsync/csevent.h>
#include <clearsync/csthread.h>
#include <clearsync/csplugin.h>

csPlugin::csPlugin(const string &name,
    csEventClient *parent, size_t stack_size)
    : csThread(stack_size), name(name), parent(parent), fh_state(NULL)
{
    csLog::Log(csLog::Debug, "Plugin initialized: %s, stack size: %ld",
        name.c_str(), stack_size);
}

csPlugin::~csPlugin()
{
    SaveState();
    if (fh_state != NULL) fclose(fh_state);
    csLog::Log(csLog::Debug, "Plugin destroyed: %s", name.c_str());
}

void csPlugin::SetStateFile(const string &state_file)
{
    if (fh_state != NULL) fclose(fh_state);
    char mode[3] = { "r+" };
    struct stat state_stat;
    if (stat(state_file.c_str(), &state_stat) < 0)
        mode[0] = 'w';
    if ((fh_state = fopen(state_file.c_str(), mode)) == NULL) {
        csLog::Log(csLog::Warning, "Error opening state: %s: %s",
            state_file.c_str(), strerror(errno));
    }
    else LoadState();
}

void csPlugin::LoadState(void)
{
    if (fh_state == NULL) return;
    rewind(fh_state);

    map<string, struct csPluginStateValue *>::iterator i;
    for (i = state.begin(); i != state.end(); i++) {
        if (i->second->value != NULL) delete i->second->value;
        delete i->second;
    }
    state.clear();

    size_t records;
    if (fread((void *)&records, sizeof(size_t), 1, fh_state) != 1) {
        if (!feof(fh_state))
            csLog::Log(csLog::Error, "%s: Error reading state 0", name.c_str());
        return;
    }

    csLog::Log(csLog::Debug, "%s: records: %lu", name.c_str(), records);

    if (records == 0) return;

    for (size_t v = 0; v < records; v++) {
        size_t length;
        if (fread((void *)&length, sizeof(size_t), 1, fh_state) != 1) {
            csLog::Log(csLog::Error, "%s: Error reading state 1", name.c_str());
            return;
        }

        if (length == 0) {
            csLog::Log(csLog::Error, "%s: Corrupt state file 2", name.c_str());
            return;
        }

        char *buffer = new char[length];
        if (fread((void *)buffer,
            sizeof(char), length, fh_state) != length) {
            csLog::Log(csLog::Error, "%s: Error reading state 3", name.c_str());
            delete buffer;
            return;
        }

        string key;
        key.assign(buffer, length);
        delete buffer;

        struct csPluginStateValue *var = new csPluginStateValue;
        if (fread((void *)&var->length, sizeof(size_t), 1, fh_state) != 1) {
            csLog::Log(csLog::Error, "%s: Error reading state 4", name.c_str());
            delete var;
            return;
        }

        if (var->length == 0)
            var->value = NULL;
        else {
            var->value = new uint8_t[var->length];
            if (fread((void *)var->value,
                sizeof(uint8_t), var->length, fh_state) != var->length) {
                csLog::Log(csLog::Error, "%s: Error reading state 5", name.c_str());
                delete var->value;
                delete var;
                return;
            }
        }

        state[key] = var;
    }
}

void csPlugin::SaveState(void)
{
    if (fh_state == NULL) return;
    rewind(fh_state);

    size_t length = state.size();
    if (fwrite((const void *)&length, sizeof(size_t), 1, fh_state) != 1) {
        csLog::Log(csLog::Error, "%s: Error writing state", name.c_str());
        return;
    }

    map<string, struct csPluginStateValue *>::iterator i;
    for (i = state.begin(); i != state.end(); i++) {
        length = i->first.size();
        if (!length) continue;
        if (fwrite((const void *)&length, sizeof(size_t), 1, fh_state) != 1) {
            csLog::Log(csLog::Error, "%s: Error writing state", name.c_str());
            return;
        }
        if (fwrite((const void *)i->first.c_str(),
            sizeof(char), length, fh_state) != length) {
            csLog::Log(csLog::Error, "%s: Error writing state", name.c_str());
            return;
        }

        length = i->second->length;
        if (fwrite((const void *)&length, sizeof(size_t), 1, fh_state) != 1) {
            csLog::Log(csLog::Error, "%s: Error writing state", name.c_str());
            return;
        }
        if (fwrite((const void *)i->second->value,
            sizeof(uint8_t), length, fh_state) != length) {
            csLog::Log(csLog::Error, "%s: Error writing state", name.c_str());
            return;
        }
    }
}

bool csPlugin::GetStateVar(const string &key, unsigned long &value)
{
    map<string, struct csPluginStateValue *>::iterator i;
    i = state.find(key);
    if (i == state.end()) return false;
    if (i->second->length != sizeof(unsigned long)) return false;
    value = *(reinterpret_cast<unsigned long *>(i->second->value));
    return true;
}

bool csPlugin::GetStateVar(const string &key, float &value)
{
    map<string, struct csPluginStateValue *>::iterator i;
    i = state.find(key);
    if (i == state.end()) return false;
    if (i->second->length != sizeof(float)) return false;
    value = *(reinterpret_cast<float *>(i->second->value));
    return true;
}

bool csPlugin::GetStateVar(const string &key, string &value)
{
    map<string, struct csPluginStateValue *>::iterator i;
    i = state.find(key);
    if (i == state.end()) return false;
    if (i->second->length == 0) value = "";
    else {
        value.assign(
            reinterpret_cast<char *>(i->second->value), i->second->length);
    }
    return true;
}

bool csPlugin::GetStateVar(const string &key, size_t &length, uint8_t *value)
{
    map<string, struct csPluginStateValue *>::iterator i;
    i = state.find(key);
    if (i == state.end()) return false;
    length = i->second->length;
    value = i->second->value;
    return true;
}

void csPlugin::SetStateVar(const string &key, const unsigned long &value)
{
    struct csPluginStateValue *var = new struct csPluginStateValue;

    var->length = sizeof(unsigned long);
    var->value = reinterpret_cast<uint8_t *>(new unsigned long);
    memcpy((void *)var->value, (const void *)&value, var->length);

    SetStateVar(key, var);
}

void csPlugin::SetStateVar(const string &key, const float &value)
{
    struct csPluginStateValue *var = new struct csPluginStateValue;

    var->length = sizeof(float);
    var->value = reinterpret_cast<uint8_t *>(new float);
    memcpy((void *)var->value, (const void *)&value, var->length);

    SetStateVar(key, var);
}

void csPlugin::SetStateVar(const string &key, const string &value)
{
    struct csPluginStateValue *var = new struct csPluginStateValue;

    var->length = value.size();
    if (var->length == 0)
        var->value = NULL;
    else {
        char *buffer = new char[var->length];
        value.copy(buffer, var->length);
        var->value = reinterpret_cast<uint8_t *>(buffer);
    }

    SetStateVar(key, var);
}

void csPlugin::SetStateVar(
    const string &key, size_t length, const uint8_t *value)
{
    struct csPluginStateValue *var = new struct csPluginStateValue;

    var->length = length;
    if (var->length == 0)
        var->value = NULL;
    else {
        uint8_t *buffer = new uint8_t[var->length];
        memcpy((void *)buffer, (const void *)value, var->length);
        var->value = buffer;
    }

    SetStateVar(key, var);
}

void csPlugin::SetStateVar(const string &key, struct csPluginStateValue *var)
{
    map<string, struct csPluginStateValue *>::iterator i;
    i = state.find(key);
    if (i != state.end()) {
        if (i->second->value) delete i->second->value;
        delete i->second;
    }
    state[key] = var;
}

csPluginLoader::csPluginLoader(const string &so_name,
    const string &name, csEventClient *parent, size_t stack_size)
    : so_name(so_name), so_handle(NULL)
{
    so_handle = dlopen(so_name.c_str(),
        RTLD_LAZY | RTLD_LOCAL | RTLD_DEEPBIND);
    if (so_handle == NULL) throw csException(dlerror());

    char *dlerror_string;
    csPlugin *(*csPluginInit)(const string &, csEventClient *, size_t);

    dlerror();
    *(void **) (&csPluginInit) = dlsym(so_handle, "csPluginInit");

    if ((dlerror_string = dlerror()) != NULL) {
        dlclose(so_handle);
        so_handle = NULL;
        csLog::Log(csLog::Warning,
            "Plugin initialization failed: %s", dlerror_string);
        throw csException(dlerror_string);
    }

    plugin = (*csPluginInit)(name, parent, stack_size);
    if (plugin == NULL) {
        dlclose(so_handle);
        so_handle = NULL;
        csLog::Log(csLog::Warning,
            "Plugin initialization failed: %s", so_name.c_str());
        throw csException("csPluginInit");
    }

    csLog::Log(csLog::Debug, "Plugin loaded: %s", so_name.c_str());
}

csPluginLoader::~csPluginLoader()
{
    if (so_handle != NULL) dlclose(so_handle);
    csLog::Log(csLog::Debug, "Plugin dereferenced: %s", so_name.c_str());
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
