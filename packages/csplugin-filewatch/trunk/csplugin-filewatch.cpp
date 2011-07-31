// ClearSync: file watch plugin.
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

#include <sys/time.h>
#include <sys/types.h>
#include <sys/inotify.h>
#include <sys/stat.h>

#include <unistd.h>
#include <limits.h>
#include <stdlib.h>
#include <libgen.h>

#include <clearsync/csplugin.h>

#define _DEFAULT_DELAY          5
#define _DELAY_TIMER_BASE       500
#define _DIRTY_TIMER_ID         100
#define _DIRTY_TIMER_VALUE      10
#define _INOTIFY_MASK_SELF      "__csInotifyMaskSelf__"

class csPluginFileWatch;
class csActionGroup
{
public:
    csActionGroup(const string &name, time_t delay = _DEFAULT_DELAY);
    virtual ~csActionGroup();

    inline string GetName(void) { return name; };
    inline void AppendAction(const string &action);

    bool operator!=(cstimer_id_t id) {
        if (timer == NULL) return true;
        if (timer->GetId() != id) return true;
        return false;
    };

    void ResetDelayTimer(csPluginFileWatch *plugin);
    void Execute(void);

protected:
    string name;
    time_t delay;
    vector<string> action;
    csTimer *timer;
    static cstimer_id_t timer_index;
};

cstimer_id_t csActionGroup::timer_index = _DELAY_TIMER_BASE;

csActionGroup::csActionGroup(const string &name, time_t delay)
    : name(name), delay(delay), timer(NULL) { }

csActionGroup::~csActionGroup()
{
    if (timer != NULL) delete timer;
}

void csActionGroup::AppendAction(const string &action)
{
    this->action.push_back(action);
}

void csActionGroup::Execute(void)
{
    int rc;
    sigset_t signal_set;
    vector<string>::iterator i;

    delete timer; timer = NULL;

    for (i = action.begin(); i != action.end(); i++) {
        sigemptyset(&signal_set);
        sigaddset(&signal_set, SIGCHLD);
        if ((rc = pthread_sigmask(SIG_UNBLOCK, &signal_set, NULL)) != 0) {
            csLog::Log(csLog::Error, "%s: pthread_sigmask: %s",
                name.c_str(), strerror(rc));
        }

        int rc = ::csExecute((*i));
        csLog::Log(csLog::Debug,
            "%s: %s: %d", name.c_str(), (*i).c_str(), rc);
        if (rc != 0) {
            csLog::Log(csLog::Warning,
                "%s: %s: %d", name.c_str(), (*i).c_str(), rc);
        }

        sigemptyset(&signal_set);
        sigaddset(&signal_set, SIGCHLD);
        if ((rc = pthread_sigmask(SIG_BLOCK, &signal_set, NULL)) != 0) {
            csLog::Log(csLog::Error, "%s: pthread_sigmask: %s",
                name.c_str(), strerror(rc));
        }
    }
}

class csInotifyMask
{
public:
    csInotifyMask(uint32_t mask, const string &action_group,
        const string &pattern, bool is_regex);
    virtual ~csInotifyMask();

    inline uint32_t GetMask(void) { return mask; };
    inline string GetActionGroup(void) { return action_group; };

    bool operator==(const struct inotify_event *iev);

protected:
    uint32_t mask;
    string action_group;
    string pattern;
    csRegEx *regex;
};

csInotifyMask::csInotifyMask(uint32_t mask, const string &action_group,
    const string &pattern, bool is_regex)
    : mask(mask), action_group(action_group), pattern(pattern), regex(NULL)
{
    if (is_regex) regex = new csRegEx(pattern.c_str());
}

csInotifyMask::~csInotifyMask()
{
    if (regex != NULL) delete regex;
}

bool csInotifyMask::operator==(const struct inotify_event *iev)
{
    if (!(mask & iev->mask)) {
#ifdef _CS_DEBUG
        csLog::Log(csLog::Debug, "%s: mask doesn't match: %08x != %08x",
            action_group.c_str(), mask, iev->mask);
#endif
        return false;
    }
    if (iev->len == 1) {
        if (strcmp(pattern.c_str(), _INOTIFY_MASK_SELF)) {
#ifdef _CS_DEBUG
            csLog::Log(csLog::Debug, "%s: len == 1, %s != %s",
                action_group.c_str(), pattern.c_str(), _INOTIFY_MASK_SELF);
#endif
            return false;
        }
        else
            return true;
    }
    if (regex == NULL) {
        if (strcmp(pattern.c_str(), iev->name)) {
#ifdef _CS_DEBUG
            csLog::Log(csLog::Debug, "%s: regex == NULL, %s != %s",
                action_group.c_str(), pattern.c_str(), iev->name);
#endif
            return false;
        }
        else
            return true;
    }
    if (regex->Execute(iev->name) == REG_NOMATCH) {
#ifdef _CS_DEBUG
        csLog::Log(csLog::Debug, "%s: regex != %s",
            action_group.c_str(), iev->name);
#endif
        return false;
    }

    return true;
}

class csInotifyMaskSelf : public csInotifyMask
{
public:
    csInotifyMaskSelf(uint32_t mask, const string &action_group)
        : csInotifyMask(mask, action_group, _INOTIFY_MASK_SELF, false)
    { };
};

class csInotifyWatch
{
public:
    csInotifyWatch(const string &path);
    virtual ~csInotifyWatch();

    void AddMask(csInotifyMask *mask);
    void AddSelf(uint32_t mask, const string &action_group);

    string GetPath(void) const { return path; };

    void Initialize(int fd_inotify);

    bool operator==(const char *path) {
        if (!strcmp(path, this->path.c_str())) return true;
        return false;
    };
    bool operator!=(const char *path) {
        if (!strcmp(path, this->path.c_str())) return false;
        return true;
    };

    bool operator==(const struct inotify_event *iev);

    vector<string> *GetActionGroupMatches(void) {
        return &action_group_matches;
    };

protected:
    int wd;
    uint32_t masksum;
    string path;
    int fd_inotify;
    vector<csInotifyMask *> mask;
    vector<string> action_group_matches;
};

csInotifyWatch::csInotifyWatch(const string &path)
    : wd(-1), masksum(0), path(path), fd_inotify(-1) { }

csInotifyWatch::~csInotifyWatch()
{
    if (fd_inotify != -1 && wd != -1)
        inotify_rm_watch(fd_inotify, wd);
}

void csInotifyWatch::AddMask(csInotifyMask *mask)
{
    this->masksum |= mask->GetMask();
    this->mask.push_back(mask);
}

void csInotifyWatch::AddSelf(uint32_t mask, const string &action_group)
{
    this->masksum |= mask;
    csInotifyMaskSelf *mask_self = new csInotifyMaskSelf(mask, action_group);
    this->mask.push_back(mask_self);
}

void csInotifyWatch::Initialize(int fd_inotify)
{
    this->fd_inotify = fd_inotify;
    if (wd == -1) {
        wd = inotify_add_watch(fd_inotify,
            path.c_str(), masksum | IN_DELETE_SELF);
        if (wd == -1) {
            csLog::Log(csLog::Warning, "inotify_add_watch: %s: %s",
                path.c_str(), strerror(errno));
        }
    }
}

bool csInotifyWatch::operator==(const struct inotify_event *iev)
{
    action_group_matches.clear();

    if (iev->wd != this->wd) return false;

    for (vector<csInotifyMask *>::iterator i = mask.begin();
        i != mask.end(); i++) {
        if ((*(*i)) == iev) {
            bool unique = true;
            vector<string>::iterator acmi;
            for (acmi = action_group_matches.begin();
                acmi != action_group_matches.end(); acmi++) {
                if ((*acmi) != (*i)->GetActionGroup()) continue;
                unique = false;
                break;
            }
            if (unique)
                action_group_matches.push_back((*i)->GetActionGroup());
        }
    }

    if ((iev->mask & IN_DELETE_SELF) ||
        (iev->mask & IN_IGNORED) || (iev->mask & IN_UNMOUNT)) {
        csLog::Log(csLog::Debug, "Removing watch from dirty path: %s",
            path.c_str());
        inotify_rm_watch(fd_inotify, wd);
        wd = -1;
    }

    return (bool)(action_group_matches.size() > 0);
}

class csInotifyConf
{
public:
    enum Type {
        Path,
        Pattern,
        Unknown
    };

    csInotifyConf(uint32_t mask, const string &action_group);
    csInotifyConf(uint32_t mask,
        const string &action_group, const string &path);

    virtual ~csInotifyConf();

    inline void SetPattern(const string &pattern) { this->pattern = pattern; };

    inline Type GetType(void) { return type; };
    inline uint32_t GetMask(void) { return mask; };
    inline string GetActionGroup(void) { return action_group; };
    inline char *GetPath(void) { return _path; };
    inline char *GetPattern(void) { return _pattern; };

    void Resolve(void);

protected:
    Type type;
    uint32_t mask;
    string action_group;
    string path;
    string pattern;
    char *_path;
    char *_pattern;
};

csInotifyConf::csInotifyConf(uint32_t mask, const string &action_group)
    : type(Path), mask(mask), action_group(action_group),
        path("(null)"), pattern("(null)"), _path(NULL), _pattern(NULL) { }

csInotifyConf::csInotifyConf(uint32_t mask,
    const string &action_group, const string &path)
    : type(Pattern), mask(mask), action_group(action_group),
        path(path), pattern("(null)"), _path(NULL), _pattern(NULL) { }

csInotifyConf::~csInotifyConf()
{
    if (_path != NULL) free(_path);
    if (_pattern != NULL) free(_pattern);
}

void csInotifyConf::Resolve(void)
{
    if (_path == NULL) free(_path);
    if (_pattern == NULL) free(_pattern);

    if (type == Pattern) {
        _path = realpath(path.c_str(), NULL);
        if (_path == NULL)
            throw csException(errno, path.c_str());
        _pattern = strdup(pattern.c_str());
        if (_pattern == NULL)
            throw csException(errno, pattern.c_str());

        struct stat path_stat;
        if (stat(_path, &path_stat) < 0)
            throw csException(errno, path.c_str());
        if (!S_ISDIR(path_stat.st_mode))
            throw csException(ENOTDIR, path.c_str());

        return;
    }

    char *buffer, *temp;
    buffer = realpath(pattern.c_str(), NULL);
    if (buffer == NULL)
        throw csException(errno, pattern.c_str());

    struct stat path_stat;
    if (stat(buffer, &path_stat) < 0) {
        free(buffer);
        throw csException(errno, pattern.c_str());
    }
    if (S_ISDIR(path_stat.st_mode)) {
        _path = buffer;
        return;
    }

    temp = dirname(buffer);
    if (temp == NULL) {
        free(buffer);
        throw csException(EINVAL, pattern.c_str());
    }
    _path = strdup(temp);
    free(buffer);

    buffer = realpath(pattern.c_str(), NULL);
    if (buffer == NULL)
        throw csException(errno, pattern.c_str());
    temp = basename(buffer);
    if (temp == NULL) {
        free(buffer);
        throw csException(EINVAL, pattern.c_str());
    }
    _pattern = strdup(temp);
    free(buffer);
}

class csPluginXmlParser : public csXmlParser
{
public:
    virtual void ParseElementOpen(csXmlTag *tag);
    virtual void ParseElementClose(csXmlTag *tag);

protected:
    void ParseFileWatchOpen(csXmlTag *tag, uint32_t mask);
    void ParseFileWatchClose(csXmlTag *tag, const string &text);
};

class csPluginConf : public csConf
{
public:
    csPluginConf(csPluginFileWatch *parent,
        const char *filename, csPluginXmlParser *parser)
        : csConf(filename, parser), parent(parent) { };

    virtual void Reload(void);

protected:
    friend class csPluginXmlParser;

    csPluginFileWatch *parent;
};

void csPluginConf::Reload(void)
{
    csConf::Reload();
    parser->Parse();
}

class csPluginFileWatch : public csPlugin
{
public:
    csPluginFileWatch(const string &name,
        csEventClient *parent, size_t stack_size);
    virtual ~csPluginFileWatch();

    virtual void SetConfigurationFile(const string &conf_filename);

    virtual void *Entry(void);

protected:
    friend class csPluginXmlParser;

    ssize_t InotifyRead(void);
    void InotifyEvent(const struct inotify_event *iev);
    bool AddWatch(csInotifyConf *conf_watch);

    csPluginConf *conf;
    vector<csInotifyWatch *> watch;
    map<string, csActionGroup *> action_group;
    vector<csInotifyConf *> dirty_conf;
    int pages;
    long page_size;
    int fd_inotify;
    uint8_t *buffer;
    csTimer *dirty_timer;
};

csPluginFileWatch::csPluginFileWatch(const string &name,
    csEventClient *parent, size_t stack_size)
    : csPlugin(name, parent, stack_size), conf(NULL),
    pages(1), buffer(NULL), dirty_timer(NULL)
{
    fd_inotify = inotify_init1(IN_NONBLOCK | IN_CLOEXEC);
    if (fd_inotify < 0) {
        csLog::Log(csLog::Error, "%s: inotify_init1: %s",
            name.c_str(), strerror(errno));
        return;
    }

    page_size = ::csGetPageSize();
    buffer = (uint8_t *)realloc(NULL, page_size);
    if (buffer == NULL)
        throw csException(ENOMEM, "inotify buffer");

    dirty_timer = new csTimer(_DIRTY_TIMER_ID,
        _DEFAULT_DELAY, _DIRTY_TIMER_VALUE, this);

    csLog::Log(csLog::Debug, "%s: Initialized.", name.c_str());
}

csPluginFileWatch::~csPluginFileWatch()
{
    Join();

    if (dirty_timer != NULL) delete dirty_timer;
    for (vector<csInotifyConf *>::iterator i = dirty_conf.begin();
        i != dirty_conf.end(); i++) delete (*i);
    for (vector<csInotifyWatch *>::iterator i = watch.begin();
        i != watch.end(); i++) delete (*i);
    for (map<string, csActionGroup *>::iterator i = action_group.begin();
        i != action_group.end(); i++) delete i->second;
    if (conf) delete conf;
    if (buffer) free(buffer);
    if (fd_inotify != -1) close(fd_inotify);
}

void csPluginFileWatch::SetConfigurationFile(const string &conf_filename)
{
    if (conf == NULL) {
        csPluginXmlParser *parser = new csPluginXmlParser();
        conf = new csPluginConf(this, conf_filename.c_str(), parser);
        parser->SetConf(dynamic_cast<csConf *>(conf));
        conf->Reload();
    }
}

void *csPluginFileWatch::Entry(void)
{
    if (fd_inotify == -1) return NULL;

    dirty_timer->Start();

    ssize_t len;

    for ( ;; ) {
        if ((len = InotifyRead()) < 0) break;
        else {
            uint8_t *ptr = buffer;
            struct inotify_event *iev = (struct inotify_event *)ptr;
	        while (len > 0) {
                InotifyEvent(iev);
                ptr += sizeof(struct inotify_event) + iev->len;
                len -= sizeof(struct inotify_event) + iev->len;
                iev = (struct inotify_event *)ptr;
            }
        }

        csEvent *event = EventPopWait(500);
        if (event == NULL) continue;

        switch (event->GetId()) {
        case csEVENT_QUIT:
            delete event;
            return NULL;

        case csEVENT_TIMER:
        {
            csTimer *timer =
                static_cast<csTimerEvent *>(event)->GetTimer();
            if (timer->GetId() == _DIRTY_TIMER_ID) {
                csLog::Log(csLog::Debug,
                    "%s: Initializing inotify watches", name.c_str());
                for (vector<csInotifyConf *>::iterator i = dirty_conf.begin();
                    i != dirty_conf.end(); i++) {
                    try {
                        if (!AddWatch((*i))) continue;
                    } catch (csException &e) {
                        delete (*i);
                        csLog::Log(csLog::Warning,
                            "%s: Error creating watch: %s: %s",
                            name.c_str(), e.estring.c_str(), e.what()); 
                    }
                    dirty_conf.erase(i);
                    i = dirty_conf.begin();
                    if (i == dirty_conf.end()) break;
                }
                for (vector<csInotifyWatch *>::iterator i = watch.begin();
                    i != watch.end(); i++) (*i)->Initialize(fd_inotify);
                break;
            }

            map<string, csActionGroup *>::iterator i;
            for (i = action_group.begin(); i != action_group.end(); i++) {
                if (*(i->second) != timer->GetId()) continue;
                i->second->Execute();
                break;
            }
            break;
        }

        default:
            break;
        }

        delete event;
    }

    return NULL;
}

ssize_t csPluginFileWatch::InotifyRead(void)
{
    uint8_t *ptr = buffer;
    ssize_t bytes;
    ssize_t bytes_read = 0;
    size_t buffer_len = page_size * pages;

    for ( ;; ) {
        bytes = read(fd_inotify, (void *)ptr, buffer_len);
        if (bytes < 0) {
            if (errno == EAGAIN) break;
            csLog::Log(csLog::Error, "%s: inotify read: %s",
                name.c_str(), strerror(errno));
            bytes_read = -1;
            break;
        }

        bytes_read += bytes;
        buffer_len -= bytes;
        ptr += bytes;

        if (buffer_len <= 0) {
            buffer = (uint8_t *)realloc(buffer, page_size * ++pages);
            if (buffer == NULL) {
                csLog::Log(csLog::Error, "%s: inotify buffer: %s",
                    name.c_str(), strerror(ENOMEM));
                bytes_read = -1;
                break;
            }
            buffer_len += page_size;
            ptr = buffer + bytes_read;
            csLog::Log(csLog::Debug,
                "%s: increased inotify buffer to %ld bytes.",
                name.c_str(), page_size * pages);
        }
    }

    return bytes_read;
}

void csPluginFileWatch::InotifyEvent(const struct inotify_event *iev)
{
#ifdef _CS_DEBUG
    csLog::Log(csLog::Debug,
        "%s: mask: %08x, cookie: %8x, len: %d, name: %s",
        name.c_str(), iev->mask, iev->cookie, iev->len,
        (iev->len > 1) ? iev->name : "(null)");
#endif
    for (vector<csInotifyWatch *>::iterator i = watch.begin();
        i != watch.end(); i++) {
        if ((*(*i)) == iev) {
            vector<string> *matches = (*i)->GetActionGroupMatches();
            vector<string>::iterator mi = matches->begin();
            for ( ; mi != matches->end(); mi++) {
                map<string, csActionGroup *>::iterator agi;
                agi = action_group.find((*mi));
                if (agi == action_group.end()) continue;
                agi->second->ResetDelayTimer(this);
            }
        }
    }
}

bool csPluginFileWatch::AddWatch(csInotifyConf *conf_watch)
{
    csLog::Log(csLog::Debug, "%s: conf_watch: %d, %08x, %s, %s, %s",
        name.c_str(), conf_watch->GetType(), conf_watch->GetMask(),
        conf_watch->GetActionGroup().c_str(),
        (conf_watch->GetPath() == NULL) ? "(null)" : conf_watch->GetPath(),
        (conf_watch->GetPattern() == NULL) ? "(null)" : conf_watch->GetPattern());

    try {
        conf_watch->Resolve();
    } catch (csException &e) {
        csLog::Log(csLog::Warning, "%s: Error creating watch: %s: %s",
            name.c_str(), e.estring.c_str(), e.what()); 
        return false;
    }

    csInotifyWatch *inotify_watch = NULL;
    for (vector<csInotifyWatch *>::iterator i = watch.begin();
        i != watch.end(); i++) {
        if ((*(*i)) != conf_watch->GetPath()) continue;
        inotify_watch = (*i);
        break;
    }

    if (inotify_watch == NULL) {
        inotify_watch = new csInotifyWatch(conf_watch->GetPath());
        watch.push_back(inotify_watch);
    }

    if (conf_watch->GetPattern() == NULL) {
        inotify_watch->AddSelf(conf_watch->GetMask(),
            conf_watch->GetActionGroup());
    }
    else {
        csInotifyMask *inotify_mask = new csInotifyMask(
            conf_watch->GetMask(),
            conf_watch->GetActionGroup(),
            conf_watch->GetPattern(),
            (conf_watch->GetType() == csInotifyConf::Pattern));
        inotify_watch->AddMask(inotify_mask);
    }

    delete conf_watch;
    return true;
}

void csActionGroup::ResetDelayTimer(csPluginFileWatch *plugin)
{
    if (timer != NULL)
        timer->SetValue(delay);
    else {
        cstimer_id_t id = 0;

        ::csCriticalSection::Lock();
        id = timer_index++;
        if (id == 0) id = _DELAY_TIMER_BASE;
        ::csCriticalSection::Unlock();

        timer = new csTimer(id, delay, 0, plugin);
        timer->Start();
    }
}

void csPluginXmlParser::ParseElementOpen(csXmlTag *tag)
{
    if ((*tag) == "on-access") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_ACCESS);
    }
    else if ((*tag) == "on-attrib") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_ATTRIB);
    }
    else if ((*tag) == "on-close") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_CLOSE_NOWRITE);
    }
    else if ((*tag) == "on-close-write") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_CLOSE_WRITE);
    }
    else if ((*tag) == "on-create") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_CREATE);
    }
    else if ((*tag) == "on-delete") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_DELETE | IN_DELETE_SELF);
    }
    else if ((*tag) == "on-modify") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_MODIFY);
    }
    else if ((*tag) == "on-move") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_MOVE_SELF | IN_MOVED_FROM | IN_MOVED_TO);
    }
    else if ((*tag) == "on-open") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_OPEN);
    }
    else if ((*tag) == "on-all") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchOpen(tag, IN_ALL_EVENTS);
    }
    else if ((*tag) == "action-group") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        if (!tag->ParamExists("name"))
            ParseError("name parameter missing");

        time_t delay = _DEFAULT_DELAY;
        if (tag->ParamExists("delay"))
            delay = (time_t)atoi(tag->GetParamValue("delay").c_str());

        csActionGroup *action_group = new csActionGroup(
            tag->GetParamValue("name"), delay);
        tag->SetData((void *)action_group);
    }
    else if ((*tag) == "action") {
        if (!stack.size() || (*stack.back()) != "action-group")
            ParseError("unexpected tag: " + tag->GetName());
    }
}

void csPluginXmlParser::ParseElementClose(csXmlTag *tag)
{
    string text = tag->GetText();
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if ((*tag) == "on-access" || (*tag) == "on-attrib" ||
        (*tag) == "on-close" || (*tag) == "on-close-write" ||
        (*tag) == "on-create" || (*tag) == "on-delete" ||
        (*tag) == "on-modify" || (*tag) == "on-move" ||
        (*tag) == "on-open" || (*tag) == "on-all") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        ParseFileWatchClose(tag, text);
    }
    else if ((*tag) == "action-group") {
        if (!stack.size() || (*stack.back()) != "plugin")
            ParseError("unexpected tag: " + tag->GetName());
        csActionGroup *action_group = (csActionGroup *)tag->GetData();
        map<string, csActionGroup *>::iterator agi;
        agi = _conf->parent->action_group.find(action_group->GetName());
        if (agi != _conf->parent->action_group.end()) {
            delete action_group;
            ParseError("duplicate action group: " + agi->second->GetName());
        }
        _conf->parent->action_group[action_group->GetName()] = action_group;
    }
    else if ((*tag) == "action") {
        if (!stack.size() || (*stack.back()) != "action-group")
            ParseError("unexpected tag: " + tag->GetName());
        csXmlTag *tag_parent = stack.back();
        csActionGroup *action_group = (csActionGroup *)tag_parent->GetData();
        action_group->AppendAction(text);
    }
}

void csPluginXmlParser::ParseFileWatchOpen(csXmlTag *tag, uint32_t mask)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if (!tag->ParamExists("type"))
        ParseError("type parameter missing");
    if (!tag->ParamExists("action-group"))
        ParseError("action-group parameter missing");

    csInotifyConf::Type type = csInotifyConf::Unknown;
    if (!strncasecmp(tag->GetParamValue("type").c_str(), "path", 4))
        type = csInotifyConf::Path;
    else if (!strncasecmp(
        tag->GetParamValue("type").c_str(), "pattern", 7)) {
        type = csInotifyConf::Pattern;
        if (!tag->ParamExists("path"))
            ParseError("path parameter missing");
    }

    if (type == csInotifyConf::Unknown)
        ParseError("unknown watch type: " + tag->GetParamValue("type"));

    csInotifyConf *conf_watch = NULL;
    if (type == csInotifyConf::Path) {
        conf_watch = new csInotifyConf(mask,
            tag->GetParamValue("action-group"));
    }
    else {
        conf_watch = new csInotifyConf(mask,
            tag->GetParamValue("action-group"),
            tag->GetParamValue("path"));
    }
    tag->SetData((void *)conf_watch);
}

void csPluginXmlParser::ParseFileWatchClose(csXmlTag *tag, const string &text)
{
    csPluginConf *_conf = static_cast<csPluginConf *>(conf);

    if (!text.size())
        ParseError("missing value for tag: " + tag->GetName());

    csInotifyConf *conf_watch = (csInotifyConf *)tag->GetData();

    if (conf_watch->GetType() == csInotifyConf::Pattern) {
        try {
            csRegEx *regex = new csRegEx(text.c_str());
        }
        catch (csException &e) {
            csLog::Log(csLog::Warning, "%s: Error creating watch: %s: %s",
                _conf->parent->name.c_str(), e.estring.c_str(), e.what()); 
            delete conf_watch;
        }
    }
    conf_watch->SetPattern(text);
   
    try { 
        if (!_conf->parent->AddWatch(conf_watch))
            _conf->parent->dirty_conf.push_back(conf_watch);
    } catch (csException &e) {
        delete conf_watch;
        csLog::Log(csLog::Warning, "%s: Error creating watch: %s: %s",
            _conf->parent->name.c_str(), e.estring.c_str(), e.what()); 
    }
}

csPluginInit(csPluginFileWatch);

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
