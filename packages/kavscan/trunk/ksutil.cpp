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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <string>
#include <map>
#include <vector>
#include <stdexcept>
#include <sstream>

#include <sys/types.h>
#include <sys/stat.h>

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <expat.h>
#include <string.h>
#include <errno.h>

#include "ksutil.h"

extern int errno;

ksXmlTag::ksXmlTag(const char *name, const char **attr)
    : name(name)
{
    for (int i = 0; attr[i]; i += 2)
        param[attr[i]] = attr[i + 1];
}

bool ksXmlTag::ParamExists(const string &key)
{
    map<string, string>::iterator i;
    i = param.find(key);
    return (bool)(i != param.end());
}

string ksXmlTag::GetParamValue(const string &key)
{
    map<string, string>::iterator i;
    i = param.find(key);
    if (i == param.end())
        throw ksExConfKeyNotFound(key);
    return i->second;
}

bool ksXmlTag::operator==(const char *tag)
{
    if (!strcasecmp(tag, name.c_str())) return true;
    return false;
}

bool ksXmlTag::operator!=(const char *tag)
{
    if (!strcasecmp(tag, name.c_str())) return false;
    return true;
}

static void ksXmlElementOpen(
    void *data, const char *element, const char **attr)
{
    ksXmlParser *ksp = (ksXmlParser *)data;

    ksXmlTag *tag = new ksXmlTag(element, attr);
    ksp->ParseElementOpen(tag);
    ksp->stack.push_back(tag);
}

static void ksXmlElementClose(void *data, const char *element)
{
    ksXmlParser *ksp = (ksXmlParser *)data;

    ksXmlTag *tag = ksp->stack.back();
    string text = tag->GetText();
    ksp->stack.pop_back();
    ksp->ParseElementClose(tag);
    delete tag;
}

static void ksXmlText(void *data, const char *txt, int length)
{
    ksXmlParser *ksp = (ksXmlParser *)data;

    ksXmlTag *tag = ksp->stack.back();
    string text = tag->GetText();
    for (int i = 0; i < length; i++) {
        if (txt[i] == '\n' || txt[i] == '\r' ||
            !isprint(txt[i])) continue;
        text.append(1, txt[i]);
    }
    tag->SetText(text);
}

ksXmlParser::ksXmlParser(struct ks_conf_t *conf)
    : fh(NULL), buffer(NULL), conf(conf)
{
    p = XML_ParserCreate(NULL);
    XML_SetUserData(p, (void *)this);
    XML_SetElementHandler(p, ksXmlElementOpen, ksXmlElementClose);
    XML_SetCharacterDataHandler(p, ksXmlText);
    page_size = sysconf(_SC_PAGESIZE);
    if (page_size <= 0) page_size = 4096;
    buffer = new uint8_t[page_size];
}

ksXmlParser::~ksXmlParser()
{
    XML_ParserFree(p);
    delete [] buffer;
    if (fh) fclose(fh);
    for (vector<ksXmlTag *>::iterator i = stack.begin();
        i != stack.end(); i++) delete (*i);
}

void ksXmlParser::Parse(void)
{
    if (!(fh = fopen(conf->filename.c_str(), "r")))
        throw ksExConfOpen(conf->filename, strerror(errno));
    for (;;) {
        size_t length;
        length = fread(buffer, 1, page_size, fh);
        if (ferror(fh))
            throw ksExConfRead(conf->filename, strerror(errno));
        int done = feof(fh);

        if (!XML_Parse(p, (const char *)buffer, length, done)) {
            ParseError(string("XML parse error: ") +
                XML_ErrorString(XML_GetErrorCode(p)));
        }
        if (done) break;
    }
}

void ksXmlParser::ParseError(const string &what)
{
    throw ksExConfParse(conf->filename, what,
        XML_GetCurrentLineNumber(p),
        XML_GetCurrentColumnNumber(p),
        buffer[XML_GetCurrentByteIndex(p)]);
}

void ksXmlParser::ParseElementOpen(ksXmlTag *tag)
{
}

void ksXmlParser::ParseElementClose(ksXmlTag *tag)
{
    string text = tag->GetText();

    if (text.size() && stack.size() &&
        (*stack.back()) == "ConnectionSettings") {
        if (tag->GetName() == "ComConnectionString")
            conf->com_connection = text;
        else if (tag->GetName() == "ScanConnectionString")
            conf->scan_connection = text;
        else if (tag->GetName() == "EventConnectionString")
            conf->event_connection = text;
        else if (tag->GetName() == "CtrlConnectionString")
            conf->ctrl_connection = text;
    }
    else if (text.size() && stack.size() &&
        (*stack.back()) == "DirectorySettings") {
        if (tag->GetName() == "PidPath")
            conf->pid_path = text;
    }
}

void ksIsRunning(const string &pid_path)
{
    struct stat pid_stat;
    if (stat(pid_path.c_str(), &pid_stat) == -1)
        throw ksExNotRunning();

    FILE *h_pid = fopen(pid_path.c_str(), "r");
    if (!h_pid)
        throw ksExNotRunning();

    pid_t kavehost_pid;
    int rc = fscanf(h_pid, "%d", (int *)&kavehost_pid);
    fclose(h_pid);
    if (rc != 1)
        throw ksExNotRunning();

    ostringstream os;
    os << "/proc/" << kavehost_pid;
    if (stat(os.str().c_str(), &pid_stat) == -1)
        throw ksExNotRunning();
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
