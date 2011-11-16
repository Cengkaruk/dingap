// ClearSync: FileSync socket declarations.
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

#ifndef _CSSOCKET_H
#define _CSSOCKET_H

using namespace std;

#define csSocketRetry   80000

class csSocketTimeout : public csException
{
public:
    explicit csSocketTimeout(void)
        : csException("csSocketTimeout") { };
};

class csSocketConnecting : public csException
{
public:
    explicit csSocketConnecting(void)
        : csException("csSocketConnecting") { };
};

class csSocketHangup : public csException
{
public:
    explicit csSocketHangup(void)
        : csException("csSocketHangup") { };
};

class csSocket
{
public:
    enum State
    {
        Init,
        Accepting,
        Accepted,
        Connecting,
        Connected,
    };

    enum Flags
    {
        None,
        WaitAll = 0x1,
    };

    csSocket();
    csSocket(int sd, struct sockaddr_in &sa);
    virtual ~csSocket();

    virtual void Create(void);
    virtual void Close(void);
    int GetDescriptor(void) { return sd; };

    void SetTimeout(time_t tv_sec) {
        timeout = tv_sec;
    };
    void SetWaitAll(bool enable = true) {
        if (enable)
            flags = WaitAll;
        else
            flags = None;
    };

    void Read(size_t &length, uint8_t *buffer);
    void Write(size_t &length, uint8_t *buffer);

protected:
    int sd;
    struct sockaddr_in sa;
    State state;
    Flags flags;
    time_t timeout;
    struct timeval tv_active;
    size_t bytes_read;
    size_t bytes_wrote;
};

class csSocketAccept : public csSocket
{
public:
    csSocketAccept(const string &addr, in_port_t port);

    csSocket *Accept(void);
};

class csSocketConnect : public csSocket
{
public:
    csSocketConnect(const string &host, in_port_t port);

    virtual void Close(void);
    void Connect(void);
};

#endif // _CSSOCKET_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
