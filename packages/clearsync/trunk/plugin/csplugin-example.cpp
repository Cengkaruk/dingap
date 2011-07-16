// ClearSync: example plugin.
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

#include <clearsync/csplugin.h>

class csPluginConf;
class csPluginXmlParser : public csXmlParser
{
public:
    virtual void ParseElementOpen(csXmlTag *tag);
    virtual void ParseElementClose(csXmlTag *tag);
};

class csPluginExample;
class csPluginConf : public csConf
{
public:
    csPluginConf(csPluginExample *parent,
        const char *filename, csPluginXmlParser *parser)
        : csConf(filename, parser), parent(parent) { };

    virtual void Reload(void);

protected:
    friend class csPluginXmlParser;

    csPluginExample *parent;
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

class csPluginExample : public csPlugin
{
public:
    csPluginExample(const string &name,
        csEventClient *parent, size_t stack_size);
    virtual ~csPluginExample();

    virtual void SetConfigurationFile(const string &conf_filename);

    virtual void *Entry(void);

protected:
    friend class csPluginXmlParser;

    csPluginConf *conf;
};

csPluginExample::csPluginExample(const string &name,
    csEventClient *parent, size_t stack_size)
    : csPlugin(name, parent, stack_size), conf(NULL)
{
    csLog::Log(csLog::Debug, "%s: Initialized.", name.c_str());
}

csPluginExample::~csPluginExample()
{
    if (conf) delete conf;
}

void csPluginExample::SetConfigurationFile(const string &conf_filename)
{
    if (conf == NULL) {
        csPluginXmlParser *parser = new csPluginXmlParser();
        conf = new csPluginConf(this, conf_filename.c_str(), parser);
        parser->SetConf(dynamic_cast<csConf *>(conf));
        conf->Reload();
    }
}

void *csPluginExample::Entry(void)
{
    csLog::Log(csLog::Info, "%s: Running.", name.c_str());
    csTimer *timer = new csTimer(500, 3, 3, this);

    unsigned long loops = 0ul;
    GetStateVar("loops", loops);
    csLog::Log(csLog::Debug, "%s: loops: %lu", name.c_str(), loops);

    for (bool run = true; run; loops++) {
        csEvent *event = EventPopWait();

        switch (event->GetId()) {
        case csEVENT_QUIT:
            csLog::Log(csLog::Info, "%s: Terminated.", name.c_str());
            run = false;
            break;

        case csEVENT_TIMER:
            csLog::Log(csLog::Debug, "%s: Tick: %lu", name.c_str(),
                static_cast<csTimerEvent *>(event)->GetTimer()->GetId());
            break;
        }

        delete event;
    }

    delete timer;

    SetStateVar("loops", loops);
    csLog::Log(csLog::Debug, "%s: loops: %lu", name.c_str(), loops);

    return NULL;
}

csPluginInit(csPluginExample);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
