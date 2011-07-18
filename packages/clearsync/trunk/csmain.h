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

#ifndef _CSMAIN_H
#define _CSMAIN_H

#define _CS_CONF_VERSION        1

#ifndef _CS_MAIN_CONF
#define _CS_MAIN_CONF           "/etc/clearsync.conf"
#endif

#ifndef _CS_PLUGIN_CONF
#define _CS_PLUGIN_CONF         "/etc/clearsync.d"
#endif

#ifndef _CS_PID_FILE
#define _CS_PID_FILE            "/var/run/clearsync/clearsyncd.pid"
#endif

#ifndef _CS_VERSION
#ifndef PROGRAM_VERSION
#define _CS_VERSION             "0.0"
#else
#define _CS_VERSION             PROGRAM_VERSION
#endif
#endif

#define csEXIT_SUCCESS          0
#define csEXIT_INVALID_OPTION   1
#define csEXIT_XML_PARSE_ERROR  2
#define csEXIT_UNHANDLED_EX     3

class csSignalHandler : public csThread
{
public:
    csSignalHandler(csEventClient *parent, const sigset_t &signal_set)
        : csThread(), parent(parent), signal_set(signal_set) { };
    virtual ~csSignalHandler() {
        pthread_kill(id, SIGTERM);
        Join();
    };

    virtual void *Entry(void);
    void Reaper(void);

protected:
    sigset_t signal_set;
    csEventClient *parent;
};

class csMainConf;
class csMainXmlParser : public csXmlParser
{
public:
    csMainXmlParser(void);

    virtual void ParseElementOpen(csXmlTag *tag);
    virtual void ParseElementClose(csXmlTag *tag);
};

class csMain;
class csMainConf : public csConf
{
public:
    csMainConf(csMain *parent, const char *filename, csMainXmlParser *parser);
    virtual ~csMainConf();

    virtual void Reload(void);

protected:
    friend class csMainXmlParser;

    csMain *parent;
    int version;
    string plugin_dir;

    void ScanPlugins(void);
};

class csMain : public csEventClient
{
public:
    csMain(int argc, char *argv[]);
    virtual ~csMain();

    void Run(void);
    void Usage(bool version = false);

protected:
    friend class csMainXmlParser;

    csLog *log_stdout;
    csLog *log_syslog;
    csLog *log_logfile;
    csMainConf *conf;
    csSignalHandler *sig_handler;
    map<string, csPluginLoader *> plugin;
};

class csUsageException : public csException
{
public:
    explicit csUsageException(void)
        : csException("csUsageException") { };
};

class csInvalidOptionException : public csException
{
public:
    explicit csInvalidOptionException(void)
        : csException("csInvalidOptionException") { };
};

#endif // _CSMAIN_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
