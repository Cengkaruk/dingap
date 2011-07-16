// TODO: Program name/short-description
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

#ifndef _CSEXCEPTION_H
#define _CSEXCEPTION_H

#define _CS_THROW_DEBUG(e, s) \
    throw csDebugException(e, s, __LINE__, __FILE__)

using namespace std;

class csException : public runtime_error
{
public:
    explicit csException(void)
        : runtime_error("csException"),
        eint(-1), estring("csException") { };
    explicit csException(const char *s)
        : runtime_error("csException"),
        eint(-1), estring(s) { };
    explicit csException(int e)
        : runtime_error(strerror(e)), eint(e), estring("csException") { };
    explicit csException(int e, const char *s)
        : runtime_error(strerror(e)), eint(e), estring(s) { };

    virtual ~csException() throw() { };

    const int eint;
    const string estring;
};

class csDebugException : public csException
{
public:
    explicit csDebugException(int e, const char *s, long l, const char *f)
        : csException(e, s), eline(l), efile(f) { };

    virtual ~csDebugException() throw() { };

    const char *efile;
    long eline;
};

#endif // _CSEXCEPTION_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
