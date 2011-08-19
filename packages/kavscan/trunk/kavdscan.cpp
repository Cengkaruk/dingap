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

#include <sys/types.h>
#include <sys/socket.h>
#include <sys/time.h>
#include <sys/stat.h>
#include <sys/select.h>
#include <sys/socketvar.h>

#include <linux/un.h>
#include <netinet/in.h>

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <pthread.h>
#include <expat.h>
#include <syslog.h>
#include <signal.h>
#include <fcntl.h>
#include <limits.h>
#include <ctype.h>

#include "ksutil.h"

#define KS_READ_TTL             15
#define KS_STACK_SIZE           0x80000
#define KS_CMD_LINE_SIZE        (PATH_MAX + 1024)
#define KS_PID_PATH             "/var/run/kavscan/kavdscan.pid"

extern int errno;

static int signal_caught = 0;
static void signal_handler(int sig) { signal_caught = sig; }

class ksExSyscallError : public runtime_error
{
public:
    explicit ksExSyscallError(const string &syscall, const string &error)
        : runtime_error(error), syscall(syscall) { };
    virtual ~ksExSyscallError() throw() { };
    const string &GetSyscall(void) const { return syscall; };

protected:
    string syscall;
};

enum ksCommand
{
    KS_CMD_CONTSCAN,
    KS_CMD_ZCONTSCAN,
    KS_CMD_MAX,
};

enum ksReply
{
    KS_REPLY_UNKNOWN,
    KS_REPLY_READ_TIMEOUT,
    KS_REPLY_MAX,
};

static const char *ksCommandString[] = {
    "CONTSCAN",
    "zCONTSCAN",
    NULL
};

static const char *ksReplyString[] = {
    "UNKNOWN COMMAND",
    "COMMAND READ TIMED OUT",
    NULL
};

struct ksSession_t
{
    pthread_t tid;
    int cd;
};

#define KS_WRITE(cd, buffer, length) { \
    if (write(cd, buffer, length) != (ssize_t)length) \
        syslog(LOG_DAEMON | LOG_ERR, \
            "%s:%d: write: %s", __func__, __LINE__, strerror(errno)); \
    }

static void *ksSessionThread(void *param)
{
    int r;
    fd_set fds;
    struct timeval tv, tv_ttl;
    ssize_t bytes, length = 0;
    char buffer[KS_CMD_LINE_SIZE];
    struct ksSession_t *session = (struct ksSession_t *)param;

    sigset_t sigset;
    sigemptyset(&sigset);
    sigaddset(&sigset, SIGINT);
    sigaddset(&sigset, SIGQUIT);
    sigaddset(&sigset, SIGTERM);
    sigaddset(&sigset, SIGPIPE);
    pthread_sigmask(SIG_BLOCK, &sigset, NULL);

    if (fcntl(session->cd, F_SETFL, O_NONBLOCK) == -1) {
        syslog(LOG_DAEMON | LOG_ERR,
            "fcntl: O_NONBLOCK: %s.", strerror(errno));
        goto session_terminate;
    }

    gettimeofday(&tv_ttl, NULL);

    for ( ;; ) {
        FD_ZERO(&fds);
        FD_SET(session->cd, &fds);
        tv.tv_sec = 1; tv.tv_usec = 0;
        if ((r = select(session->cd + 1, &fds, NULL, NULL, &tv)) == -1) {
            if (errno == EINTR) continue;
            syslog(LOG_DAEMON | LOG_ERR, "select: %s", strerror(errno));
            break;
        }
        else if (r > 0 && FD_ISSET(session->cd, &fds)) {
            if (length == KS_CMD_LINE_SIZE) {
                syslog(LOG_DAEMON | LOG_ERR, "read error: buffer exhausted");
                length = -1;
                break;
            }
            bytes = read(session->cd,
                buffer + length, KS_CMD_LINE_SIZE - length);
            if (bytes == 0) {
                length = -1;
                break;
            }
            if (bytes == -1) {
                if (errno == EAGAIN) continue;
                syslog(LOG_DAEMON | LOG_ERR, "read: %s", strerror(errno));
                length = -1;
                break;
            }
            length += bytes;
            if (buffer[length - 1] == '\n') break;
            else if (buffer[length - 1] == 0) {
                length--;
                break;
            }
            gettimeofday(&tv_ttl, NULL);
        }
        else {
            struct timeval tv_now;
            gettimeofday(&tv_now, NULL);
            if (tv_now.tv_sec - tv_ttl.tv_sec > KS_READ_TTL) {
                syslog(LOG_DAEMON | LOG_ERR, "read: timed-out");
                sprintf(buffer, "%s\n", ksReplyString[KS_REPLY_READ_TIMEOUT]);
                KS_WRITE(session->cd, buffer, strlen(buffer));
                length = -1;
                break;
            }
        }
    }

    if (length > 0) {
        int offset = 0;
        ksCommand cmd = KS_CMD_MAX;
        if (!strncmp(buffer, ksCommandString[KS_CMD_CONTSCAN],
            strlen(ksCommandString[KS_CMD_CONTSCAN]))) {
            cmd = KS_CMD_CONTSCAN;
            offset = strlen(ksCommandString[KS_CMD_CONTSCAN]);
        }
        else if (!strncmp(buffer, ksCommandString[KS_CMD_ZCONTSCAN],
            strlen(ksCommandString[KS_CMD_ZCONTSCAN]))) {
            cmd = KS_CMD_ZCONTSCAN;
            offset = strlen(ksCommandString[KS_CMD_ZCONTSCAN]);
        }

        if (cmd == KS_CMD_MAX) {
            syslog(LOG_DAEMON | LOG_ERR, "read: invalid command");
            sprintf(buffer, "%s\n", ksReplyString[KS_REPLY_UNKNOWN]);
            KS_WRITE(session->cd, buffer, strlen(buffer));
            goto session_terminate;
        }

        while (length > 0) {
            if (isspace(buffer[length - 1])) length--;
            else break;
        }

        string arg;
        if (length > offset) {
            while (isspace(buffer[offset])) offset++;
            if (length > offset) {
                arg.assign(
                    (const char *)(buffer + offset), length - offset);
            }
        }

        ostringstream os;
        os << "kavscan \"" << arg << "\"";
#ifdef KS_DEBUG
        syslog(LOG_DAEMON | LOG_DEBUG, "popen: %s",
            os.str().c_str());
#endif
        FILE *hp = popen(os.str().c_str(), "r");
        if (!hp) {
            syslog(LOG_DAEMON | LOG_ERR, "popen: %s", strerror(errno));
            KS_WRITE(session->cd, buffer, length);
            sprintf(buffer, ": Internal error. ERROR%c",
                (cmd == KS_CMD_ZCONTSCAN) ? '\0' : '\n');
            KS_WRITE(session->cd, buffer,
                (cmd == KS_CMD_ZCONTSCAN) ? strlen(buffer) + 1 :
                strlen(buffer));
            goto session_terminate;
        }
        while (!feof(hp)) {
            if (!fgets(buffer, KS_CMD_LINE_SIZE, hp)) break;
            if (cmd == KS_CMD_ZCONTSCAN) buffer[strlen(buffer) - 1] = '\0';
            KS_WRITE(session->cd, buffer,
                (cmd == KS_CMD_ZCONTSCAN) ? strlen(buffer) + 1 :
                strlen(buffer));
        }
        r = pclose(hp);
        if (r != 0) {
            sprintf(buffer, ": Internal error. ERROR%c",
                (cmd == KS_CMD_ZCONTSCAN) ? '\0' : '\n');
            KS_WRITE(session->cd, buffer,
                (cmd == KS_CMD_ZCONTSCAN) ? strlen(buffer) + 1 :
                strlen(buffer));
        }
    }

session_terminate:
    close(session->cd);
    delete session;

    return NULL;
}

class ksServer
{
public:
    ksServer(int argc, char *argv[]);
    ~ksServer();
    void Run(void);

protected:
    int sd;
    struct ks_conf_t conf;
    ksXmlParser *parser;
};

ksServer::ksServer(int argc, char *argv[])
    : sd(-1), parser(NULL)
{
    conf.filename = KAVSCAN_CONF;
}

ksServer::~ksServer()
{
    if (sd != -1) close(sd);
    if (parser) delete parser;
}

void ksServer::Run(void)
{
    if (!parser) parser = new ksXmlParser(&conf);
    parser->Parse();
    delete parser; parser = NULL;

    if (!conf.ctrl_connection.size())
        throw ksExConfTagNotFound("CtrlConnectionString");
    else if (!conf.pid_path.size())
        throw ksExConfTagNotFound("PidPath");

    ksIsRunning(conf.pid_path);

    if (sd != -1) {
        close(sd);
        unlink(conf.ctrl_connection.c_str());
    }

    int r = 0;
    if (memchr((const void *)conf.ctrl_connection.c_str(), ':',
        conf.ctrl_connection.size()) == NULL) {
        sd = socket(AF_LOCAL, SOCK_STREAM, 0);
        if (sd == -1) throw ksExSyscallError("socket", strerror(errno));

        struct sockaddr_un sa_un;
        size_t slen = sizeof(struct sockaddr_un);
        memset(&sa_un, 0, slen);
        sa_un.sun_family = AF_LOCAL;
        strncpy(sa_un.sun_path, conf.ctrl_connection.c_str(), UNIX_PATH_MAX);

        long max_path_len = 4096;
        max_path_len = pathconf(conf.ctrl_connection.c_str(), _PC_PATH_MAX);
        if (max_path_len == -1)
            throw ksExSyscallError("pathconf", strerror(errno));

        FILE *fh = fopen("/proc/net/unix", "r");
        if (!fh)
            throw ksExSyscallError("fopen", strerror(errno));

        for ( ;; ) {
            char filename[max_path_len];
            uint32_t a, b, c, d, e, f, g;
            int count = fscanf(fh, "%x: %u %u %u %u %u %u ",
                &a, &b, &c, &d, &e, &f, &g);
            if (count == 0) {
                if (!fgets(filename, max_path_len, fh)) break;
                continue;
            }
            else if (count == -1) break;
            else if (!fgets(filename, max_path_len, fh)) break;
            else if (strncmp(filename,
                conf.ctrl_connection.c_str(), conf.ctrl_connection.size()) == 0) {
                r = EADDRINUSE;
                break;
            }
        }

        fclose(fh);
        if (r != 0)
            throw ksExSyscallError(conf.ctrl_connection, strerror(r));

        struct stat skt_stat;
        if (stat(conf.ctrl_connection.c_str(), &skt_stat) != 0) {
            if (errno != ENOENT)
                throw ksExSyscallError("stat", strerror(errno));
        }
        else if (unlink(conf.ctrl_connection.c_str()) != 0)
            throw ksExSyscallError("unlink", strerror(errno));

        if (bind(sd, (struct sockaddr *)&sa_un, slen) != 0)
            throw ksExSyscallError("bind", strerror(errno));

        chmod(conf.ctrl_connection.c_str(),
            S_IRUSR | S_IWUSR | S_IRGRP | S_IWGRP | S_IROTH | S_IWOTH);
    }
    else {
        short port = 3310;

        sd = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
        if (sd < 0)
            throw ksExSyscallError("socket", strerror(errno));

        struct sockaddr_in sin;
        memset(&sin, 0, sizeof(struct sockaddr_in));
        sin.sin_family = AF_INET;
        sin.sin_port = htons(port);
        sin.sin_addr.s_addr = htonl(INADDR_ANY);

        int on = 1;
        if (setsockopt(sd,
            SOL_SOCKET, SO_REUSEADDR, (char *)&on, sizeof(on)) == -1) {
            throw ksExSyscallError("setsockopt: SO_REUSEADDR",
                strerror(errno));
        }

        if (bind(sd,
            (struct sockaddr *)&sin, sizeof(struct sockaddr_in)) < 0) {
            throw ksExSyscallError("bind", strerror(errno));
        }
    }

    if (listen(sd, SOMAXCONN) != 0)
        throw ksExSyscallError("listen", strerror(errno));

    pthread_attr_t thread_attr;
    if ((r = pthread_attr_init(&thread_attr)) != 0)
        throw ksExSyscallError("pthread_attr_init", strerror(r));
    if ((r = pthread_attr_setstacksize(&thread_attr, KS_STACK_SIZE)) != 0) {
        pthread_attr_destroy(&thread_attr);
        throw ksExSyscallError("pthread_attr_setstacksize", strerror(r));
    }
    if ((r = pthread_attr_setdetachstate(&thread_attr,
        PTHREAD_CREATE_DETACHED)) != 0) {
        pthread_attr_destroy(&thread_attr);
        throw ksExSyscallError("pthread_attr_setdetachstate", strerror(r));
    }

    FILE *hf = fopen(KS_PID_PATH, "w");
    if (!hf)
        syslog(LOG_DAEMON | LOG_ERR, "%s: %s", KS_PID_PATH, strerror(errno));
    else {
        fprintf(hf, "%d", getpid());
        fclose(hf);
    }

    syslog(LOG_INFO | LOG_DAEMON, "ready");

    fd_set fds;
    int max_fd;
    struct timeval tv;

    for (;;) {
        if (signal_caught) {
            switch (signal_caught) {
            case SIGINT:
            case SIGQUIT:
            case SIGTERM:
                goto ks_server_terminate;

            default:
                syslog(LOG_DAEMON | LOG_WARNING, "un-handled signal: %s",
                    (const char *)strsignal(signal_caught));
            }
            signal_caught = 0;
        }

        FD_ZERO(&fds);
        FD_SET(sd, &fds);
        max_fd = sd;

        tv.tv_sec = 1; tv.tv_usec = 0;
        if ((r = select(max_fd + 1, &fds, NULL, NULL, &tv)) == -1) {
            if (errno == EINTR) continue;
            throw ksExSyscallError("select", strerror(errno));
        }
        else if (r > 0 && FD_ISSET(sd, &fds)) {
            struct ksSession_t *session = new struct ksSession_t;
            if (!session)
                throw ksExSyscallError("new", strerror(errno));
            memset(session, 0, sizeof(struct ksSession_t));

            struct sockaddr_un client;
            socklen_t slen = sizeof(struct sockaddr_un);
            memset(&client, 0, slen);
            session->cd = accept(sd, (struct sockaddr *)&client, &slen);
            if (session->cd == -1) {
                delete session;
                throw ksExSyscallError("accept", strerror(errno));
            }
            if ((r = pthread_create(&session->tid,
                &thread_attr, &ksSessionThread, (void *)session)) != 0) {
                delete session;
                throw ksExSyscallError("pthread_create", strerror(r));
            }
        }
    }

ks_server_terminate:
    pthread_attr_destroy(&thread_attr);
}

int main(int argc, char *argv[])
{
    struct sigaction sigact;
    memset(&sigact, 0, sizeof(struct sigaction));
    sigact.sa_flags = SA_RESTART;
    sigact.sa_handler = signal_handler;
    sigaction(SIGINT, &sigact, NULL);
    sigaction(SIGTERM, &sigact, NULL);
    sigaction(SIGQUIT, &sigact, NULL);
    //sigaction(SIGCHLD, &sigact, NULL);
    //sigaction(SIGUSR1, &sigact, NULL);
    signal(SIGPIPE, SIG_IGN);

#ifndef KS_DEBUG
    if (daemon(0, 0) != 0) {
        fprintf(stderr, "Error daemonizing: %s\n",
            strerror(errno));
        return 1;
    }
    openlog("kavdscan", LOG_PID, LOG_DAEMON);
#else
    openlog("kavdscan", LOG_PERROR | LOG_PID, LOG_DAEMON);
#endif

    try {
        ksServer server(argc, argv);
        server.Run();
    }
    catch (ksExConfOpen &e) {
        syslog(LOG_DAEMON | LOG_ERR,
            "Configuration open error: %s", e.what());
    }
    catch (ksExConfRead &e) {
        syslog(LOG_DAEMON | LOG_ERR,
            "Configuration read error: %s", e.what());
    }
    catch (ksExConfParse &e) {
        syslog(LOG_DAEMON | LOG_ERR,
            "Configuration parse error: %s", e.what());
    }
    catch (ksExConfTagNotFound &e) {
        syslog(LOG_DAEMON | LOG_ERR,
            "Required configuration tag \"%s\" not found", e.what());
    }
    catch (ksExConfKeyNotFound &e) {
        syslog(LOG_DAEMON | LOG_ERR,
            "Required configuration key \"%s\" not found", e.what());
    }
    catch (ksExSyscallError &e) {
        syslog(LOG_DAEMON | LOG_ERR,
            "System call failure: %s: %s", e.GetSyscall().c_str(), e.what());
    }
    catch (ksExNotRunning &e) {
        syslog(LOG_DAEMON | LOG_ERR, "%s", e.what());
    }
    catch (exception &e) {
        syslog(LOG_DAEMON | LOG_ERR, "Exception: %s", e.what());
        return 1;
    }

    return 0;
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
