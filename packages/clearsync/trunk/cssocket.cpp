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

#include <sys/types.h>
#include <sys/socket.h>
#include <sys/time.h>
#include <sys/ioctl.h>

#include <stdexcept>
#include <vector>

#include <stdio.h>
#include <stdint.h>
#include <pthread.h>
#include <string.h>
#include <errno.h>
#include <regex.h>

#include <netinet/in.h>
#include <netdb.h>
#include <net/if.h>
#include <linux/sockios.h>

#include <clearsync/csexception.h>
#include <clearsync/cslog.h>
#include <clearsync/cssocket.h>

csSocket::csSocket()
    : state(Init), flags(None), timeout(0)
{
    memset(&sa, 0, sizeof(struct sockaddr_in));
    sd = socket(AF_INET, SOCK_STREAM | SOCK_NONBLOCK, 0);
    if (sd < 0) throw csException(errno, "socket");
}

csSocket::csSocket(int sd, struct sockaddr_in &sa)
    : state(Connected), flags(None), sd(sd), timeout(0)
{
    memcpy(&this->sa, &sa, sizeof(struct sockaddr_in));
}

csSocket::~csSocket()
{
    if (sd >= 0) {
        if (state == Connected) shutdown(sd, SHUT_RDWR);
        close(sd);
    }
}

void csSocket::Read(size_t &length, uint8_t *buffer)
{
	struct timeval tv;
	uint8_t *ptr = buffer;
	size_t bytes_read, bytes_left = length;

	for (length = 0; bytes_left > 0; ) {
        bytes_read = recv(sd, (char *)ptr, bytes_left, 0);

		if (!bytes_read) throw csSocketHangup();
		else if (bytes_read == -1) {
			if (errno == EAGAIN || errno == EWOULDBLOCK) {
                if (!(flags & WaitAll)) break;

				gettimeofday(&tv, NULL);
				if (tv.tv_sec - tv_active.tv_sec <= timeout) {
					usleep(csSocketRetry);
					continue;
				}
				throw csSocketTimeout();
            }
            throw csException(errno, "recv");
        }

		ptr += bytes_read;
		this->bytes_read += bytes_read;
		bytes_left -= bytes_read;
		length += bytes_read;

		gettimeofday(&tv_active, NULL);
    }
}

void csSocket::Write(size_t &length, uint8_t *buffer)
{
	struct timeval tv;
	uint8_t *ptr = buffer;
	size_t bytes_wrote, bytes_left = length;

	for (length = 0; bytes_left > 0; ) {
        bytes_wrote = send(sd, (const char *)ptr, bytes_left, 0);

		if (!bytes_wrote) throw csSocketHangup();
		else if (bytes_wrote == -1) {
			if (errno == EAGAIN || errno == EWOULDBLOCK) {
				if (!(flags & WaitAll)) break;

				gettimeofday(&tv, NULL);
				if (tv.tv_sec - tv_active.tv_sec <= timeout) {
					usleep(csSocketRetry);
					continue;
				}
				throw csSocketTimeout();
			}
            throw csException(errno, "send");
		}

		ptr += bytes_wrote;
		this->bytes_wrote += bytes_wrote;
		bytes_left -= bytes_wrote;
		length += bytes_wrote;

		gettimeofday(&tv_active, NULL);
	}
}

csSocketAccept::csSocketAccept(
    const string &addr, in_port_t port)
    : csSocket()
{
    int sd_ifr;
    struct ifreq ifr;
    struct sockaddr_in sa_ifaddr;
    struct sockaddr_in *sa_result = &sa_ifaddr;
    struct addrinfo hints, *result;

    sa.sin_family = AF_INET;
    sa.sin_port = htons(port);

    if (addr == "all" || addr == "any")
        sa.sin_addr.s_addr = htonl(INADDR_ANY);
    else {
        if ((sd_ifr = socket(AF_INET, SOCK_DGRAM, 0)) < 0)
            throw csException(errno, "socket");

        strncpy(ifr.ifr_name, addr.c_str(), IFNAMSIZ);

        if (ioctl(sd_ifr, SIOCGIFADDR, &ifr) == 0) {
            sa_ifaddr = *((struct sockaddr_in *)&ifr.ifr_addr);
            sa.sin_addr.s_addr = sa_result->sin_addr.s_addr;

            close(sd_ifr);
        }
        else {
            close(sd_ifr);

            memset(&hints, 0, sizeof(struct addrinfo));
            hints.ai_family = AF_INET;
            hints.ai_flags = AI_PASSIVE;

            int rc;
            if ((rc = getaddrinfo(addr.c_str(), NULL, &hints, &result)) != 0)
                throw csException(rc, "getaddrinfo");

            sa_result = (struct sockaddr_in *)result->ai_addr;
            sa.sin_addr.s_addr = sa_result->sin_addr.s_addr;
            freeaddrinfo(result);
        }
    }

	int on = 1;
	if (setsockopt(sd,
		SOL_SOCKET, SO_REUSEADDR, (char *)&on, sizeof(on)) != 0) {
		throw csException(errno, "setsockopt: SO_REUSEADDR");
	}

	if (bind(sd, (struct sockaddr *)&sa, sizeof(struct sockaddr_in)) < 0)
		throw csException(errno, "bind");

	if (listen(sd, SOMAXCONN) < 0)
		throw csException(errno, "listen");

    state = Accepting;
}

csSocket *csSocketAccept::Accept(void)
{
    if (state == Accepting) {
        struct sockaddr_in sa_client;
        socklen_t sa_len = sizeof(struct sockaddr_in);
        int sd_client = accept(sd,
            (struct sockaddr *)&sa_client, &sa_len);
        if (sd_client < 0)
            throw csException(errno, "accept");
        return new csSocket(sd_client, sa_client);
    }
    return NULL;
}

csSocketConnect::csSocketConnect(
    const string &host, in_port_t port)
    : csSocket()
{
    struct addrinfo hints, *result;
    memset(&hints, 0, sizeof(struct addrinfo));
    hints.ai_family = AF_INET;
    hints.ai_flags = AI_PASSIVE;

    int rc;
    if ((rc = getaddrinfo(host.c_str(), NULL, &hints, &result)) != 0)
        throw csException(rc, "getaddrinfo");

    struct sockaddr_in *sa_result =
        (struct sockaddr_in *)result->ai_addr;

    sa.sin_family = AF_INET;
    sa.sin_port = htons(port);
    sa.sin_addr.s_addr = sa_result->sin_addr.s_addr;

    freeaddrinfo(result);
    gettimeofday(&tv_active, NULL);

    state = Connecting;
}

void csSocketConnect::Connect(void)
{
    if (state == Connecting) {
		struct timeval tv;
		gettimeofday(&tv, NULL);
		if (tv.tv_sec - tv_active.tv_sec > timeout)
            throw csSocketTimeout();

		if (connect(sd,
            (struct sockaddr *)&sa, sizeof(struct sockaddr_in)) == 0) {
			state = Connected;
			return;
		}

		if (errno == EISCONN) {
			state = Connected;
			return;
		}

        throw csSocketConnecting();
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
