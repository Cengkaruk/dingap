// ClearSync: route watch plugin.
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

#include <sys/socket.h>
#include <sys/time.h>

#include <linux/netlink.h>
#include <linux/rtnetlink.h>

#include <clearsync/csplugin.h>

class csPluginXmlParser : public csXmlParser
{
public:
    virtual void ParseElementOpen(csXmlTag *tag);
    virtual void ParseElementClose(csXmlTag *tag);
};

class csPluginRouteWatch;
class csPluginConf : public csConf
{
public:
    csPluginConf(csPluginRouteWatch *parent,
        const char *filename, csPluginXmlParser *parser)
        : csConf(filename, parser), parent(parent) { };

    virtual void Reload(void);

protected:
    friend class csPluginXmlParser;

    csPluginRouteWatch *parent;
};

void csPluginConf::Reload(void)
{
    csConf::Reload();
    parser->Parse();
}

class csPluginRouteWatch : public csPlugin
{
public:
    csPluginRouteWatch(const string &name,
        csEventClient *parent, size_t stack_size);
    virtual ~csPluginRouteWatch();

    virtual void SetConfigurationFile(const string &conf_filename);

    virtual void *Entry(void);

protected:
    friend class csPluginXmlParser;

    int fd_netlink;
    struct sockaddr_nl sa;
    csPluginConf *conf;
    map<int, string> table;
    map<int, csTimer *> timer;
};

csPluginRouteWatch::csPluginRouteWatch(const string &name,
    csEventClient *parent, size_t stack_size)
    : csPlugin(name, parent, stack_size), conf(NULL)
{
    memset(&sa, 0, sizeof(sa));
    sa.nl_family = AF_NETLINK;
    sa.nl_groups = RTMGRP_IPV4_ROUTE | RTMGRP_IPV6_ROUTE;

    fd_netlink = socket(AF_NETLINK, SOCK_RAW, NETLINK_ROUTE);
    if (fd_netlink == -1) {
        csLog::Log(csLog::Error,
            "%s: socket: %s", name.c_str(), strerror(errno));
    }
    else if (bind(fd_netlink, (struct sockaddr *) &sa, sizeof(sa)) == -1) {
        close(fd_netlink);
        fd_netlink = -1;
        csLog::Log(csLog::Error, "%s: bind: %s",
            name.c_str(), strerror(errno));
    }

    csLog::Log(csLog::Debug, "%s: Initialized.", name.c_str());
}

csPluginRouteWatch::~csPluginRouteWatch()
{
    if (conf) delete conf;
    if (fd_netlink != -1) close(fd_netlink);
}

void csPluginRouteWatch::SetConfigurationFile(const string &conf_filename)
{
    if (conf == NULL) {
        csPluginXmlParser *parser = new csPluginXmlParser();
        conf = new csPluginConf(this, conf_filename.c_str(), parser);
        parser->SetConf(dynamic_cast<csConf *>(conf));
        conf->Reload();
    }
}

void *csPluginRouteWatch::Entry(void)
{
    if (fd_netlink == -1) return NULL;

    ssize_t len;
    char buf[::csGetPageSize()];
    struct iovec iov = { buf, sizeof(buf) };
    struct msghdr msg = { (void *)&sa, sizeof(sa), &iov, 1, NULL, 0, 0 };
    struct nlmsghdr *nh;
    struct rtmsg *rth;

    for ( ;; ) {
        if ((len = recvmsg(fd_netlink, &msg, MSG_DONTWAIT)) < 0) {
            if (errno != EAGAIN && errno != EWOULDBLOCK) {
                csLog::Log(csLog::Error, "%s: recvmsg: %s",
                    name.c_str(), strerror(errno));
                return NULL;
            }

            csEvent *event = EventPopWait(500);
            if (event == NULL) continue;

            switch (event->GetId()) {
            case csEVENT_QUIT:
                delete event;
                return NULL;

            default:
                delete event;
            }

            continue;
        }

        for (nh = (struct nlmsghdr *)buf;
            NLMSG_OK(nh, len); nh = NLMSG_NEXT(nh, len)) {

            if (nh->nlmsg_type == NLMSG_NOOP) continue;

            if (nh->nlmsg_type == NLMSG_DONE) {
                csLog::Log(csLog::Debug, "%s: End of multi-part message",
                    name.c_str());
                return NULL;
            }

            if (nh->nlmsg_type == NLMSG_ERROR) {
                csLog::Log(csLog::Error, "%s: NLMSG_ERROR",
                    name.c_str());
                return NULL;
            }

            if (nh->nlmsg_type == NLMSG_OVERRUN) {
                csLog::Log(csLog::Error, "%s: NLMSG_OVERRUN",
                    name.c_str());
                return NULL;
            }

            if (nh->nlmsg_type != RTM_NEWROUTE &&
                nh->nlmsg_type != RTM_DELROUTE) continue;

            rth = (struct rtmsg *)NLMSG_DATA(nh);
            map<int, string>::iterator i = table.find(rth->rtm_table);
            if (i == table.end()) continue;

            if (rth->rtm_family != AF_INET &&
                rth->rtm_family != AF_INET6) continue;
            if (rth->rtm_family == AF_INET6) {
                csLog::Log(csLog::Warning, "%s: No IPv6t support (yet)",
                    name.c_str());
                continue;
            }

            switch (nh->nlmsg_type) {
            case RTM_NEWROUTE:
                csLog::Log(csLog::Debug, "%s: New route", name.c_str());
                ::csExecute(i->second);
                break;
            case RTM_DELROUTE:
                csLog::Log(csLog::Debug, "%s: Deleted route", name.c_str());
                ::csExecute(i->second);
                break;
            default:
                csLog::Log(csLog::Debug, "%s: Received message: %d",
                    name.c_str(), nh->nlmsg_type);
                break;
            }
        }
    }

    return NULL;
}

void csPluginXmlParser::ParseElementOpen(csXmlTag *tag)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "on-route-change") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->ParamExists("table"))
            ParseError("table parameter missing");

        int table = atoi(tag->GetParamValue("table").c_str());
        tag->SetData((void *)table);

        csLog::Log(csLog::Debug,
            "%s: Watching routing table %d for changes.",
            _conf->parent->name.c_str(), table);
    }
}

void csPluginXmlParser::ParseElementClose(csXmlTag *tag)
{
    string text = tag->GetText();
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "on-route-change") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!text.size())
            ParseError("missing value for tag: " + tag->GetName());

        csLog::Log(csLog::Debug, "%s: %s: %s",
            _conf->parent->name.c_str(),
            tag->GetName().c_str(), text.c_str());

        _conf->parent->table[(int)tag->GetData()] = text;
    }
}

csPluginInit(csPluginRouteWatch);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
