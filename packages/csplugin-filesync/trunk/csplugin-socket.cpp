// ClearSync: FileSync socket implementation.
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

#include <netinet/in.h>

#include <string.h>

#include <clearsync/csplugin.h>

#include "csplugin-socket.h"

csFileSyncSocket::csFileSyncSocket()
    : state(Init), flags(None)
{
    memset(&tv_timeout, 0, sizeof(struct timeval));
    memset(&sa, 0, sizeof(struct sockaddr_in));
    sd = socket(AF_INET, SOCK_STREAM | SOCK_NONBLOCK, 0);
    if (sd < 0) throw csException(errno, "socket");
}

csFileSyncSocket::csFileSyncSocket(int sd, struct sockaddr_in &sa)
    : state(Connected), flags(None), sd(sd)
{
    memset(&tv_timeout, 0, sizeof(struct timeval));
    memcpy(&this->sa, &sa, sizeof(struct sockaddr_in));
}

csFileSyncSocket::~csFileSyncSocket()
{
    if (sd != -1) {
        if (state == Connected) shutdown(sd, SHUT_RDWR);
        close(sd);
    }
}

size_t csFileSyncSocket::Read(size_t length, uint8_t *buffer)
{
}

size_t csFileSyncSocket::Write(size_t length, uint8_t *buffer)
{
}

csFileSyncSocketAccept::csFileSyncSocketAccept(
    const string &addr, in_port_t port)
    : csFileSyncSocket()
{
}

csFileSyncSocketConnect::csFileSyncSocketConnect(
    const string &host, in_port_t port)
    : csFileSyncSocket()
{
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
