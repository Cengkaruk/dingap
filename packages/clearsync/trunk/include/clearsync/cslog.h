// TODO: Program name/short-description
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

#ifndef _CSLOG_H
#define _CSLOG_H

using namespace std;

#define _CS_MAX_TIMESTAMP       64

class csLog
{
public:
    enum Type
    {
        StdOut,
        LogFile,
        Syslog
    };

    enum Level
    {
        Info = 0x01,
        Warning = 0x02,
        Error = 0x04,
        Debug = 0x08,

        Everything = (Info | Warning | Error | Debug)
    };

    csLog();
    csLog(const char *filename);
    csLog(const char *ident, int option, int facility);

    Type GetType(void) { return type; };
    FILE *GetStream(void) { return fh; };
	bool operator!=(csLog *log) { return bool(log != this); };

    virtual ~csLog();

    static void Log(Level level, const string &message);
    static void Log(Level level, const char *format, ...);
    static void LogException(Level level, csException &e);
    static void LogException(csDebugException &e);

    static void SetMask(uint32_t mask) { csLog::logger_mask = mask; };

protected:
    Type type;
    const char *filename;
    FILE *fh;
    const char *ident;
    int option;
    int facility;

    void Initialize(void);

    static vector<csLog *> logger;
    static pthread_mutex_t *logger_mutex;
    static uint32_t logger_mask;
    static char timestamp[_CS_MAX_TIMESTAMP];
};

#endif // _CSLOG_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
