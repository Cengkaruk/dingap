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

#include <unistd.h>
#include <getopt.h>
#include <stdio.h>
#include <stdint.h>
#include <stddef.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <pthread.h>
#include <syslog.h>
#include <signal.h>
#include <expat.h>
#include <limits.h>
#include <dirent.h>

#include <clearsync/csexception.h>
#include <clearsync/cslog.h>
#include <clearsync/csconf.h>
#include <clearsync/csevent.h>
#include <clearsync/cstimer.h>
#include <clearsync/csutil.h>
#include <clearsync/csthread.h>
#include <clearsync/csplugin.h>

#include "csmain.h"

void *csSignalHandler::Entry(void)
{
    pid_t pid;
    siginfo_t si;
    int sig, status;

    csLog::Log(csLog::Debug, "Signal handler started.");

    for ( ;; ) {
        sig = sigwaitinfo(&signal_set, &si);
        if (sig < 0) {
            csLog::Log(csLog::Error, "sigwaitinfo: %s", strerror(errno));
            if (errno == EINTR) {
                usleep(100 * 1000);
                continue;
            }
            EventBroadcast(new csEvent(csEVENT_QUIT,
                csEvent::Sticky | csEvent::HighPriority));
            return NULL;
        }
        csLog::Log(csLog::Debug, "Signal received: %s", strsignal(sig));
        switch (sig) {
        case SIGINT:
        case SIGTERM:
            EventBroadcast(new csEvent(csEVENT_QUIT,
                csEvent::Sticky | csEvent::HighPriority));
            return NULL;

        case SIGHUP:
            EventDispatch(new csEvent(csEVENT_RELOAD), parent);
            break;

        default:
            if (sig >= SIGRTMIN && sig <= SIGRTMAX) {
                csTimer *timer = reinterpret_cast<csTimer *>
                    (si.si_value.sival_ptr);
#if 0
                if (timer->GetId() == 900) {
                    EventBroadcast(new csEvent(csEVENT_QUIT,
                        csEvent::Sticky | csEvent::HighPriority));
                    return NULL;
                }
#endif
                csEventClient *target = timer->GetTarget();
                if (target == NULL) target = parent;
                EventDispatch(new csTimerEvent(timer), target);
            }
            else csLog::Log(csLog::Warning,
                "Unhandled signal: %s", strsignal(sig));
        }
    }

    return NULL;
}

csMainXmlParser::csMainXmlParser(void)
    : csXmlParser()
{
}

void csMainXmlParser::ParseElementOpen(csXmlTag *tag)
{
    csMainConf *_conf = static_cast<csMainConf *>(conf);

    if ((*tag) == "csconf") {
        if (stack.size() != 0)
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->ParamExists("version"))
            ParseError("version parameter missing");

        _conf->version = atoi(tag->GetParamValue("version").c_str());
        csLog::Log(csLog::Debug,
            "Configuration version: %d", _conf->version);
        if (_conf->version > _CS_CONF_VERSION)
            ParseError("unsupported version, too new");
    }
    else if ((*tag == "plugin")) {
        size_t stack_size = _CS_THREAD_STACK_SIZE;

        if (stack.size() != 0)
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->ParamExists("name"))
            ParseError("name parameter missing");
        if (!tag->ParamExists("library"))
            ParseError("library parameter missing");
        if (tag->ParamExists("stack-size")) {
            stack_size = (size_t)atol(
                tag->GetParamValue("stack-size").c_str());
            if (stack_size < PTHREAD_STACK_MIN)
                stack_size = PTHREAD_STACK_MIN;
            else if (stack_size % ::csGetPageSize())
                stack_size += (stack_size % ::csGetPageSize());
        }

        map<string, csPluginLoader *>::iterator i;
        i = _conf->parent->plugin.find(tag->GetParamValue("name"));
        if (i != _conf->parent->plugin.end())
            ParseError("duplicate plugin: " + tag->GetParamValue("name"));

        csPluginLoader *plugin = NULL;

        try {
            plugin = new csPluginLoader(
                tag->GetParamValue("library"),
                tag->GetParamValue("name"), _conf->parent, stack_size);
        } catch (csException &e) {
            csLog::Log(csLog::Error, "Plugin loader failed: %s",
                e.estring.c_str());
        }

        if (plugin != NULL) {
            plugin->GetPlugin()->SetConfigurationFile(_conf->filename);
            tag->SetData(plugin->GetPlugin());
            _conf->parent->plugin[tag->GetParamValue("name")] = plugin;

            csLog::Log(csLog::Debug,
                "Plugin: %s (%s), stack size: %ld",
                tag->GetParamValue("name").c_str(),
                tag->GetParamValue("library").c_str(), stack_size);
        }
    }
}

void csMainXmlParser::ParseElementClose(csXmlTag *tag)
{
    string text = tag->GetText();
    csMainConf *_conf = static_cast<csMainConf *>(conf);

    if ((*tag) == "plugin-dir") {
        if (!stack.size() || (*stack.back()) != "csconf")
            ParseError("unexpected tag: " + tag->GetName());
        if (!text.size())
            ParseError("missing value for tag: " + tag->GetName());

        _conf->plugin_dir = text;
        csLog::Log(csLog::Debug,
            "Plug-in configuration directory: %s",
            _conf->plugin_dir.c_str());
    }
    else if ((*tag) == "state-file") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!text.size())
            ParseError("missing value for tag: " + tag->GetName());

        csPlugin *plugin = reinterpret_cast<csPlugin *>
            (stack.back()->GetData());
        if (plugin != NULL)
            plugin->SetStateFile(text);
    }
}

csMainConf::csMainConf(csMain *parent,
    const char *filename, csMainXmlParser *parser)
    : csConf(filename, parser),
    parent(parent), version(-1), plugin_dir(_CS_PLUGIN_CONF)
{
}

csMainConf::~csMainConf()
{
}

void csMainConf::ScanPlugins(void)
{
    string main_conf_filename = filename;
    size_t dirent_len = offsetof(struct dirent, d_name) +
        pathconf(plugin_dir.c_str(), _PC_NAME_MAX) + 1;
    struct dirent *dirent_entry = (struct dirent *)malloc(dirent_len);

    DIR *dh = opendir(plugin_dir.c_str());
    if (dh == NULL) {
        csLog::Log(csLog::Warning, "Error opening plugin-dir: %s: %s",
            plugin_dir.c_str(), strerror(errno));
        goto ScanPlugins_Leave;
    }

    struct dirent *dirent_result;
    for ( ;; ) {
        if (readdir_r(dh, dirent_entry, &dirent_result) != 0)
            break;
        else if (dirent_result == NULL) break;

        if (dirent_result->d_type == DT_DIR) continue;
        else if (dirent_result->d_type != DT_REG &&
            dirent_result->d_type != DT_LNK) continue;

        try {
            ostringstream os;
            os << plugin_dir << "/" << dirent_result->d_name;
            filename = os.str();

            parser->Reset();
            parser->Parse();

        } catch (csXmlParseException &e) {
            csLog::Log(csLog::Error,
                "XML parse error, %s on line: %u, column: %u, byte: 0x%02x",
                e.estring.c_str(), e.row, e.col, e.byte);
        } catch (csException &e) {
                csLog::Log(csLog::Error,
                    "%s: %s.", e.estring.c_str(), e.what());
        }
    }

    closedir(dh);

ScanPlugins_Leave:
    filename = main_conf_filename;
    free(dirent_entry);
}

csMain::csMain(int argc, char *argv[])
    : csEventClient(), log_syslog(NULL), log_logfile(NULL)
{
    bool debug = false;
    string conf_filename = _CS_MAIN_CONF;
    string log_file;

    log_stdout = new csLog();
    log_stdout->SetMask(csLog::Info | csLog::Warning | csLog::Error);

    int rc;
    static struct option options[] =
    {
        { "version", 0, 0, 'V' },
        { "config", 1, 0, 'c' },
        { "debug", 0, 0, 'd' },
        { "log", 1, 0, 'l' },
        { "help", 0, 0, 'h' },

        { NULL, 0, 0, 0 }
    };

    for (optind = 1;; ) {
        int o = 0;
        if ((rc = getopt_long(argc, argv,
            "Vc:dl:h?", options, &o)) == -1) break;
        switch (rc) {
        case 'V':
            Usage(true);
        case 'c':
            conf_filename = optarg;
            break;
        case 'd':
            debug = true;
            log_stdout->SetMask(
                csLog::Info | csLog::Warning | csLog::Error | csLog::Debug);
            break;
        case 'l':
            log_file = optarg;
            break;
        case '?':
            csLog::Log(csLog::Info,
                "Try %s --help for more information.", argv[0]);
            throw csInvalidOptionException();
        case 'h':
            Usage();
            break;
        }
    }

    csMainXmlParser *parser = new csMainXmlParser();
    conf = new csMainConf(this, conf_filename.c_str(), parser);
    parser->SetConf(dynamic_cast<csConf *>(conf));
    conf->Reload();

    if (!debug) {
        if (daemon(1, 0) != 0)
            throw csException(errno, "daemon");
        log_syslog = new csLog("clearsyncd", LOG_PID, LOG_DAEMON);

        pid_t pid = getpid();
        FILE *h_pid = fopen(_CS_PID_FILE, "w+");
        if (h_pid == NULL) {
            csLog::Log(csLog::Warning, "Error saving PID file: %s",
                _CS_PID_FILE);
        }
        else {
            if (fwrite((const void *)&pid, sizeof(pid_t), 1, h_pid) != 1) {
                csLog::Log(csLog::Warning, "Error saving PID file: %s",
                    _CS_PID_FILE);
            }
            fclose(h_pid);
        }
    }

    sigset_t signal_set;
    sigfillset(&signal_set);
    sigdelset(&signal_set, SIGPROF);

    if ((rc = pthread_sigmask(SIG_BLOCK, &signal_set, NULL)) != 0)
        throw csException(rc, "pthread_sigmask");

    sigemptyset(&signal_set);
    sigaddset(&signal_set, SIGINT);
    sigaddset(&signal_set, SIGHUP);
    sigaddset(&signal_set, SIGTERM);
    sigaddset(&signal_set, SIGPIPE);
    sigaddset(&signal_set, SIGCHLD);
    sigaddset(&signal_set, SIGALRM);
    sigaddset(&signal_set, SIGUSR1);
    sigaddset(&signal_set, SIGUSR2);

    csLog::Log(csLog::Debug, "Real-time signals: %d", SIGRTMAX - SIGRTMIN);

    for (int sigrt = SIGRTMIN; sigrt <= SIGRTMAX; sigrt++)
        sigaddset(&signal_set, sigrt);

    sig_handler = new csSignalHandler(this, signal_set);
    sig_handler->Start();

    map<string, csPluginLoader *>::iterator i;
    for (i = plugin.begin(); i != plugin.end(); i++) {
        try {
            i->second->GetPlugin()->Start();
        } catch (csException &e) {
            csLog::Log(csLog::Error, "Error starting plugin: %s", e.what());
        }
    }

    csLog::Log(csLog::Info, "ClearSync initialized.");
}

csMain::~csMain()
{
    map<string, csPluginLoader *>::iterator i;
    for (i = plugin.begin(); i != plugin.end(); i++) {
        delete i->second->GetPlugin();
        delete i->second;
    }

    if (sig_handler) delete sig_handler;
    if (conf) delete conf;
    if (log_logfile) delete log_logfile;
    if (log_syslog) delete log_syslog;
    if (log_stdout) delete log_stdout;
}

void csMainConf::Reload(void)
{
    csLog::Log(csLog::Debug, "Reload configuration.");
    csConf::Reload();
    parser->Parse();
    ScanPlugins();
}

void csMain::Run(void)
{
    for ( ;; ) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            csLog::Log(csLog::Info, "Terminated.");
            delete event;
            return;

        case csEVENT_RELOAD:
            conf->Reload();
            break;

        case csEVENT_TIMER:
            csLog::Log(csLog::Debug, "Timer alarm: %lu",
                static_cast<csTimerEvent *>(event)->GetTimer()->GetId());
            if (static_cast<csTimerEvent *>(event)->GetTimer()->GetId() == 200) {
                delete static_cast<csTimerEvent *>(event)->GetTimer();
                csTimer *cs_timer = new csTimer(200, 10, 10, this);
            }
            break;

        default:
            csLog::Log(csLog::Debug, "Unhandled event: %u", event->GetId());
            break;
        }

        delete event;
    }
}

void csMain::Usage(bool version)
{
    csLog::Log(csLog::Info, "ClearSync v%s", _CS_VERSION);
    csLog::Log(csLog::Info, "Copyright (C) 2011 ClearFoundation [%s %s]",
        __DATE__, __TIME__);
    if (version) {
        csLog::Log(csLog::Info,
            "  This program comes with ABSOLUTELY NO WARRANTY.");
        csLog::Log(csLog::Info,
            "  This is free software, and you are welcome to redistribute it");
        csLog::Log(csLog::Info,
            "  under certain conditions according to the GNU General Public");
        csLog::Log(csLog::Info,
            "  License version 3, or (at your option) any later version.");
#ifdef PACKAGE_BUGREPORT
        csLog::Log(csLog::Info, "Report bugs to: %s", PACKAGE_BUGREPORT);
#endif
    }
    else {
        csLog::Log(csLog::Info,
            "  -V, --version");
        csLog::Log(csLog::Info,
            "    Display program version and license information.");
        csLog::Log(csLog::Info,
            "  -c <file>, --config <file>");
        csLog::Log(csLog::Info,
            "    Specify an alternate configuration file.");
        csLog::Log(csLog::Info,
            "    Default: %s", _CS_MAIN_CONF);
        csLog::Log(csLog::Info,
            "  -d, --debug");
        csLog::Log(csLog::Info,
            "    Enable debugging messages and remain in the foreground.");
    }

    throw csUsageException();
}

int main(int argc, char *argv[])
{
    csMain *cs_main = NULL;
    int rc = csEXIT_SUCCESS;

    try {
        cs_main = new csMain(argc, argv);

        csTimer *cs_timer1 = new csTimer(100, 5, 5, cs_main);
        csTimer *cs_timer2 = new csTimer(200, 7, 7, cs_main);
        csTimer *cs_timer3 = new csTimer(300, 2, 10, cs_main);
        csTimer *cs_timer4 = new csTimer(900, 15, 15, cs_main);

        cs_main->Run();

    } catch (csUsageException &e) {
    } catch (csInvalidOptionException &e) {
        rc = csEXIT_INVALID_OPTION;
    } catch (csXmlParseException &e) {
        csLog::Log(csLog::Error,
            "XML parse error, %s on line: %u, column: %u, byte: 0x%02x",
            e.estring.c_str(), e.row, e.col, e.byte);
        rc = csEXIT_XML_PARSE_ERROR;
    } catch (csException &e) {
        csLog::Log(csLog::Error,
            "%s: %s.", e.estring.c_str(), e.what());
        rc = csEXIT_UNHANDLED_EX;
    }

    if (cs_main) delete cs_main;

    return rc;
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
