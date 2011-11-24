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

#include <iomanip>
#include <sstream>

#include <clearsync/csplugin.h>

#include <sys/select.h>
#include <sys/stat.h>

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
#include <openssl/sha.h>

#define csPluginFileSyncMasterTerm  csEVENT_USER + 1
#define csPluginFileSyncAuthKeyBits 256
#ifndef csPluginFileSyncSudo
#define csPluginFileSyncSudo        "/usr/bin/sudo"
#endif

__attribute__ ((unused)) static rs_result rs_cb_read(
    void *ctx, char *buffer, size_t length, size_t *bytes_read)
{
    csSocket *skt = static_cast<csSocket *>(ctx);

    try {
        skt->Read(*bytes_read, (uint8_t *)buffer);
    } catch (csException &e) {
    }

    return RS_DONE;
}

__attribute__ ((unused)) static rs_result rs_cb_basis(
    void *ctx, char *buffer, size_t length, off_t offset, size_t *bytes_read)
{
//    csSocket *skt = static_cast<csSocket *>(ctx);

    try {
    } catch (csException &e) {
    }

    return RS_DONE;
}

__attribute__ ((unused)) static rs_result rs_cb_write(
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
    union {
        struct {
            uint8_t id;
            uint8_t arg;
            uint8_t blk;
            uint8_t pad;
        } hdr;
        uint32_t hdr_union;
    };

    size_t size_buffer;
    size_t size_max_payload;

    uint8_t *buffer;
    uint8_t *sha;
    uint8_t *header;
    uint8_t *payload;

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
    enum CommandType {
        Command_stat,
        Command_sha1sum
    };

    csPluginFileSyncFile()
    : name(NULL), path(NULL), presync(NULL), postsync(NULL),
    user(NULL), group(NULL), rx_stat(NULL), rx_sha1sum(NULL) { Initialize(); };
    csPluginFileSyncFile(csPluginFileSyncFile *src);
    virtual ~csPluginFileSyncFile() {
        if (name) delete name;
        if (path) delete path;
        if (presync) delete presync;
        if (postsync) delete postsync;
        if (user) delete user;
        if (group) delete group;
        if (rx_stat) delete rx_stat;
        if (rx_sha1sum) delete rx_sha1sum;
    };

    void Initialize(void) {
        memset(sha, 0, SHA_DIGEST_LENGTH);
        memset(&st_info, 0, sizeof(struct stat));
        rx_stat = new csRegEx(
            "^([0-7]{3,4}):([a-z_][a-z0-9_-]*[$]?):([a-z_][a-z0-9_-]*[$]?)",
            REG_EXTENDED | REG_ICASE | REG_NEWLINE);
        rx_sha1sum = new csRegEx(
            "^([a-f0-9]{40})",
            REG_EXTENDED | REG_ICASE | REG_NEWLINE);
    };
    void ExecuteExternal(CommandType type);
    void Refresh(void);

    string *name;
    string *path;
    string *presync;
    string *postsync;
    string *user;
    string *group;
    uint8_t sha[SHA_DIGEST_LENGTH];
    struct stat st_info;
    csRegEx *rx_stat;
    csRegEx *rx_sha1sum;
};

csPluginFileSyncFile::csPluginFileSyncFile(csPluginFileSyncFile *src)
    : name(NULL), path(NULL), presync(NULL), postsync(NULL),
    user(NULL), group(NULL), rx_stat(NULL), rx_sha1sum(NULL)
{
    if (src->name) name = new string(*(src->name));
    if (src->path) path = new string(*(src->path));
    if (src->presync) presync = new string(*(src->presync));
    if (src->postsync) postsync = new string(*(src->postsync));
    Initialize();
}

void csPluginFileSyncFile::ExecuteExternal(CommandType type)
{
    int rc;
    ostringstream os;
    vector<string> output;
    vector<string>::iterator i;

    if (type == Command_stat) {
        os << csPluginFileSyncSudo << " ";
        os << "/usr/bin/stat -c '%a:%U:%G' ";
        os << "\"" << path->c_str() << "\" ";
        os << "2>/dev/null";

        rc = ::csExecute(os.str(), output);
        csLog::Log(csLog::Debug,
            "Execute: %s = %d", os.str().c_str(), rc);

        if (rc != 0 || output.size() == 0)
            throw csException(EINVAL, path->c_str());

        i = output.begin();
        if (rx_stat->Execute((*i).c_str()) == REG_NOMATCH) {
            csLog::Log(csLog::Debug, "No match: %s", (*i).c_str());
            throw csException(EINVAL, path->c_str());
        }

        const char *st_mode = NULL;
        const char *st_user = NULL;
        const char *st_group = NULL;
        try {
            st_mode = rx_stat->GetMatch(1);
            st_user = rx_stat->GetMatch(2);
            st_group = rx_stat->GetMatch(3);
        } catch (csException &e) {
        }

        if (st_mode == NULL ||
            st_user == NULL || st_group == NULL)
            throw csException(EINVAL, path->c_str());

        user = new string(st_user);
        group = new string(st_group);
        st_info.st_mode = (mode_t)strtol(st_mode, NULL, 8);
    }
    else if (type == Command_sha1sum) {
        os << csPluginFileSyncSudo << " ";
        os << "/usr/bin/sha1sum ";
        os << "\"" << path->c_str() << "\" ";
        os << "2>/dev/null";

        output.clear();
        rc = ::csExecute(os.str(), output);
        csLog::Log(csLog::Debug,
            "Execute: %s = %d", os.str().c_str(), rc);

        if (rc != 0 || output.size() == 0)
            throw csException(EINVAL, path->c_str());

        i = output.begin();
        if (rx_sha1sum->Execute((*i).c_str()) == REG_NOMATCH) {
            csLog::Log(csLog::Debug, "No match: %s", (*i).c_str());
            throw csException(EINVAL, path->c_str());
        }

        const char *sha1sum = NULL;
        try {
            sha1sum = rx_sha1sum->GetMatch(1);
        } catch (csException &e) {
        }

        if (sha1sum == NULL)
            throw csException(EINVAL, path->c_str());

        ::csHexToBinary(sha1sum, sha, SHA_DIGEST_LENGTH);
    }
    else throw csException(EINVAL, "Invalid external command type");
}

void csPluginFileSyncFile::Refresh(void)
{
    memset(sha, 0, SHA_DIGEST_LENGTH);

    if (stat(path->c_str(), &st_info) != 0 && errno != EACCES)
        throw csException(errno, path->c_str());

    if (errno == EACCES) {
        ExecuteExternal(Command_stat);
        ExecuteExternal(Command_sha1sum);
    }
    else {
        try {
            ::csSHA1(*path, sha);
        } catch (csException &e) {
            ExecuteExternal(Command_sha1sum);
        }

        user = new string("");
        group = new string("");
        ::csGetUserName(st_info.st_uid, *user);
        ::csGetGroupName(st_info.st_uid, *group);
    }
#if 0
    csLog::Log(csLog::Debug,
        "user: %s, group: %s, mode: %lo, sha:",
        user->c_str(), group->c_str(),
        (unsigned long)(~S_IFMT & st_info.st_mode));
    csHexDump(stderr, sha, SHA_DIGEST_LENGTH);
#endif
}

class csPluginFileSyncSession;
class csPluginFileSyncSessionMaster;
class csPluginFileSyncSessionSlave;
class csPluginFileSyncConfig
{
public:
    csPluginFileSyncConfig(csSocket *skt);
    virtual ~csPluginFileSyncConfig();

    void AddFile(csPluginFileSyncFile *add_file);
    void AddFile(csPluginFileSyncConfig *config);
    size_t GetFileCount(void) { return file.size(); };
    inline csSocket *GetSocket(void) { return skt; };
    inline void SetSocket(csSocket *skt) { this->skt = skt; };

protected:
    friend class csPluginFileSyncSession;
    friend class csPluginFileSyncSessionMaster;
    friend class csPluginFileSyncSessionSlave;

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
    enum PacketId {
        idNone,
        idOk,
        idFileSync,
        idFileRequest,
        idData,
        idException,
        idTerminate,
    };

    enum PacketArg {
        argNone,
        argEndOfFile,
    };

    csPluginFileSyncSession(csSocket *skt,
        const uint8_t *authkey, size_t authkey_bits)
        : csThread(), config(NULL) {
        config = new csPluginFileSyncConfig(skt);
        config->skt->SetWaitAll();
        InitializePacket(authkey, authkey_bits);
    };
    csPluginFileSyncSession(csPluginFileSyncConfig *config,
        const uint8_t *authkey, size_t authkey_bits)
        : csThread(), config(config) {
        config->skt->SetWaitAll();
        InitializePacket(authkey, authkey_bits);
    };
    virtual ~csPluginFileSyncSession();

    virtual void *Entry(void) = 0;

    ssize_t ReadPacket(PacketId &id, PacketArg &arg);
    ssize_t WritePacket(PacketId id, PacketArg arg = argNone,
        const uint8_t *buffer = NULL, size_t length = 0);

    csPluginFileSyncConfig *config;

protected:
    void InitializePacket(const uint8_t *authkey, size_t authkey_bits);
    virtual void Run(void) = 0;
    virtual void SynchronizeFile(csPluginFileSyncFile *file) = 0;

    struct csPluginFileSyncPacket pkt;
};

csPluginFileSyncSession::~csPluginFileSyncSession()
{
    delete config;
    delete [] pkt.buffer;
}

void csPluginFileSyncSession::InitializePacket(const uint8_t *authkey, size_t authkey_bits)
{
    pkt.size_buffer = 256 * csPluginFileSyncAuthKeyBits;
    pkt.size_max_payload = pkt.size_buffer - (sizeof(uint32_t) + SHA_DIGEST_LENGTH);
    if (pkt.size_max_payload % csPluginFileSyncAuthKeyBits)
        pkt.size_max_payload -= pkt.size_max_payload % csPluginFileSyncAuthKeyBits;

    //csLog::Log(csLog::Debug, "max payload size: %d", pkt.size_max_payload);

    pkt.hdr_union = 0;
    pkt.buffer = new uint8_t[pkt.size_buffer];
    pkt.sha = pkt.buffer;
    pkt.header = pkt.buffer + SHA_DIGEST_LENGTH;
    pkt.payload = pkt.header + sizeof(uint32_t);

    if (RAND_bytes(pkt.buffer, pkt.size_buffer) == 0)
        throw csException(EINVAL, "Error gathering random bytes");

    //csLog::Log(csLog::Debug, "Encrypt/decrypt key (%d bits):", authkey_bits);
    //csHexDump(stderr, authkey, 32);

    if (AES_set_encrypt_key(authkey, authkey_bits, &pkt.authkey_encrypt) < 0)
        throw csException(EINVAL, "Error setting AES encryption key");
    if (AES_set_decrypt_key(authkey, authkey_bits, &pkt.authkey_decrypt) < 0)
        throw csException(EINVAL, "Error setting AES decryption key");
}

ssize_t csPluginFileSyncSession::ReadPacket(PacketId &id, PacketArg &arg)
{
    size_t length = csPluginFileSyncAuthKeyBits;

    try {
        config->skt->Read(length, pkt.buffer);
    } catch (csException &e) {
        csLog::Log(csLog::Error,
            "Error reading packet header: %s", e.what());
        return -1;
    }

    //csLog::Log(csLog::Debug, "ReadPacket: raw header: %ld", length);
    //csHexDump(stderr, pkt.buffer, csPluginFileSyncAuthKeyBits);

    for (uint32_t i = 0; i < csPluginFileSyncAuthKeyBits; i += AES_BLOCK_SIZE)
        AES_decrypt(pkt.buffer + i, pkt.buffer + i, &pkt.authkey_decrypt);

    //csLog::Log(csLog::Debug, "ReadPacket: decrypted");
    //csHexDump(stderr, pkt.buffer, csPluginFileSyncAuthKeyBits);

    uint8_t sha[SHA_DIGEST_LENGTH];
    SHA1(pkt.header, csPluginFileSyncAuthKeyBits - SHA_DIGEST_LENGTH, sha);

    if (memcmp(pkt.sha, sha, SHA_DIGEST_LENGTH)) {
        csLog::Log(csLog::Error, "Packet authentication failure.");
        return -1;
    }

    memcpy(&pkt.hdr_union, pkt.header, sizeof(uint32_t));
    //csLog::Log(csLog::Debug,
    //    "ReadPacket: length: %d, blk: %hhu, pad: %hhu, union: 0x%08x",
    //    pkt.hdr.blk * csPluginFileSyncAuthKeyBits - pkt.hdr.pad,
    //    pkt.hdr.blk, pkt.hdr.pad, pkt.hdr_union);
    //csHexDump(stderr, pkt.buffer, csPluginFileSyncAuthKeyBits);

    for (uint8_t i = 1; i < pkt.hdr.blk; i++) {
        length = csPluginFileSyncAuthKeyBits;
        try {
            config->skt->Read(length,
                pkt.buffer + (csPluginFileSyncAuthKeyBits * i));
        } catch (csException &e) {
            csLog::Log(csLog::Error,
                "Error reading packet payload: %s", e.what());
            return -1;
        }
        if (length != csPluginFileSyncAuthKeyBits) {
            csLog::Log(csLog::Error,
                "Error reading payload block: %d", length);
            return -1;
        }
    }

    id = (PacketId)pkt.hdr.id;
    arg = (PacketArg)pkt.hdr.arg;
    length = pkt.hdr.blk * csPluginFileSyncAuthKeyBits;

    for (uint32_t i = csPluginFileSyncAuthKeyBits;
        i < length; i += AES_BLOCK_SIZE)
        AES_decrypt(pkt.buffer + i, pkt.buffer + i, &pkt.authkey_decrypt);

    length -= (sizeof(uint32_t) + SHA_DIGEST_LENGTH + pkt.hdr.pad);

    return (ssize_t)length;
}

ssize_t csPluginFileSyncSession::WritePacket(
    PacketId id, PacketArg arg, const uint8_t *buffer, size_t length)
{
    if (length > pkt.size_max_payload)
        throw csException(EINVAL, "Packet payload too large");
    else if (length > 0) {
        if (buffer != NULL)
            memcpy(pkt.payload, buffer, length);
    }

    pkt.hdr.id = (uint8_t)id;
    pkt.hdr.arg = (uint8_t)arg;
    length += sizeof(uint32_t) + SHA_DIGEST_LENGTH;
    pkt.hdr.pad = (uint8_t)(csPluginFileSyncAuthKeyBits - (length % csPluginFileSyncAuthKeyBits));
    pkt.hdr.blk = (uint8_t)((length + pkt.hdr.pad) / csPluginFileSyncAuthKeyBits);
    memcpy(pkt.header, &pkt.hdr_union, sizeof(uint32_t));

    SHA1(pkt.header, csPluginFileSyncAuthKeyBits - SHA_DIGEST_LENGTH, pkt.sha);

    //csLog::Log(csLog::Debug,
    //    "WritePacket: length: %d, blk: %hhu, pad: %hhu, union: 0x%08x",
    //    length, pkt.hdr.blk, pkt.hdr.pad, pkt.hdr_union);
    //csHexDump(stderr, pkt.buffer, csPluginFileSyncAuthKeyBits);

    uint32_t total = pkt.hdr.blk * csPluginFileSyncAuthKeyBits;
    for (uint32_t i = 0; i < total; i += AES_BLOCK_SIZE)
        AES_encrypt(pkt.buffer + i, pkt.buffer + i, &pkt.authkey_encrypt);

    //csHexDump(stderr, pkt.buffer, csPluginFileSyncAuthKeyBits);
    size_t bytes = pkt.hdr.blk * csPluginFileSyncAuthKeyBits;
    config->skt->Write(bytes, pkt.buffer);

    return (ssize_t)bytes;
}

class csPluginFileSyncSessionMaster : public csPluginFileSyncSession
{
public:
    csPluginFileSyncSessionMaster(csEventClient *parent,
        csPluginFileSyncConfig *config, const uint8_t *authkey, size_t authkey_bits)
        : csPluginFileSyncSession(config, authkey, authkey_bits),
        parent(parent) { };
    virtual ~csPluginFileSyncSessionMaster() { Join(); };
    virtual void *Entry(void);

protected:
    virtual void Run(void);
    virtual void SynchronizeFile(csPluginFileSyncFile *file);

    csEventClient *parent;
};

void *csPluginFileSyncSessionMaster::Entry(void)
{
    try {
        Run();
    } catch (csException &e) {
    }

    EventDispatch(new csEvent(csPluginFileSyncMasterTerm), parent);
/*
    csEvent *event = EventPopWait();

    switch (event->GetId()) {
    case csEVENT_QUIT:
        run = false;
        break;
    }

    delete event;
*/
    return NULL;
}

void csPluginFileSyncSessionMaster::Run(void)
{
    PacketId id;
    PacketArg arg;
    ssize_t length;
    map<string, csPluginFileSyncFile *>::iterator i;

    while (true) {
        length = ReadPacket(id, arg);

        if (id != idFileRequest) {
            if (id == idTerminate) break;
            throw csException(EINVAL, "Invalid packet ID");
        }
        if (length <= SHA_DIGEST_LENGTH)
            throw csException(EINVAL, "Invalid packet payload length");

        i = config->file.find(
            string(
                (const char *)pkt.payload + SHA_DIGEST_LENGTH,
                length - SHA_DIGEST_LENGTH));
        if (i != config->file.end()) {
            try {
                i->second->Refresh();
            } catch (csException &e) {
                WritePacket(idException);
            }
            if (memcmp(i->second->sha, pkt.payload, SHA_DIGEST_LENGTH)) {
                size_t bytes;
                uint8_t *ptr = pkt.payload + SHA_DIGEST_LENGTH;

                memcpy(pkt.payload, i->second->sha, SHA_DIGEST_LENGTH);

                bytes = i->second->user->size();
                memcpy(ptr, &bytes, sizeof(size_t));
                memcpy(ptr + sizeof(size_t), i->second->user->c_str(), bytes);

                bytes = i->second->group->size();
                ptr += sizeof(size_t) + i->second->user->size();
                memcpy(ptr, &bytes, sizeof(size_t));
                memcpy(ptr + sizeof(size_t), i->second->group->c_str(), bytes);

                ptr += sizeof(size_t) + i->second->group->size();
                memcpy(ptr, &i->second->st_info.st_mode, sizeof(mode_t));

                length = SHA_DIGEST_LENGTH +
                    sizeof(size_t) * 2 + sizeof(mode_t) +
                    i->second->user->size() + i->second->group->size();
                WritePacket(idFileSync, argNone, NULL, length);

                id = idNone;
                length = ReadPacket(id, arg);
                if (id != idOk) continue;

                SynchronizeFile(i->second);
            }
            else WritePacket(idOk);
        }
        else WritePacket(idException);
    }
}

void csPluginFileSyncSessionMaster::SynchronizeFile(csPluginFileSyncFile *file)
{
    int (*pfclose)(FILE *fh) = &fclose;
    FILE *fh = fopen(file->path->c_str(), "r");
    if (fh == NULL && errno != EACCES)
        throw csException(errno, file->path->c_str());
    else {
        ostringstream os;
        os << csPluginFileSyncSudo << " ";
        os << "/bin/cat \"" << *(file->path) << "\" ";
        os << "2>/dev/null";
        
        pfclose = &pclose;
        fh = popen(os.str().c_str(), "r");
        if (fh == NULL)
            throw csException(errno, file->path->c_str());
    }

    size_t chunk_size = ::csGetPageSize();
    while (!feof(fh)) {
        size_t bytes = fread(pkt.payload, 1, chunk_size, fh);
        if (bytes == 0) {
            if (!feof(fh) && ferror(fh)) {
                fclose(fh);
                csLog::Log(csLog::Error,
                    "File read error: %s", file->name->c_str());
                WritePacket(idException);
                return;
            }
            break;
        }
        WritePacket(idData, argNone, NULL, bytes);
    }

    if (pfclose(fh) != 0) {
        csLog::Log(csLog::Error,
            "File/pipe close failure: %s", file->name->c_str());
        WritePacket(idException);
    }
    else
        WritePacket(idData, argEndOfFile);
}

class csPluginFileSyncSessionSlave : public csPluginFileSyncSession
{
public:
    csPluginFileSyncSessionSlave(
        csSocket *skt, const uint8_t *authkey, size_t authkey_bits,
        time_t tv_interval);
    virtual ~csPluginFileSyncSessionSlave();
    virtual void *Entry(void);

protected:
    time_t tv_interval;
    csTimer *timer;
    map<int, char *> tmp_file;

    virtual void Run(void);
    virtual void SynchronizeFile(csPluginFileSyncFile *file);
    inline void CloseTemporaryFiles(void) {
        for (map<int, char *>::iterator i = tmp_file.begin();
            i != tmp_file.end(); i++) {
            close(i->first);
            unlink(i->second);
            delete [] i->second;
        }
        tmp_file.clear();
    };
};

static cstimer_id_t csSlaveSessionInterval = 1;

csPluginFileSyncSessionSlave::csPluginFileSyncSessionSlave(
    csSocket *skt, const uint8_t *authkey, size_t authkey_bits,
    time_t tv_interval)
    : csPluginFileSyncSession(skt, authkey, authkey_bits),
    tv_interval(tv_interval), timer(NULL)
{
    cstimer_id_t id = __sync_fetch_and_add(&csSlaveSessionInterval, 1);
    timer = new csTimer(id, tv_interval, tv_interval, this);
}

csPluginFileSyncSessionSlave::~csPluginFileSyncSessionSlave()
{
    Join();

    if (timer) delete timer;
    CloseTemporaryFiles();
}

void *csPluginFileSyncSessionSlave::Entry(void)
{
    map<int, char *>::iterator i;
    csSocketConnect *skt = static_cast<csSocketConnect *>(config->GetSocket());
    skt->SetTimeout(30);

    timer->Start();

    bool run = true;
    while (run) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            run = false;
            break;

        case csEVENT_TIMER:
            timer->Stop();

            try {
                skt->Connect();
                try {
                    Run();
                } catch (csException &e) {
                    csLog::Log(csLog::Error,
                        "Session exception: %s: %s",
                        e.estring.c_str(), e.what());
                }
            } catch (csSocketTimeout &e) {
                csLog::Log(csLog::Warning,
                    "Time-out while connecting");
            } catch (csException &e) {
                csLog::Log(csLog::Error,
                    "Error establishing connection: %s", e.what());
            }

            CloseTemporaryFiles();

            skt->Close();
            skt->Create();

            timer->SetValue(tv_interval);
            timer->SetInterval(tv_interval);
            timer->Start();
            break;
        }

        delete event;
    }

    return NULL;
}

void csPluginFileSyncSessionSlave::Run()
{
    PacketId id;
    PacketArg arg;
    ssize_t length;
    map<string, csPluginFileSyncFile *>::iterator i;

    for (i = config->file.begin(); i != config->file.end(); i++) {
        try {
            i->second->Refresh();
        } catch (csException &e) {
            csLog::Log(csLog::Debug, "Refresh: %s: %s (%s)",
                i->first.c_str(), e.what(), e.estring.c_str());
        }

        memcpy(pkt.payload, i->second->sha, SHA_DIGEST_LENGTH);
        memcpy(pkt.payload + SHA_DIGEST_LENGTH,
            i->first.c_str(), i->first.size());
        WritePacket(idFileRequest, argNone,
            NULL, SHA_DIGEST_LENGTH + i->first.size());

        id = idNone;
        length = ReadPacket(id, arg);
        if (id == idException) {
            csLog::Log(csLog::Warning,
                "Remote file exception: %s", i->first.c_str());
            continue;
        }
        if (id == idOk) continue;
        else if (id == idFileSync)
            SynchronizeFile(i->second);
        else if (id == idTerminate) return;
        else {
            csLog::Log(csLog::Error,
                "Unexpected packet id: 0x%02x", id);
            break;
        }
    }

    WritePacket(idTerminate);
}

void csPluginFileSyncSessionSlave::SynchronizeFile(csPluginFileSyncFile *file)
{
    size_t user_size, group_size;
    uint8_t *ptr = pkt.payload + SHA_DIGEST_LENGTH + sizeof(size_t);

    memcpy(file->sha, pkt.payload, SHA_DIGEST_LENGTH);
    memcpy(&user_size, pkt.payload + SHA_DIGEST_LENGTH, sizeof(size_t));
    memcpy(&group_size,
        pkt.payload + SHA_DIGEST_LENGTH + sizeof(size_t) + user_size, sizeof(size_t));

    string user((const char *)ptr, user_size);
    ptr += sizeof(size_t) + user_size;
    string group((const char *)ptr, group_size);
    ptr += group_size;
    mode_t mode;
    memcpy(&mode, ptr, sizeof(mode_t));

    uid_t uid;
    try {
        uid = ::csGetUserId(user);
    } catch (csException &e) {
        csLog::Log(csLog::Error,
            "User not found for file: %s", file->name->c_str());
        WritePacket(idException);
        return;
    }
    gid_t gid;
    try {
        gid = ::csGetUserId(group);
    } catch (csException &e) {
        csLog::Log(csLog::Error,
            "Group not found for file: %s", file->name->c_str());
        WritePacket(idException);
        return;
    }

//    csLog::Log(csLog::Debug, "user: %s, group: %s, mode: %lo",
//        user.c_str(), group.c_str(), mode);

    WritePacket(idOk);
    CloseTemporaryFiles();

    char *tmp = new char[256];
    strcpy(tmp, "/var/tmp/clearsync/filesyncXXXXXX");
    int fd = mkstemp(tmp);
    if (fd < 0) {
        delete [] tmp;
        throw csException(errno, "mkstemp");
    }
    tmp_file[fd] = tmp;

    for ( ;; ) {
        PacketId id = idNone;
        PacketArg arg = argNone;
        ssize_t length = ReadPacket(id, arg);

        if (id == idData) {
            if (arg == argEndOfFile) break;
            ssize_t bytes = write(fd, pkt.payload, length);
            if (bytes != length)
                throw csException(errno, "write");
        }
        else if (id == idException) {
            csLog::Log(csLog::Warning,
                "Remote file exception: %s", file->name->c_str());
            return;
        }
    }

    close(fd);

    uint8_t sha[SHA_DIGEST_LENGTH];
    ::csSHA1(tmp, sha);

    if (memcmp(file->sha, sha, SHA_DIGEST_LENGTH)) {
        csLog::Log(csLog::Error,
            "File integrity check failed for: %s",
            file->name->c_str());
        return;
    }

    int rc;

    ostringstream os;
    os << csPluginFileSyncSudo << " ";
    os << "/bin/chown " << user << ":" << group << " ";
    os << "\"" << tmp << "\" ";
    os << "2>/dev/null";
    
    rc = ::csExecute(os.str());
    csLog::Log(csLog::Debug,
        "Execute: %s = %d", os.str().c_str(), rc);
    if (rc != 0) {
        csLog::Log(csLog::Error,
            "Unable to set file ownership for: %s",
            file->name->c_str());
        return;
    }

    os.str("");
    os << csPluginFileSyncSudo << " ";
    os << "/bin/chmod ";
    os << oct << (~S_IFMT & mode) << " ";
    os << "\"" << tmp << "\" ";
    os << "2>/dev/null";

    rc = ::csExecute(os.str());
    csLog::Log(csLog::Debug,
        "Execute: %s = %d", os.str().c_str(), rc);
    if (rc != 0) {
        csLog::Log(csLog::Error,
            "Unable to set file mode for: %s",
            file->name->c_str());
        return;
    }

    os.str("");
    os << csPluginFileSyncSudo << " ";
    os << "/bin/mv ";
    os << "\"" << tmp << "\" ";
    os << "\"" << file->path->c_str() << "\" ";
    os << "2>/dev/null";

    rc = ::csExecute(os.str());
    csLog::Log(csLog::Debug,
        "Execute: %s = %d", os.str().c_str(), rc);
    if (rc != 0) {
        csLog::Log(csLog::Error,
            "Unable to move file: %s",
            file->name->c_str());
        return;
    }
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

class csPluginFileSync : public csPlugin
{
public:
    csPluginFileSync(const string &name,
        csEventClient *parent, size_t stack_size);
    virtual ~csPluginFileSync();

    virtual void SetConfigurationFile(const string &conf_filename);
    virtual void ValidateConfiguration(void);

    virtual void *Entry(void);

protected:
    friend class csPluginXmlParser;

    inline void PollSessions(void);
    inline void StartSession(csPluginFileSyncConfig *session);
    inline void DestroySession(csEventClient *client);

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

void csPluginFileSync::ValidateConfiguration(void)
{
    vector<csPluginFileSyncConfig *>::iterator i;
    for (i = server.begin(); i != server.end(); i++) {
        if ((*i)->GetFileCount() > 0) continue;
        throw csException(EINVAL, "No files defined");
    }
    vector<csPluginFileSyncSessionSlave *>::iterator si;
    for (si = slave.begin(); si != slave.end(); si++) {
        if ((*si)->config->GetFileCount() > 0) continue;
        throw csException(EINVAL, "No files defined");
    }

}

void *csPluginFileSync::Entry(void)
{
    sigset_t signal_set;
    sigemptyset(&signal_set);
    sigaddset(&signal_set, SIGCHLD);
    pthread_sigmask(SIG_UNBLOCK, &signal_set, NULL);

    vector<csPluginFileSyncSessionSlave *>::iterator si;
    for (si = slave.begin(); si != slave.end(); si++)
        (*si)->Start();

    bool run = true;
    while (run) {
        PollSessions();

        csEvent *event = EventPopWait(100);
        if (event == _CS_EVENT_NONE) continue;

        switch (event->GetId()) {
        case csEVENT_QUIT:
            run = false;
            break;

        case csPluginFileSyncMasterTerm:
            DestroySession(event->GetSource());
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
                if (FD_ISSET(skt->GetDescriptor(), &fds))
                    StartSession((*si));
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

    session = new csPluginFileSyncSessionMaster(
        this, new_config, authkey, authkey_bits);
    master.push_back(session);
    session->Start();
}

void csPluginFileSync::DestroySession(csEventClient *client)
{
    csPluginFileSyncSessionMaster *session;
    session = static_cast<csPluginFileSyncSessionMaster *>(client);
    vector<csPluginFileSyncSessionMaster *>::iterator i;
    for (i = master.begin(); i != master.end(); i++) {
        if ((*i) != session) continue;
        delete (*i);
        master.erase(i);
        csLog::Log(csLog::Debug, "Destroyed master session.");
        return;
    }
    csLog::Log(csLog::Warning, "Failed to destroy master session, not found.");
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
        csPluginFileSyncSessionSlave *session;
        session = new csPluginFileSyncSessionSlave(skt,
            _conf->parent->authkey, _conf->parent->authkey_bits, interval);
        _conf->parent->slave.push_back(session);
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

        csHexToBinary(tag->GetText(),
            _conf->parent->authkey, _conf->parent->authkey_bytes);
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

void csPluginConf::Reload(void)
{
    csConf::Reload();
    parser->Parse();
    parent->ValidateConfiguration();
}

csPluginInit(csPluginFileSync);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
