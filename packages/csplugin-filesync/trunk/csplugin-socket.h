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

class csFileSyncSocketTimeout : public csException
{
public:
    explicit csFileSyncSocketTimeout(void)
        : csException("csFileSyncSocketTimeout") { };
};

class csFileSyncSocketConnecting : public csException
{
public:
    explicit csFileSyncSocketConnecting(void)
        : csException("csFileSyncSocketConnecting") { };
};

class csFileSyncSocket
{
public:
    enum State
    {
        Init,
        Connected,
    };

    enum Flags
    {
        None,
        WaitAll = 0x01,
    };

    csFileSyncSocket();
    csFileSyncSocket(int sd, struct sockaddr_in &sa);
    virtual ~csFileSyncSocket();

    int GetDescriptor(void) { return sd; };

    void SetTimeout(time_t tv_sec, time_t tv_usec) {
        tv_timeout.tv_sec = tv_sec;
        tv_timeout.tv_usec = tv_usec;
    };

    size_t Read(size_t length, uint8_t *buffer);
    size_t Write(size_t length, uint8_t *buffer);

protected:
    int sd;
    struct sockaddr_in sa;
    State state;
    Flags flags;
    struct timeval tv_timeout;
};

class csFileSyncSocketAccept : public csFileSyncSocket
{
public:
    csFileSyncSocketAccept(const string &addr, in_port_t port);

    csFileSyncSocket *Accept(void);
};

class csFileSyncSocketConnect : public csFileSyncSocket
{
public:
    csFileSyncSocketConnect(const string &host, in_port_t port);

    void Connect(void);
};

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
