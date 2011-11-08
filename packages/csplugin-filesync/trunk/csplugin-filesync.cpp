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

#include <sys/types.h>
#include <sys/socket.h>

#include <netinet/in.h>

#include <string.h>

#include <clearsync/csplugin.h>

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

#include "csplugin-socket.h"

#define csPluginFileSyncAuthKeyBits 256

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

struct csPluginFileSyncPacket
{
    uint32_t hdr_id:8, hdr_pad:8, hdr_len:16;
    uint8_t *buffer;
	AES_KEY authkey_encrypt;
	AES_KEY authkey_decrypt;
};

class csPluginFileSyncSession : public csThread
{
public:
    csPluginFileSyncSession(const uint8_t *authkey, size_t authkey_bits);
    virtual ~csPluginFileSyncSession();

    virtual void *Entry(void);

protected:
    struct csPluginFileSyncPacket pkt;
};

csPluginFileSyncSession::csPluginFileSyncSession(
    const uint8_t *authkey, size_t authkey_bits)
    : csThread()
{
    pkt.hdr_id = 0;
    pkt.hdr_pad = 0;
    pkt.hdr_len = 0;
    pkt.buffer = new uint8_t[getpagesize() * 2];

    if (AES_set_encrypt_key(authkey, authkey_bits, &pkt.authkey_encrypt) < 0)
        throw csException(EINVAL, "Error setting AES encryption key");
    if (AES_set_decrypt_key(authkey, authkey_bits, &pkt.authkey_decrypt) < 0)
        throw csException(EINVAL, "Error setting AES decryption key");
}

csPluginFileSyncSession::~csPluginFileSyncSession()
{
    Join();

    delete [] pkt.buffer;
}

void *csPluginFileSyncSession::Entry(void)
{
    return NULL;
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
    void SetAuthKey(const string &key);

    csPluginConf *conf;
    uint8_t *authkey;
	size_t authkey_bits;
	size_t authkey_bytes;
    struct csPluginFileSyncPacket pkt;
};

csPluginFileSync::csPluginFileSync(const string &name,
    csEventClient *parent, size_t stack_size)
    : csPlugin(name, parent, stack_size), conf(NULL),
    authkey_bits(csPluginFileSyncAuthKeyBits),
    authkey_bytes(csPluginFileSyncAuthKeyBits / 8)
{
    authkey = new uint8_t[authkey_bytes];

    csLog::Log(csLog::Debug, "%s: Initialized.", name.c_str());
}

csPluginFileSync::~csPluginFileSync()
{
    Join();

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
    csLog::Log(csLog::Info, "%s: Running.", name.c_str());
    csTimer *timer = new csTimer(500, 3, 3, this);

    unsigned long loops = 0ul;
    GetStateVar("loops", loops);
    csLog::Log(csLog::Debug, "%s: loops: %lu", name.c_str(), loops);

    for (bool run = true; run; loops++) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            csLog::Log(csLog::Info, "%s: Terminated.", name.c_str());
            run = false;
            break;

        case csEVENT_TIMER:
            csLog::Log(csLog::Debug, "%s: Tick: %lu", name.c_str(),
                static_cast<csTimerEvent *>(event)->GetTimer()->GetId());
            break;
        }

        delete event;
    }

    delete timer;

    SetStateVar("loops", loops);
    csLog::Log(csLog::Debug, "%s: loops: %lu", name.c_str(), loops);

    return NULL;
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

void csPluginXmlParser::ParseElementOpen(csXmlTag *tag)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);
}

void csPluginXmlParser::ParseElementClose(csXmlTag *tag)
{
    string text = tag->GetText();
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "authkey") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!text.size())
            ParseError("missing value for tag: " + tag->GetName());

        _conf->parent->SetAuthKey(text);
    }
}

csPluginInit(csPluginFileSync);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
