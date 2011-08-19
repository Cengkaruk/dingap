// KAVscan: Kaspersky Antivirus Scanner
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

#ifndef _KSUTIL_H
#define _KSUTIL_H

#ifndef KAVSCAN_CONF
#define KAVSCAN_CONF        "/etc/kavscan.conf"
#endif

using namespace std;

struct ks_conf_t
{
    string filename;
    string com_connection;
    string scan_connection;
    string event_connection;
    string ctrl_connection;
    string pid_path;
};

class ksExConfOpen : public runtime_error
{
public:
    explicit ksExConfOpen(const string &filename, const string &what)
        : runtime_error(filename + ": " + what) { };
    virtual ~ksExConfOpen() throw() { };
};

class ksExConfRead : public runtime_error
{
public:
    explicit ksExConfRead(const string &filename, const string &what)
        : runtime_error(filename + ": " + what) { };
    virtual ~ksExConfRead() throw() { };
};

class ksExConfParse : public runtime_error
{
public:
    explicit ksExConfParse(const string &filename,
        const string &what, uint32_t row, uint32_t col, uint8_t byte)
        : runtime_error(filename + ": " + what),
        row(row), col(col), byte(byte) { };
    virtual ~ksExConfParse() throw() { };

    uint32_t row;
    uint32_t col;
    uint8_t byte;
};

class ksExConfTagNotFound : public runtime_error
{
public:
    explicit ksExConfTagNotFound(const string &tag)
        : runtime_error(tag) { };
    virtual ~ksExConfTagNotFound() throw() { };
};

class ksExConfKeyNotFound : public runtime_error
{
public:
    explicit ksExConfKeyNotFound(const string &key)
        : runtime_error(key) { };
    virtual ~ksExConfKeyNotFound() throw() { };
};

class ksExNotRunning : public runtime_error
{
public:
    explicit ksExNotRunning()
        : runtime_error("kavehost not running") { };
    virtual ~ksExNotRunning() throw() { };
};

class ksXmlTag
{
public:
    ksXmlTag(const char *name, const char **attr);

    bool ParamExists(const string &key);
    const string GetName(void) const { return name; };
    string GetParamValue(const string &key);
    string GetText(void) { return text; };
    void SetText(const string &text) { this->text = text; };

    bool operator==(const char *tag);
    bool operator!=(const char *tag);

protected:
    string name;
    map<string, string> param;
    string text;
};

class ksXmlParser
{
public:
    ksXmlParser(struct ks_conf_t *conf);
    virtual ~ksXmlParser();
    void Parse(void);
    void ParseError(const string &what);
    void ParseElementOpen(ksXmlTag *tag);
    void ParseElementClose(ksXmlTag *tag);

    FILE *fh;
    long page_size;
    uint8_t *buffer;
    XML_Parser p;
    vector<ksXmlTag *> stack;
    struct ks_conf_t *conf;
};

void ksIsRunning(const string &pid_path);

#endif // _KSUTIL_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
