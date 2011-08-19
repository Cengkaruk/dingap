// KAVscan: Kaspersky Antivirus Scanner
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

#include <string>
#include <map>
#include <vector>
#include <stdexcept>
#include <sstream>

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <errno.h>
#include <expat.h>
#include <dirent.h>
#include <signal.h>
#include <limits.h>

#include <sys/types.h>
#include <sys/stat.h>

#include "sdk8_unix_interface.h"
#include "ksutil.h"

static bool ksAbortScan = false;
void ksSignalHandler(int sig)
{
    switch (sig) {
    case SIGINT:
    case SIGTERM:
    case SIGQUIT:
        ksAbortScan = true;
    }
}

class ksClient;

CALLBACK_RESULT ksEventCallback(unsigned long dwEvent,
    unsigned long dwParam1, unsigned long dwParam2,
    const char *pObjectName, const char *pVirusName,
    unsigned long dwObjectID, unsigned long dwMonitorID,
    void *pUserContext)
{
    //ksClient *client = (ksClient *)pUserContext;

    switch (dwEvent) {
    case EVENT_ASK_PASSWORD:
        return CLBK_CANCEL_OBJECT;

    case EVENT_DETECT:
        printf("%s: %s FOUND\n", pObjectName, pVirusName);
        fflush(stdout);
        break;

    case EVENT_RESULT:
        switch (dwParam1) {
        case KAV_S_R_CLEAN:
            printf("%s: OK\n", pObjectName);
            fflush(stdout);
            break;

        case KAV_S_R_INFECTED:
            break;

        case KAV_S_R_CORRUPTED:
            printf("%s: Corrupted. ERROR\n", pObjectName);
            fflush(stdout);
            break;

        case KAV_S_R_ACCESSDENIED:
            printf("%s: Access denied. ERROR\n", pObjectName);
            fflush(stdout);
            break;

        case KAV_S_R_CANCELED:
            printf("%s: Canceled. ERROR\n", pObjectName);
            fflush(stdout);
            break;

        case KAV_S_R_FAILURE:
            printf("%s: Failure. ERROR\n", pObjectName);
            fflush(stdout);
            break;

        case KAV_S_R_SKIPPED:
            printf("%s: Skipped. ERROR\n", pObjectName);
            fflush(stdout);
            break;

        case KAV_S_R_PASSWORD_PROTECTED:
            printf("%s: Password protected. ERROR\n", pObjectName);
            fflush(stdout);
            break;

        default:
            printf("%s: Unknown 0x%lx. ERROR\n", pObjectName, dwParam1);
            fflush(stdout);
            break;
        }
        break;
    }

    return CLBK_OK;
}

class ksExInitialized : public runtime_error
{
public:
    explicit ksExInitialized()
        : runtime_error("already initialized") { };
    virtual ~ksExInitialized() throw() { };
};

class ksExNotInitialized : public runtime_error
{
public:
    explicit ksExNotInitialized()
        : runtime_error("not initialized") { };
    virtual ~ksExNotInitialized() throw() { };
};

class ksExNoObjects : public runtime_error
{
public:
    explicit ksExNoObjects()
        : runtime_error("no objects to scan") { };
    virtual ~ksExNoObjects() throw() { };
};

class ksExInitializeService : public runtime_error
{
public:
    explicit ksExInitializeService(HRESULT result)
        : runtime_error("kavInitializeService"), result(result) { };
    virtual ~ksExInitializeService() throw() { };
    HRESULT GetResult(void) { return result; };

protected:
    HRESULT result;
};

class ksExScanFile : public runtime_error
{
public:
    explicit ksExScanFile(HRESULT result)
        : runtime_error("kavScanFile"), result(result) { };
    virtual ~ksExScanFile() throw() { };
    HRESULT GetResult(void) { return result; };

protected:
    HRESULT result;
};

class ksExAbortScan : public runtime_error
{
public:
    explicit ksExAbortScan()
        : runtime_error("abort scan") { };
    virtual ~ksExAbortScan() throw() { };
};

class ksClient
{
public:
    ksClient(int argc, char *argv[]);
    ~ksClient();

    void Initialize(void);
    void Scan(void);

protected:
    void ScanFile(const char *file);
    void ScanDirectory(const char *dir);

    bool init;
    struct ks_conf_t conf;
    vector<string> scan_object;
    ksXmlParser *parser;
    unsigned long dwScanMode;
    char rp[PATH_MAX];
};

ksClient::ksClient(int argc, char *argv[])
    : init(false), parser(NULL)
{
    conf.filename = KAVSCAN_CONF;
    for (int i = 1; i < argc; i++) scan_object.push_back(argv[i]);
    dwScanMode =
        KAV_O_M_PACKED | KAV_O_M_ARCHIVED | KAV_O_M_MAILBASES |
        KAV_O_M_MAILPLAIN | KAV_O_M_ICHECKER |
        KAV_O_M_HEURISTIC_LEVEL_DETAIL;
}

ksClient::~ksClient(void)
{
    if (parser) delete parser;
    if (init) kaveUninitialize();
}

void ksClient::Initialize(void)
{
    if (init) throw ksExInitialized();
    if (scan_object.size() == 0) throw ksExNoObjects();

    if (!parser) parser = new ksXmlParser(&conf);
    parser->Parse();
    delete parser; parser = NULL;

    if (!conf.com_connection.size())
        throw ksExConfTagNotFound("ComConnectionString");
    else if (!conf.scan_connection.size())
        throw ksExConfTagNotFound("ScanConnectionString");
    else if (!conf.event_connection.size())
        throw ksExConfTagNotFound("EventConnectionString");
    else if (!conf.pid_path.size())
        throw ksExConfTagNotFound("PidPath");

    ksIsRunning(conf.pid_path);

    HRESULT hr;
    hr = kaveInitializeService(
        conf.com_connection.c_str(),
        conf.scan_connection.c_str(),
        conf.event_connection.c_str(),
        10, 10, 0, ksEventCallback);

    if (FAILED(hr)) throw ksExInitializeService(hr);

    init = true;
}

void ksClient::Scan(void)
{
    if (!init) throw ksExNotInitialized();

    struct stat obj_stat;

    try {
        for (unsigned long i = 0ul; i < scan_object.size(); i++) {
            if (stat(scan_object[i].c_str(), &obj_stat) == -1) {
                printf("%s: %s. ERROR\n",
                    scan_object[i].c_str(), strerror(errno));
                fflush(stdout);
            }
            else if (S_ISREG(obj_stat.st_mode))
                ScanFile(scan_object[i].c_str());
            else if (S_ISDIR(obj_stat.st_mode))
                ScanDirectory(scan_object[i].c_str());
            else {
                printf("%s: Not a file or directory. ERROR\n",
                    scan_object[i].c_str());
                fflush(stdout);
            }

            if (ksAbortScan) throw ksExAbortScan();
        }
    } catch (ksExScanFile &e) {
        kaveCancelAllScan();
        kaveWaitForAllScan();
        throw e;

    } catch (ksExAbortScan &e) {
        kaveCancelAllScan();
        kaveWaitForAllScan();
        throw e;
    }

    kaveWaitForAllScan();
}

void ksClient::ScanFile(const char *file)
{
    HRESULT hr;

    while (!ksAbortScan) {
        hr = kaveScanFile(file,
            1, dwScanMode, KAV_SKIP, INFINITE,
            (void *)this, NULL, NULL);

        if (!FAILED(hr)) break;
        if (hr == KAV_E_QUEUE_OVERFLOW) sleep(1);
        else throw ksExScanFile(hr);
    }
}

void ksClient::ScanDirectory(const char *dir)
{
    DIR *h_dir = opendir(dir);
    if (!h_dir) {
        printf("%s: %s. ERROR\n", dir, strerror(errno));
        fflush(stdout);
        return;
    }

    struct dirent *de;
    struct stat obj_stat;

    while (!ksAbortScan && (de = readdir(h_dir))) {
        string de_name = de->d_name;
        ostringstream os_path;
        os_path << dir << "/" << de_name;

        if (lstat(os_path.str().c_str(), &obj_stat) == -1)
            continue;

        if (S_ISDIR(obj_stat.st_mode)) {
            if (de_name != "." && de_name != "..")
                ScanDirectory(os_path.str().c_str());
            continue;
        }
        else if (!S_ISREG(obj_stat.st_mode)) continue;

        if (realpath(os_path.str().c_str(), rp) == NULL)
            continue;
        else if (S_ISREG(obj_stat.st_mode))
            ScanFile(rp);
    }

    closedir(h_dir);
}

int main(int argc, char *argv[])
{
    signal(SIGINT, ksSignalHandler);
    signal(SIGTERM, ksSignalHandler);
    signal(SIGQUIT, ksSignalHandler);

    try {
        ksClient client(argc, argv);
        client.Initialize();
        client.Scan();

    } catch (ksExScanFile &e) {
        fprintf(stderr, "Error during scan: ");
        switch (e.GetResult()) {
        case KAV_E_MOD_NOT_FOUND:
            fprintf(stderr, "module(s) not found.\n");
            break;
        case E_OUTOFMEMORY:
            fprintf(stderr, "not enough memory.\n");
            break;
        case E_UNEXPECTED:
            fprintf(stderr, "unexpected error.\n");
            break;
        case E_FAIL:
            fprintf(stderr, "general failure.\n");
            break;
        case KAV_E_INVALID_BASES:
            fprintf(stderr, "invalid bases.\n");
            break;
        case E_INVALIDARG:
            fprintf(stderr, "invalid argument.\n");
            break;
        case KAV_E_QUEUE_OVERFLOW:
            fprintf(stderr, "queue overflow.\n");
            break;
        case KAV_E_TIMEOUT:
            fprintf(stderr, "timeout expired.\n");
            break;
        case KAV_E_OUT_OF_SPACE:
            fprintf(stderr, "out of space.\n");
            break;
        case KAV_E_READ_ERROR:
            fprintf(stderr, "read error.\n");
            break;
        case KAV_E_WRITE_ERROR:
            fprintf(stderr, "write error.\n");
            break;
        case KAV_E_ACCESS_DENIED:
            fprintf(stderr, "access denied.\n");
            break;
        case KAV_E_MAILBOMB_SUSPICIOUS:
            fprintf(stderr, "mail bomb.\n");
            break;
        case KAV_E_NEED_REBOOT:
            fprintf(stderr, "need reboot.\n");
            break;
        case E_IPC_NOT_CONNECTED:
            fprintf(stderr, "not connected to server.\n");
            break;
        case E_IPC_ALREADY_CONNECTED:
            fprintf(stderr, "already connected to server.\n");
            break;
        case E_IPC_CONNECTION_CLOSED:
            fprintf(stderr, "connection closed by other side.\n");
            break;
        case E_IPC_TIMEOUT:
            fprintf(stderr, "network operation timeout.\n");
            break;
        default:
            fprintf(stderr, "unknown (0x%lx).\n", e.GetResult());
        }
        return 1;

    } catch (ksExAbortScan &e) {
        fprintf(stderr, "Scan aborted.\n");
        return 1;

    } catch (runtime_error &e) {
        fprintf(stderr, "Run-time error: %s.\n", e.what());
        return 1;

    } catch (exception &e) {
        fprintf(stderr, "Exception: %s.\n", e.what());
        return 1;
    }

    return 0;
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
