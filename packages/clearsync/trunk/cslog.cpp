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

#include <stdio.h>
#include <stdint.h>
#include <stdarg.h>
#include <string.h>
#include <errno.h>
#include <syslog.h>
#include <pthread.h>
#include <time.h>

#include <clearsync/csexception.h>
#include <clearsync/cslog.h>

vector<csLog *> csLog::logger;
pthread_mutex_t *csLog::logger_mutex = NULL;
uint32_t csLog::logger_mask = csLog::Everything;
char csLog::timestamp[_CS_MAX_TIMESTAMP];

csLog::csLog()
    : type(csLog::StdOut),
    filename(NULL), fh(NULL),
    ident(NULL), option(-1), facility(-1)
{
    Initialize();
}

csLog::csLog(const char *filename)
    : type(csLog::LogFile),
    filename(filename), fh(NULL),
    ident(NULL), option(-1), facility(-1)
{
    fh = fopen(filename, "a+");
    if (fh == NULL) throw csException(errno, "fopen");
    Initialize();
}

csLog::csLog(const char *ident, int option, int facility)
    : type(csLog::Syslog),
    filename(NULL), fh(NULL),
    ident(ident), option(option), facility(facility)
{
    size_t syslog_count = 0;

    if (csLog::logger_mutex != NULL) {
        vector<csLog *>::iterator i;
        pthread_mutex_lock(csLog::logger_mutex);
        for (i = csLog::logger.begin(); i != csLog::logger.end(); i++) {
            if ((*i)->GetType() == csLog::Syslog) syslog_count++;
        }
        pthread_mutex_unlock(csLog::logger_mutex);
    }
    if (syslog_count != 0) throw csException("Syslog logger already exists.");
    openlog(ident, option, facility);
    Initialize();
}

csLog::~csLog()
{
    size_t syslog_count = 0;

    if (csLog::logger_mutex != NULL) {
        vector<csLog *>::iterator i;
        pthread_mutex_lock(csLog::logger_mutex);
        for (i = csLog::logger.begin(); i != csLog::logger.end(); i++) {
            if ((*i) != this) continue;
            csLog::logger.erase(i);
            break;
        }
        for (i = csLog::logger.begin(); i != csLog::logger.end(); i++) {
            if ((*i)->GetType() == csLog::Syslog) syslog_count++;
        }
        size_t logger_count = (size_t)csLog::logger.size();
        pthread_mutex_unlock(csLog::logger_mutex);
        if (logger_count == 0) {
            pthread_mutex_destroy(csLog::logger_mutex);
            delete csLog::logger_mutex;
            csLog::logger_mutex = NULL;
        }
    }

    switch (type) {
    case csLog::StdOut:
        break;
    case csLog::LogFile:
        if (fh) fclose(fh);
        break;
    case csLog::Syslog:
        if (syslog_count == 0) closelog();
        break;
    }
}

void csLog::Log(Level level, const string &message)
{
    csLog::Log(level, message.c_str());
}

void csLog::Log(Level level, const char *format, ...)
{
    if (!(level & csLog::logger_mask) ||
        csLog::logger_mutex == NULL) return;

    pthread_mutex_lock(csLog::logger_mutex);

    csLog *handler = csLog::logger.back();
    if (!handler) {
        // XXX: Should *never* be...
        pthread_mutex_unlock(csLog::logger_mutex);
        return;
    }

    va_list ap;
    va_start(ap, format);

    if (handler->GetType() == csLog::StdOut ||
        handler->GetType() == csLog::LogFile) {
        FILE *stream = NULL;
        if (handler->GetType() == csLog::LogFile)
            stream = handler->GetStream();
        else {
            if ((level & (csLog::Info | csLog::Warning)))
                stream = stdout;
            else
                stream = stderr;
        }
        time_t now = time(NULL);
        struct tm tm_now;
        localtime_r(&now, &tm_now);
        if ((handler->GetType() == csLog::LogFile ||
            (csLog::Debug & logger_mask)) &&
            strftime(timestamp, _CS_MAX_TIMESTAMP,
            "[%d/%b/%Y:%T %z]", &tm_now) > 0) {
            fputs(timestamp, stream);
            fputc(' ', stream);
        }
        if ((level & csLog::Warning))
            fputs("[Warning]: ", stream);
        else if ((level & csLog::Error))
            fputs("[Error]: ", stream);
        else if ((level & csLog::Debug))
            fputs("[Debug]: ", stream);

        vfprintf(stream, format, ap);
        fputc('\n', stream);
    }
    else if (handler->GetType() == csLog::Syslog) {
        int priority;
        if ((level & csLog::Warning))
            priority = LOG_WARNING;
        else if ((level & csLog::Error))
            priority = LOG_ERR;
        else if ((level & csLog::Debug))
            priority = LOG_DEBUG;
        else
            priority = LOG_INFO;

        vsyslog(priority, format, ap);
    }

    va_end(ap);
    pthread_mutex_unlock(csLog::logger_mutex);
}

void csLog::LogException(Level level, csException &e)
{
}

void csLog::LogException(csDebugException &e)
{
}

void csLog::Initialize(void)
{
    if (csLog::logger_mutex == NULL) {
        csLog::logger_mutex = new pthread_mutex_t;
        pthread_mutex_init(logger_mutex, NULL);
    }
    pthread_mutex_lock(csLog::logger_mutex);
    csLog::logger.push_back(this);
    pthread_mutex_unlock(csLog::logger_mutex);
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
