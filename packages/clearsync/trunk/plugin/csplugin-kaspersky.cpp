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

#ifdef _HAVE_CONFIG_H
#include "config.h"
#endif

#include <pwd.h>

#include <clearsync/csplugin.h>

#define _MIN_UID	500
#define _MAX_UID	65533

class csPluginConf;
class csPluginXmlParser : public csXmlParser
{
public:
    virtual void ParseElementOpen(csXmlTag *tag);
    virtual void ParseElementClose(csXmlTag *tag);
};

class csPluginKaspersky;
class csPluginConf : public csConf
{
public:
    csPluginConf(csPluginKaspersky *parent,
        const char *filename, csPluginXmlParser *parser)
        : csConf(filename, parser), parent(parent) { };

    virtual void Reload(void);

protected:
    friend class csPluginXmlParser;

    csPluginKaspersky *parent;
};

void csPluginConf::Reload(void)
{
    csConf::Reload();
    parser->Parse();
}

void csPluginXmlParser::ParseElementOpen(csXmlTag *tag)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);
}

void csPluginXmlParser::ParseElementClose(csXmlTag *tag)
{
    string text = tag->GetText();
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "test-tag") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!text.size())
            ParseError("missing value for tag: " + tag->GetName());

        csLog::Log(csLog::Debug, "%s: %s",
        tag->GetName().c_str(), text.c_str());
    }
}

class csPluginKaspersky : public csPlugin
{
public:
    csPluginKaspersky(const string &name,
        csEventClient *parent, size_t stack_size);
    virtual ~csPluginKaspersky();

    virtual void SetConfigurationFile(const string &conf_filename);

    virtual void *Entry(void);

protected:
    friend class csPluginXmlParser;

    void UserAudit(void);

    csPluginConf *conf;
	struct passwd pw, *pwp;
	char *buf;
	int r, pages;
	long page_size;
	unsigned long accounts;
};

csPluginKaspersky::csPluginKaspersky(const string &name,
    csEventClient *parent, size_t stack_size)
    : csPlugin(name, parent, stack_size), conf(NULL),
    buf(NULL), pages(0), accounts(0ul)
{
	page_size = getpagesize();
	if (page_size == -1L) page_size = 4096;

    csLog::Log(csLog::Debug, "%s: Initialized.", name.c_str());
}

csPluginKaspersky::~csPluginKaspersky()
{
    if (conf) delete conf;
	if (buf) free(buf);
}

void csPluginKaspersky::SetConfigurationFile(const string &conf_filename)
{
    if (conf == NULL) {
        csPluginXmlParser *parser = new csPluginXmlParser();
        conf = new csPluginConf(this, conf_filename.c_str(), parser);
        parser->SetConf(dynamic_cast<csConf *>(conf));
        conf->Reload();
    }
}

void *csPluginKaspersky::Entry(void)
{
    csLog::Log(csLog::Info, "%s: Running.", name.c_str());

    unsigned long value = 3ul;
    GetStateVar("value", value);
    csLog::Log(csLog::Debug, "%s: value: %lu", name.c_str(), value);
    csTimer *timer = new csTimer(600, value, 3, this);

    for (bool run = true; run; ) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            csLog::Log(csLog::Info, "%s: Terminated.", name.c_str());
            run = false;
            break;

        case csEVENT_TIMER:
            csLog::Log(csLog::Debug, "%s: Tick: %lu", name.c_str(),
                static_cast<csTimerEvent *>(event)->GetTimer()->GetId());
            UserAudit();
            break;
        }

        delete event;
    }

    delete timer;

    SetStateVar("value", value);
    csLog::Log(csLog::Debug, "%s: value: %lu", name.c_str(), value);

    return NULL;
}

void csPluginKaspersky::UserAudit(void)
{
    accounts = 0ul;

	setpwent();
	for ( ;; ) {
		r = getpwent_r(&pw, buf, page_size * pages, &pwp);
		if (r == ENOENT) break;
		else if (r == ERANGE) {
			pages++;
			buf = (char *)realloc(buf, page_size * pages);
			if (!buf) {
				csLog::Log(csLog::Error, "%s: realloc: %s",
                    name.c_str(), strerror(ENOMEM));
				endpwent();
				return;
			}
			csLog::Log(csLog::Debug, "increased buffer to: %ld bytes",
				page_size * pages);
			continue;
		}
		else if (r != 0) {
			csLog::Log(csLog::Error, "%s: getpwent_r: %s",
                name.c_str(), strerror(errno));
			endpwent();
			if (buf) free(buf);
            return;
		}

		if (pwp->pw_uid < _MIN_UID || pwp->pw_uid > _MAX_UID) continue;
		accounts++;
	}
	endpwent();

    csLog::Log(csLog::Info, "%s: accounts: %lu", name.c_str(), accounts);
}

csPluginInit(csPluginKaspersky);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
