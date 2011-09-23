///////////////////////////////////////////////////////////////////////////////
//
// SUVA version 3
// Copyright (C) 2001-2010 ClearCenter
//
///////////////////////////////////////////////////////////////////////////////
//
// This project uses OpenSSL (http://openssl.org) for RSA, PEM, AES, RNG, DSO,
// and MD5 support.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the
// Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful, but 
// WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
// or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program; if not, write to the Free Software Foundation, Inc.,
// 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif
#include <string>
#include <iostream>
#include <stdexcept>
#include <sstream>
#include <map>
#include <vector>

#include <sys/time.h>

#include <unistd.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <errno.h>
#include <pthread.h>
#include <signal.h>
#include <expat.h>

#ifdef HAVE_SYSLOG_H
#include <syslog.h>
#endif

#ifdef HAVE_NETDB_H
#include <netdb.h>
#endif

#include <openssl/aes.h>
#include <openssl/md5.h>
#include <openssl/bio.h>
#include <openssl/pem.h>
#include <openssl/dso.h>

#include "svoutput.h"
#include "svobject.h"
#include "svconf.h"
#include "svcrypto.h"
#include "svpacket.h"
#include "svsocket.h"
#include "svplugin.h"
#include "svkeyring.h"
#include "svpool.h"
#include "svevent.h"
#include "svthread.h"
#include "svexec.h"
#include "svsession.h"
#include "svsignal.h"
#include "svservice.h"
#include "svutil.h"
#include "svkeypoll.h"

svKeyPollResult::svKeyPollResult(uint8_t *digest, RSA *key)
	: svObject("svKeyPollResult"), score(1), key(key)
{
	memcpy(this->digest, digest, MD5_DIGEST_LENGTH);
}

svKeyPollResult::~svKeyPollResult()
{
	if (key) RSA_free(key);
}

svThreadKeyPoll::svThreadKeyPoll(const string &org)
	: svThread("svThreadKeyPoll"), org(org), skt_count(0)
{
	svConfOrganization *conf_org;
	conf_org = svService::GetConf()->GetOrganization(org);
	if (!conf_org) throw svExKeyPollInvalidOrg(org);
	vector<svConfSocket *> key_server = conf_org->GetRSAKeyServers();
	if (!key_server.size()) throw svExKeyPollNoKeyServers(org);

	for (vector<svConfSocket *>::iterator i = key_server.begin();
		i != key_server.end(); i++) {
		try {
			svSocket *skt_connect = svSocket::Create(*(*i));
			skt.push_back(skt_connect);
			skt_count++;
		} catch (runtime_error &e) {
			svError("%s: Error creating socket: %s",
				name.c_str(), e.what());
		}
	}
	if (!skt.size()) throw svExKeyPollNoKeyServers(org);

	ttl = svService::GetConf()->GetPollTTL();
	dev = conf_org->GetDevice();
	key_dir = conf_org->GetKeyDir();
	threshold = conf_org->GetKeyPollThreshold();

	memset(pkt_buffer, 0, _SUVA_MAX_PACKET_SIZE);
}

svThreadKeyPoll::~svThreadKeyPoll()
{
	Join();

	for (vector<svSocket *>::iterator i = skt.begin();
		i != skt.end(); i++) delete (*i);
	for (vector<svKeyPollResult *>::iterator i = poll_result.begin();
		i != poll_result.end(); i++) {
		RSA *key = (*i)->GetKey();
		if (key) RSA_free(key);
		delete (*i);
	}
}

void *svThreadKeyPoll::Entry(void)
{
	svSocketSet skt_set;
	struct timeval tv;
	gettimeofday(&tv, NULL);
	vector<svSocket *>::iterator i;
	svEvent *event;

	svPacketAuthKeyPoll pkt_key_poll(pkt_buffer);
	pkt_key_poll.SetDevice(dev);
	pkt_key_poll.SetOrganization(org);
	pkt_key_poll.SetVersion(
		_SUVA_PROTO_VER_MAJOR, _SUVA_PROTO_VER_MINOR);

	while (skt.size()) {
		while ((event = PopEvent())) {
			if (event->GetId() == svEVT_QUIT) return NULL;
			delete event;
		}

		struct timeval tv_now;
		gettimeofday(&tv_now, NULL);
		if (uint32_t(tv_now.tv_sec - tv.tv_sec) > ttl) {
			svError("%s: %s: exceeded %ds TTL",
				name.c_str(), org.c_str(), ttl);
			break;
		}

		i = skt.begin();
		while (i != skt.end()) {
			if (skt_set.IsSetForRead((*i))) {
				i++;
				continue;
			}
			try {
				(*i)->Connect();
				if ((*i)->IsConnected()) {
					(*i)->WritePacket(pkt_key_poll);
					skt_set.SelectForRead((*i));
				}
			}
			catch (runtime_error &e) {
				ostringstream os;
				os << name << ": " << (*i)->GetHostPath();
				if ((*i)->GetType() == svST_INET)
					os << ":" << (*i)->GetPort();
				os << ": " << e.what() << endl;
				svError(os.str().c_str());
				delete (*i);
				skt.erase(i);
				break;
			}
		}

		if (skt_set.GetCountForRead() == 0) {
			svDelay(_SUVA_DEFAULT_DELAY);
			continue;
		}

		int rc = 0;

		try {
			//rc = skt_set.Select(_SUVA_DEFAULT_DELAY);
			rc = skt_set.Select(_SUVA_DEFAULT_DELAY * 5);
		} catch (runtime_error &e) {
			svEventServer::GetInstance()->Dispatch(
				new svEventKeyPollResult(this, NULL, org));
			return NULL;
		}

		if (rc == 0) continue;
		else if (rc == -1) {
			svDelay(_SUVA_DEFAULT_DELAY);
			continue;
		}

		i = skt.begin();
		while (i != skt.end() && rc > 0) {
			if (!skt_set.IsReadyForRead((*i))) {
				i++;
				continue;
			}
			svPacket pkt(pkt_buffer);
			try {
				(*i)->ReadPacket(pkt, _SUVA_MAX_PAYLOAD);
				if (pkt.GetId() == PKT_ID_AUTH &&
					pkt.GetArg1() == PKT_ARG_AUTH_KEYPOLL) {
					AddPollResult(
						pkt.GetPayload(), pkt.GetPayloadLength());
				}
			} catch (runtime_error &e) {
				svError("%s: Error reading from key server: %s:%d: %s",
					name.c_str(), (*i)->GetHostPath().c_str(),
					(*i)->GetPort(), e.what());
			}
			skt_set.RemoveForRead((*i));
			delete (*i);
			skt.erase(i);
			i = skt.begin();
			rc--;
		}
	}

	uint32_t top_score = 0;
	RSA *top_key = NULL;
	for (vector<svKeyPollResult *>::iterator i = poll_result.begin();
		i != poll_result.end(); i++) {
		if ((*i)->GetScore() <= top_score) continue;
		top_score = (*i)->GetScore();
		if (top_key) RSA_free(top_key);
		top_key = (*i)->GetKey();
	}

	if (top_score == 0 || top_key == NULL) {
		if (top_key) RSA_free(top_key);
		svEventServer::GetInstance()->Dispatch(
			new svEventKeyPollResult(this, NULL, org));
		return NULL;
	}

	uint32_t percent = top_score * 100 / skt_count;
	if (percent != 100) {
		svLog("%s: %s: key poll score: %d%%",
			name.c_str(), org.c_str(), percent);
	}
	if (percent < threshold) {
		RSA_free(top_key);
		svEventServer::GetInstance()->Dispatch(
			new svEventKeyPollResult(this, NULL, org));
		return NULL;
	}

	//RSA_print_fp(stderr, top_key, 0);
	svEventServer::GetInstance()->Dispatch(
		new svEventKeyPollResult(this, NULL, org, top_key));
	return NULL;
}

void svThreadKeyPoll::AddClient(svEventClient *c)
{
	map<svEventClient *, svObject *>::iterator i;
	i = client.find(c);
	if (i != client.end()) {
		// TODO: exception...
		return;
	}
	client[c] = c;
}

void svThreadKeyPoll::BroadcastResult(svEventKeyPollResult *result)
{
	map<svEventClient *, svObject *>::iterator i;
	for (i = client.begin(); i != client.end(); i++) {
		RSA *key = RSAPublicKey_dup(result->GetKey());
		svEventServer::GetInstance()->Dispatch(
			new svEventKeyPollResult(GetDefaultDest(), i->first,
				result->GetOrganization(), key));
	}
}

void svThreadKeyPoll::AddPollResult(uint8_t *payload, size_t length)
{
	uint8_t digest[MD5_DIGEST_LENGTH];
	MD5(payload, length, digest);

	for (vector<svKeyPollResult *>::iterator i = poll_result.begin();
		i != poll_result.end(); i++) {
		if (*(*i) != digest) continue;
		(*i)->AddPoint();
		return;
	}

	RSA *key = NULL;
	BIO *mem = BIO_new_mem_buf(payload, length);
	if (!(key = PEM_read_bio_RSA_PUBKEY(mem, NULL, NULL, NULL)))
		svError("%s: invalid public RSA key", name.c_str());
	else {
		svKeyPollResult *result;
		result = new svKeyPollResult(digest, key);
		poll_result.push_back(result);
	}
	BIO_free(mem);
}

// vi: ts=4
