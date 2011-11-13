// ClearSync: FileSync plugin.
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

#include <sys/select.h>

#include <librsync.h>

#define OPENSSL_THREAD_DEFINES
#include <openssl/opensslconf.h>
#ifndef OPENSSL_THREADS
#error "Require OpenSSL with thread support"
#endif
#include <openssl/crypto.h>
#include <openssl/err.h>
#include <openssl/aes.h>
#include <openssl/rand.h>
#include <openssl/bn.h>

#define csPluginFileSyncAuthKeyBits 256

static rs_result rs_cb_read(
    void *ctx, char *buffer, size_t length, size_t *bytes_read)
{
    csSocket *skt = static_cast<csSocket *>(ctx);

    try {
        skt->Read(*bytes_read, (uint8_t *)buffer);
    } catch (csException &e) {
    }

    return RS_DONE;
}

static rs_result rs_cb_basis(
    void *ctx, char *buffer, size_t length, off_t offset, size_t *bytes_read)
{
    csSocket *skt = static_cast<csSocket *>(ctx);

    try {
    } catch (csException &e) {
    }

    return RS_DONE;
}

static rs_result rs_cb_write(
    void *ctx, const char *buffer, size_t length, size_t *bytes_wrote)
{
    csSocket *skt = static_cast<csSocket *>(ctx);

    try {
        skt->Write(*bytes_wrote, (uint8_t *)buffer);
    } catch (csException &e) {
    }

    return RS_DONE;
}

struct csPluginFileSyncPacket
{
    uint32_t hdr_id:4, hdr_flag:4, hdr_pad:8, hdr_len:16;
    uint8_t *buffer;
    AES_KEY authkey_encrypt;
    AES_KEY authkey_decrypt;
};

class csPluginFileSyncSessionDuplicateFile : public csException
{
public:
    explicit csPluginFileSyncSessionDuplicateFile(const string &name)
        : csException(name.c_str()) { };
};

class csPluginFileSyncFile
{
public:
    csPluginFileSyncFile()
    : name(NULL), path(NULL), presync(NULL), postsync(NULL) { };
    csPluginFileSyncFile(csPluginFileSyncFile *src);
    virtual ~csPluginFileSyncFile() {
        if (name) delete name;
        if (path) delete path;
        if (presync) delete presync;
        if (postsync) delete postsync;
    };

    string *name;
    string *path;
    string *presync;
    string *postsync;
};

csPluginFileSyncFile::csPluginFileSyncFile(csPluginFileSyncFile *src)
    : name(NULL), path(NULL), presync(NULL), postsync(NULL)
{
    if (src->name) name = new string(*(src->name));
    if (src->path) name = new string(*(src->path));
    if (src->presync) name = new string(*(src->presync));
    if (src->postsync) name = new string(*(src->postsync));
}

class csPluginFileSyncConfig
{
public:
    csPluginFileSyncConfig(csSocket *skt);
    virtual ~csPluginFileSyncConfig();

    void AddFile(csPluginFileSyncFile *add_file);
    void AddFile(csPluginFileSyncConfig *config);
    inline csSocket *GetSocket(void) { return skt; };
    inline void SetSocket(csSocket *skt) { this->skt = skt; };

protected:
    csSocket *skt;
    map<string, csPluginFileSyncFile *> file;
};

csPluginFileSyncConfig::csPluginFileSyncConfig(csSocket *skt)
    : skt(skt) { }

csPluginFileSyncConfig::~csPluginFileSyncConfig()
{
    delete skt;
    map<string, csPluginFileSyncFile *>::iterator i;
    for (i = file.begin(); i != file.end(); i++)
        delete i->second;
}

void csPluginFileSyncConfig::AddFile(csPluginFileSyncFile *add_file)
{
    map<string, csPluginFileSyncFile *>::iterator i;
    i = file.find(*(add_file->name));
    if (i != file.end())
        throw csPluginFileSyncSessionDuplicateFile(*(add_file->name));
    file[*(add_file->name)] = add_file;
}

void csPluginFileSyncConfig::AddFile(csPluginFileSyncConfig *config)
{
    map<string, csPluginFileSyncFile *>::iterator i;
    for (i = file.begin(); i != file.end(); i++) {
        csPluginFileSyncFile *new_file = new csPluginFileSyncFile(i->second);
        config->AddFile(new_file);
    }
}

class csPluginFileSyncSession : public csThread
{
public:
    csPluginFileSyncSession(csSocket *skt,
        const uint8_t *authkey, size_t authkey_bits);
    csPluginFileSyncSession(csPluginFileSyncConfig *config,
        const uint8_t *authkey, size_t authkey_bits);
    virtual ~csPluginFileSyncSession();

    virtual void *Entry(void) = 0;
    virtual void Run(void) = 0;

    csPluginFileSyncConfig *config;

protected:
    struct csPluginFileSyncPacket pkt;
};

csPluginFileSyncSession::csPluginFileSyncSession(csSocket *skt,
    const uint8_t *authkey, size_t authkey_bits)
    : csThread(), config(NULL)
{
    config = new csPluginFileSyncConfig(skt);

    pkt.hdr_id = pkt.hdr_flag = pkt.hdr_pad = pkt.hdr_len = 0;
    pkt.buffer = new uint8_t[getpagesize() * 2];

    if (AES_set_encrypt_key(authkey, authkey_bits, &pkt.authkey_encrypt) < 0)
        throw csException(EINVAL, "Error setting AES encryption key");
    if (AES_set_decrypt_key(authkey, authkey_bits, &pkt.authkey_decrypt) < 0)
        throw csException(EINVAL, "Error setting AES decryption key");
}

csPluginFileSyncSession::csPluginFileSyncSession(csPluginFileSyncConfig *config,
    const uint8_t *authkey, size_t authkey_bits)
    : csThread(), config(config)
{
    pkt.hdr_id = pkt.hdr_flag = pkt.hdr_pad = pkt.hdr_len = 0;
    pkt.buffer = new uint8_t[getpagesize() * 2];

    if (AES_set_encrypt_key(authkey, authkey_bits, &pkt.authkey_encrypt) < 0)
        throw csException(EINVAL, "Error setting AES encryption key");
    if (AES_set_decrypt_key(authkey, authkey_bits, &pkt.authkey_decrypt) < 0)
        throw csException(EINVAL, "Error setting AES decryption key");
}

csPluginFileSyncSession::~csPluginFileSyncSession()
{
    delete config;
    delete [] pkt.buffer;
}

class csPluginFileSyncSessionMaster : public csPluginFileSyncSession
{
public:
    csPluginFileSyncSessionMaster(
        csPluginFileSyncConfig *config, const uint8_t *authkey, size_t authkey_bits);
    virtual ~csPluginFileSyncSessionMaster();
    virtual void *Entry(void);
    virtual void Run(void);
};

csPluginFileSyncSessionMaster::csPluginFileSyncSessionMaster(
    csPluginFileSyncConfig *config, const uint8_t *authkey, size_t authkey_bits)
    : csPluginFileSyncSession(config, authkey, authkey_bits)
{
}

csPluginFileSyncSessionMaster::~csPluginFileSyncSessionMaster()
{
    Join();
}

void *csPluginFileSyncSessionMaster::Entry(void)
{
    bool run = true;

    while (run) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            run = false;
            break;
        }

        delete event;
    }

    return NULL;
}

void csPluginFileSyncSessionMaster::Run(void)
{
}

class csPluginFileSyncSessionSlave : public csPluginFileSyncSession
{
public:
    csPluginFileSyncSessionSlave(
        csSocket *skt, const uint8_t *authkey, size_t authkey_bits,
        time_t tv_interval);
    virtual ~csPluginFileSyncSessionSlave();
    virtual void *Entry(void);
    virtual void Run(void) { };

protected:
    csTimer *interval;
};

static cstimer_id_t csSlaveSessionInterval = 1;

csPluginFileSyncSessionSlave::csPluginFileSyncSessionSlave(
    csSocket *skt, const uint8_t *authkey, size_t authkey_bits,
    time_t tv_interval)
    : csPluginFileSyncSession(skt, authkey, authkey_bits), interval(NULL)
{
    cstimer_id_t id = __sync_fetch_and_add(&csSlaveSessionInterval, 1);
    interval = new csTimer(id, tv_interval, tv_interval, this);
}

csPluginFileSyncSessionSlave::~csPluginFileSyncSessionSlave()
{
    Join();

    if (interval) delete interval;
}

void *csPluginFileSyncSessionSlave::Entry(void)
{
    bool run = true;

    interval->Start();

    while (run) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            run = false;
            break;

        case csEVENT_TIMER:
            interval->Stop();
            csLog::Log(csLog::Debug, "Starting slave check-in: %lu",
                static_cast<csTimerEvent *>(event)->GetTimer()->GetId());
            try {
                Run();
            } catch (csException &e) {
            }
            break;
        }

        delete event;
    }

    return NULL;
}

class csPluginConf;
class csPluginXmlParser : public csXmlParser
{
public:
    virtual void ParseElementOpen(csXmlTag *tag);
    virtual void ParseElementClose(csXmlTag *tag);
};

class csPluginFileSync;
class csPluginConf : public csConf
{
public:
    csPluginConf(csPluginFileSync *parent,
        const char *filename, csPluginXmlParser *parser)
        : csConf(filename, parser), parent(parent) { };

    virtual void Reload(void);

protected:
    friend class csPluginXmlParser;

    csPluginFileSync *parent;
};

void csPluginConf::Reload(void)
{
    csConf::Reload();
    parser->Parse();
}

class csPluginFileSync : public csPlugin
{
public:
    csPluginFileSync(const string &name,
        csEventClient *parent, size_t stack_size);
    virtual ~csPluginFileSync();

    virtual void SetConfigurationFile(const string &conf_filename);

    virtual void *Entry(void);

protected:
    friend class csPluginXmlParser;

    inline void PollSessions(void);
    inline void StartSession(csPluginFileSyncConfig *session);

    void SetAuthKey(const string &key);
    csPluginFileSyncSession *CreateSession(csSocket *skt, time_t interval = 0);

    csPluginConf *conf;
    uint8_t *authkey;
    size_t authkey_bits;
    size_t authkey_bytes;

    vector<csPluginFileSyncConfig *> server;
    vector<csPluginFileSyncSessionMaster *> master;
    vector<csPluginFileSyncSessionSlave *> slave;
};

csPluginFileSync::csPluginFileSync(const string &name,
    csEventClient *parent, size_t stack_size)
    : csPlugin(name, parent, stack_size), conf(NULL),
    authkey_bits(csPluginFileSyncAuthKeyBits),
    authkey_bytes(csPluginFileSyncAuthKeyBits / 8)
{
    authkey = new uint8_t[authkey_bytes];
}

csPluginFileSync::~csPluginFileSync()
{
    Join();

    vector<csPluginFileSyncConfig *>::iterator i;
    for (i = server.begin(); i != server.end(); i++)
        delete (*i);
    vector<csPluginFileSyncSessionMaster *>::iterator mi;
    for (mi = master.begin(); mi != master.end(); mi++)
        delete (*mi);
    vector<csPluginFileSyncSessionSlave *>::iterator si;
    for (si = slave.begin(); si != slave.end(); si++)
        delete (*si);

    delete [] authkey;
    if (conf) delete conf;
}

void csPluginFileSync::SetConfigurationFile(const string &conf_filename)
{
    if (conf == NULL) {
        csPluginXmlParser *parser = new csPluginXmlParser();
        conf = new csPluginConf(this, conf_filename.c_str(), parser);
        parser->SetConf(dynamic_cast<csConf *>(conf));
        conf->Reload();
    }
}

void *csPluginFileSync::Entry(void)
{
    bool run = true;
    while (run) {
        PollSessions();

        csEvent *event = EventPopWait(100);
        if (event == _CS_EVENT_NONE) continue;

        switch (event->GetId()) {
        case csEVENT_QUIT:
            run = false;
            break;
        }

        delete event;
    }

    return NULL;
}

void csPluginFileSync::PollSessions(void)
{
    fd_set fds;
    int max_fd = -1;
    struct timeval tv_timeout;

    FD_ZERO(&fds);
    memset(&tv_timeout, 0, sizeof(struct timeval));

    vector<csPluginFileSyncConfig *>::iterator si;
    for (si = server.begin(); si != server.end(); si++) {
        csSocket *skt = (*si)->GetSocket();
        int fd = skt->GetDescriptor();
        if (fd > max_fd) max_fd = fd;
        FD_SET(fd, &fds);
    }

    if (max_fd > -1) {
        int rc = select(max_fd + 1,
            &fds, NULL, NULL, &tv_timeout);
        switch (rc) {
        case -1:
            throw csException(errno, "select");
        case 0:
            break;
        default:
            for (si = server.begin(); si != server.end(); si++) {
                csSocket *skt = (*si)->GetSocket();
                if (FD_ISSET(skt->GetDescriptor(), &fds)) {
                    StartSession((*si));
                }
            }
        }
    }
}

void csPluginFileSync::StartSession(csPluginFileSyncConfig *config)
{
    csSocketAccept *skt_server = static_cast<csSocketAccept *>(config->GetSocket());
    csSocket *skt = skt_server->Accept();
    csPluginFileSyncSessionMaster *session = NULL;
    csPluginFileSyncConfig *new_config = new csPluginFileSyncConfig(skt);
    config->AddFile(new_config);
    session = new csPluginFileSyncSessionMaster(new_config, authkey, authkey_bits);
    master.push_back(session);
    session->Start();
}

void csPluginFileSync::SetAuthKey(const string &key)
{
    size_t i, j, byte;

    for (i = 0, j = 0; i < authkey_bytes; i += 2, j++) {
        if (sscanf(key.c_str() + i, "%2x", &byte) != 1)
            throw csException(EINVAL, "Authkey parse error");
        authkey[j] = (uint8_t)byte;
    }
}

csPluginFileSyncSession *csPluginFileSync::CreateSession(csSocket *skt, time_t interval)
{
    csPluginFileSyncSessionSlave *session;
    session = new csPluginFileSyncSessionSlave(skt,
        authkey, authkey_bits, interval);
    slave.push_back(session);
    return static_cast<csPluginFileSyncSession *>(session);
}

void csPluginXmlParser::ParseElementOpen(csXmlTag *tag)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "master") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->ParamExists("bind") ||
            tag->GetParamValue("bind").size() == 0)
            ParseError("parameter missing: " + tag->GetName());
        if (!tag->ParamExists("port") ||
            tag->GetParamValue("port").size() == 0)
            ParseError("parameter missing: " + tag->GetName());

        in_port_t port = (in_port_t)atoi(tag->GetParamValue("port").c_str());
        csSocketAccept *skt;
        skt = new csSocketAccept(tag->GetParamValue("bind"), port);
        csPluginFileSyncConfig *config = new csPluginFileSyncConfig(skt);
        _conf->parent->server.push_back(config);
        tag->SetData((void *)config);
    }
    else if ((*tag) == "slave") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->ParamExists("host") ||
            tag->GetParamValue("host").size() == 0)
            ParseError("parameter missing: " + tag->GetName());
        if (!tag->ParamExists("port") ||
            tag->GetParamValue("port").size() == 0)
            ParseError("parameter missing: " + tag->GetName());
        if (!tag->ParamExists("interval") ||
            tag->GetParamValue("interval").size() == 0)
            ParseError("parameter missing: " + tag->GetName());

        in_port_t port = (in_port_t)atoi(tag->GetParamValue("port").c_str());
        time_t interval = (time_t)atoi(tag->GetParamValue("interval").c_str());
        csSocketConnect *skt;
        skt = new csSocketConnect(tag->GetParamValue("host"), port);
        csPluginFileSyncSession *session;
        session = _conf->parent->CreateSession(skt, interval);
        tag->SetData((void *)session);
    }
    else if ((*tag) == "file") {
        if (!stack.size())
            ParseError("unexpected tag: " + tag->GetName());
        else if (*stack.back() == "master") {
            if (!tag->ParamExists("name") ||
                tag->GetParamValue("name").size() == 0)
                ParseError("parameter missing: " + tag->GetName());

            csPluginFileSyncFile *file = new csPluginFileSyncFile();
            file->name = new string(tag->GetParamValue("name"));
            tag->SetData((void *)file);
        }
        else if (*stack.back() == "slave") {
            if (!tag->ParamExists("name") ||
                tag->GetParamValue("name").size() == 0)
                ParseError("parameter missing: " + tag->GetName());

            csPluginFileSyncFile *file = new csPluginFileSyncFile();
            file->name = new string(tag->GetParamValue("name"));

            if (tag->ParamExists("presync") &&
                tag->GetParamValue("presync").size())
                file->presync = new string(tag->GetParamValue("presync"));

            if (tag->ParamExists("postsync") &&
                tag->GetParamValue("postsync").size())
                file->postsync = new string(tag->GetParamValue("postsync"));

            tag->SetData((void *)file);
        }
        else
            ParseError("unexpected tag: " + tag->GetName());
    }
}

void csPluginXmlParser::ParseElementClose(csXmlTag *tag)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "authkey") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->GetText().size())
            ParseError("missing value for tag: " + tag->GetName());

        _conf->parent->SetAuthKey(tag->GetText());
    }
    else if ((*tag) == "file") {
        if (!stack.size())
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->GetText().size())
            ParseError("missing value for tag: " + tag->GetName());
        else if ((*stack.back()) == "master") {
            struct csPluginFileSyncFile *file;
            file = (struct csPluginFileSyncFile *)tag->GetData();
            file->path = new string(tag->GetText());
            csPluginFileSyncConfig *config;
            config = static_cast<csPluginFileSyncConfig *>(stack.back()->GetData());
            try {
                config->AddFile(file);
            } catch (csPluginFileSyncSessionDuplicateFile &e) {
                csLog::Log(csLog::Error,
                    "%s: Duplicate file definition: %s",
                    _conf->parent->name.c_str(), e.what());
                delete file;
            }
        }
        else if ((*stack.back()) == "slave") {
            struct csPluginFileSyncFile *file;
            file = (struct csPluginFileSyncFile *)tag->GetData();
            file->path = new string(tag->GetText());
            csPluginFileSyncSession *session;
            session = static_cast<csPluginFileSyncSession *>(stack.back()->GetData());
            try {
                session->config->AddFile(file);
            } catch (csPluginFileSyncSessionDuplicateFile &e) {
                csLog::Log(csLog::Error,
                    "%s: Duplicate file definition: %s",
                    _conf->parent->name.c_str(), e.what());
                delete file;
            }
        }
        else
            ParseError("unexpected tag: " + tag->GetName());
    }
}

csPluginInit(csPluginFileSync);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
