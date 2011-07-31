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

#ifndef _CSCONF_H
#define _CSCONF_H

using namespace std;

class csXmlTag
{
public:
    csXmlTag(const char *name, const char **attr);

    inline string GetName(void) const { return name; };
    bool ParamExists(const string &key);
    string GetParamValue(const string &key);
    inline string GetText(void) const { return text; };
    inline void SetText(const string &text) { this->text = text; };
    void *GetData(void) { return data; };
    inline void SetData(void *data) { this->data = data; };

    bool operator==(const char *tag);
    bool operator!=(const char *tag);

protected:
    map<string, string> param;
    string name;
    string text;
    void *data;
};

class csConf;
class csXmlParser
{
public:
    csXmlParser(void);
    ~csXmlParser();
    void Reset(void);
    void Parse(void);
    inline void SetConf(csConf *conf) { this->conf = conf; };

    void ParseError(const string &what);
    virtual void ParseElementOpen(csXmlTag *tag) { };
    virtual void ParseElementClose(csXmlTag *tag) { };

    XML_Parser p;
    csConf *conf;
    FILE *fh;
    uint8_t *buffer;
    long page_size;
    vector<csXmlTag *> stack;
};

class csXmlParseException : public csException
{
public:
    explicit csXmlParseException(const char *what,
        uint32_t row, uint32_t col, uint8_t byte)
        : csException(EINVAL, what), row(row), col(col), byte(byte)
        { };
    virtual ~csXmlParseException() throw() { };

    uint32_t row;
    uint32_t col;
    uint8_t byte;
};

class csXmlKeyNotFound : public csException
{
public:
    explicit csXmlKeyNotFound(const char *key)
        : csException(key)
        { };
    virtual ~csXmlKeyNotFound() throw() { };
};

class csConf
{
public:
    csConf(const char *filename, csXmlParser *parser);
    virtual ~csConf();

    virtual void Reload(void) { parser->Reset(); };

    inline const char *GetFilename(void) const { return filename.c_str(); };

protected:
    string filename;
    csXmlParser *parser;
};

#endif // _CSCONF_H
// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
