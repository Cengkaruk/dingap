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

#ifndef _CSTHREAD_H
#define _CSTHREAD_H

using namespace std;

#ifndef _CS_THREAD_STACK_SIZE
#define _CS_THREAD_STACK_SIZE   32768
#endif

class csThread : public csEventClient
{
public:
    csThread(size_t stack_size = _CS_THREAD_STACK_SIZE);
    virtual ~csThread() { };

    virtual void Start(void);
    virtual void *Entry(void) = 0;

protected:
    pthread_t id;
    pthread_attr_t attr;

    void Join(void);
};

#endif // _CSTHREAD_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
