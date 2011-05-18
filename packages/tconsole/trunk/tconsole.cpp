#include <unistd.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>

#include <iostream>
#include <string>
#include <iomanip>

#include <sys/ioctl.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/stat.h>

#include <math.h>
#include <fcntl.h>
#include <getopt.h>
#include <errno.h>
#include <signal.h>

#include <ncursesw/ncurses.h>

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "tconsole.h"
#include "thread.h"
#include "util.h"

#define SLEEP_DELAY             50000

extern int errno;
static bool idle_pause = false;
static int update_interval = UPDATE_INTERVAL;

using namespace std;

ccConsole *ccConsole::instance = NULL;
ccEventServer *ccEventServer::instance = NULL;

void signal_handler(int sig)
{
    if (ccEventServer::Instance()) {
        ccEventSignal *event = new ccEventSignal(sig);
        ccEventServer::Instance()->PostEvent(event);
    }
}

int main(int argc, char *argv[])
{
    const char *devnull = "/dev/null";
    const char *debug = devnull;
    int exit_code = 0;

    for ( ;; ) {
        int rc, o = 0;

        static struct option options[] = {
            { "help", 0, 0, '?' },
            { "interval", 1, 0, 'i' },
            { "debug", 1, 0, 'd' },
            { NULL, 0, 0, 0 }
        };

        if ((rc = getopt_long(argc, argv,
            "i:d:h?", options, &o)) == -1) break;

        switch (rc) {
        case 'i':
            update_interval = atoi(optarg);
            if (update_interval <= 0)
                update_interval = UPDATE_INTERVAL;
            break;
        case 'd':
            debug = optarg;
            break;

        case '?':
        case 'h':
            cout << "tConsole v" << VERSION << endl;
            cout << "Copyright (C) 2010-2011 ClearFoundation" << endl;
            cout << "To report bugs, go to: " << PACKAGE_BUGREPORT << endl;
            cout << "  -i, --interval <n>" << endl;
            cout << "    Specify update interval in seconds (default: "
                 << UPDATE_INTERVAL << "s)" << endl;
            cout << "  -d, --debug <log>" << endl;
            cout << "    Enable debug mode and write to: <log>" << endl;
            exit(0);
            break;
        }
    }

    if (optind < argc) {
        int fd;
        ostringstream device;
        device << "/dev/" << argv[argc - 1];

        if ((fd = open(device.str().c_str(), O_RDWR)) == -1) { }
        else if (ioctl(fd, TIOCSCTTY, 1) == -1) { }
        else {
            close(0);
            close(1);
            close(2);

            dup(fd);
            dup(fd);
            dup(fd);
            close(fd);

            setsid();
        }
    }

    close(2);
    int fd = open(debug, O_WRONLY | O_APPEND | O_CREAT | O_EXCL, S_IRUSR | S_IWUSR);

    if (fd == -1)
        fd = open(debug, O_WRONLY | O_APPEND | O_CREAT, S_IRUSR | S_IWUSR);

    setlocale(LC_ALL, "");

    cerr << "tConsole v" << VERSION << endl;
    ccThreadEvent *event_thread = new ccThreadEvent();
    event_thread->Run();

    ccConsole *console = new ccConsole();

    signal(SIGINT, signal_handler);
    signal(SIGTERM, signal_handler);
    signal(SIGHUP, signal_handler);
    signal(SIGCHLD, signal_handler);
    signal(SIGWINCH, signal_handler);

    try
    {
        exit_code = console->EventLoop();
    }
    catch (ccSingleInstanceException &e)
    {
        endwin();
        cerr << "Single instance exception: " << e.GetClassName() << endl;
        exit_code = 1;
    }
    catch (ccException &e)
    {
        endwin();
        cerr << "ccException: " << e.what() << endl;
        exit_code = 1;
    }
    catch (exception &e)
    {
        endwin();
        cerr << "std::exception: " << e.what() << endl;
        exit_code = 1;
    }

    delete console;

    event_thread->Destroy();
    event_thread->Wait();

    delete event_thread;

    return exit_code;
}

ccEvent::ccEvent(ccEventType type, ccEventClient *src, ccEventClient *dst)
    : type(type), src(src), dst(dst) { }

ccEventOutput::ccEventOutput(const string &text, ccEventClient *src)
    : ccEvent(ccEVT_OUTPUT, src, ccConsole::Instance()), text(text) { }

ccEventOutput::ccEventOutput(ostringstream &stream, ccEventClient *src)
    : ccEvent(ccEVT_OUTPUT, src, ccConsole::Instance())
{
    text = stream.str();
}

ccEventFault::ccEventFault(const string &reason)
    : ccEvent(ccEVT_FAULT, NULL, ccConsole::Instance()), reason(reason) { }

ccEventKeyPress::ccEventKeyPress(int key, ccEventClient *src)
    : ccEvent(ccEVT_KEY_PRESS, src, NULL), key(key) { }

ccEventSignal::ccEventSignal(int sig)
    : ccEvent(ccEVT_SIGNAL, NULL, ccConsole::Instance()), sig(sig) { }

ccEventClient::ccEventClient(void) { }

ccEventClient::~ccEventClient()
{
    ccEventServer::Instance()->UnregisterClient(this);
}

bool ccEventClient::HandleEvent(ccEvent *event)
{
    ostringstream os;
    os << "Unhandled event: " << hex << event->GetType() << endl;

    ccEventServer::Instance()->PostEvent(new ccEventOutput(os));

    return false;
}

ccEventServer::ccEventServer(void)
{
    if (instance)
        throw ccSingleInstanceException("ccEventServer");

    instance = this;
}

ccEventServer::~ccEventServer()
{
    if (instance == this) instance = NULL;
}

void ccEventServer::PostEvent(ccEvent *event)
{
    ccMutexLocker lock(queue_lock);

    queue.push_back(event);
}

void ccEventServer::DispatchEvents(void)
{
    queue_lock.Lock();

    for (unsigned long i = 0; i < queue.size(); i++) {
        bool handled = false;

        if (queue[i]->GetDestination()) {
            queue_lock.Unlock();
            handled = queue[i]->GetDestination()->HandleEvent(queue[i]);
            queue_lock.Lock();
        }
        else {
            for (vector<client_pair>::reverse_iterator c = client.rbegin();
                !handled && c != client.rend(); c++) {
                if (c->second != queue[i]->GetType()) continue;
                queue_lock.Unlock();
                handled = c->first->HandleEvent(queue[i]);
                queue_lock.Lock();
            }
        }

        if (!handled && ccConsole::Instance()) {
            queue_lock.Unlock();
            ccConsole::Instance()->HandleEvent(queue[i]);
            queue_lock.Lock();
        }

        delete queue[i];
    }

    queue.clear();
    queue_lock.Unlock();
}

ccEventServer *ccEventServer::Instance(void) { return instance; }

void ccEventServer::RegisterEvent(ccEventClient *client, ccEventType type)
{
    ccMutexLocker lock(queue_lock); 
    pair<ccEventClient *, ccEventType> cp(client,type);
    this->client.push_back(cp);
}

void ccEventServer::UnregisterClient(ccEventClient *client)
{
    ccMutexLocker lock(queue_lock);
    vector<client_pair>::iterator i = this->client.begin();

    while (i != this->client.end()) {
        if (i->first != client) i++;
        else {
            this->client.erase(i);
            i = this->client.begin();
        }
    }

    vector<ccEvent *>::iterator j = queue.begin();

    while (j != queue.end()) {
        if ((*j)->GetDestination() != client) j++;
        else {
            queue.erase(j);
            j = queue.begin();
        }
    }
}

ccText::ccText(const string &text, int width) : text(text) { SetWidth(width); }

void ccText::Resize(void)
{
    int w;
    _lines.clear();
    string word, line, last, remaining = text;

    while (remaining.size()) {
        w = remaining.find_first_of(0x20);

        if (w == -1) word = remaining;
        else word = remaining.substr(0, w);

        if (line.size()) line.append(" ");

        line.append(word);

        if (line.size() > (unsigned long)width) {
            if (last.size()) {
                _lines.push_back(last);
                last.clear();

                if (w == -1) {
                    _lines.push_back(word);
                    break;
                }

                line = word;
            }
            else {
                _lines.push_back(line);
                break;
            }
        }

        last = line;

        if (w == -1) break;
        remaining = remaining.substr(w + 1);
    }

    if (w != -1 || (!_lines.size() && line.size())) _lines.push_back(line);
    else if (w == -1 && _lines.size()) {
        if (_lines[_lines.size() - 1] != line) _lines.push_back(line);
    }

    for (unsigned long i = 0; i < _lines.size(); i++)
        cerr << setw(2) << i << ": " << _lines[i] << endl;
}

ccTimer::ccTimer(ccEventClient *parent, bool one_shot)
    : ccThread(ccThread::ccTHREAD_TYPE_JOINABLE),
    parent(parent), one_shot(one_shot), running(false), usec(0) { };

bool ccTimer::Start(uint32_t usec)
{
    if (running) return false;
    else if (!usec) return false;
    else if (!parent) return false;

    this->usec = usec;

    Run();

    return true;
}

bool ccTimer::Stop(void)
{
    if (!running) return false;

    Destroy();
    Wait();

    running = false;

    return true;
}

void *ccTimer::Entry(void)
{
    ccEventTimer *event;

    running = true;

    while (!TestDestroy()) {
        for (uint32_t i = 0u; i < usec && !TestDestroy(); i += 100u) usleep(100u);
        if (TestDestroy()) break;

        event = new ccEventTimer(parent);
        ccEventServer::Instance()->PostEvent(event);

        if (one_shot) break;
    }

    return NULL;
}

ccWindow::ccWindow(ccWindow *parent, const ccSize &size, int bg_cp)
    : ccEventClient(), parent(parent), size(size), window(NULL), bg_cp(bg_cp), visible(true)
{
    if (parent != NULL) {
        parent->AddChild(this);
        window = newwin(this->size.GetHeight(), this->size.GetWidth(),
            this->size.GetY(), this->size.GetX());
    }

    if (bg_cp == -1) this->bg_cp = 6;
    wbkgd(window, COLOR_PAIR(this->bg_cp));
}

ccWindow::~ccWindow()
{
    vector<ccWindow *>::iterator i = child.begin();

    while (i != child.end()) {
        delete (*i);
        i = child.begin();
    }

    if (parent) parent->RemoveChild(this);

    if (window != stdscr) delwin(window);
}

void ccWindow::Draw(void)
{
    if (!visible) return;
    for (unsigned long i = 0; i < child.size(); i++) child[i]->Draw();
}

void ccWindow::Refresh(void)
{
    if (isendwin() || !visible) return;
    wnoutrefresh(window);
    for (unsigned long i = 0; i < child.size(); i++) child[i]->Refresh();
}

void ccWindow::RemoveChild(ccWindow *w)
{
    vector<ccWindow *>::iterator i = child.begin();

    while (i != child.end()) {
        if ((*i) != w) {
            i++;
            continue;
        }

        child.erase(i);
        break;
    }

    Clear();
}

void ccWindow::CenterOnParent(void)
{
    if (!parent) return;

    size.SetX((parent->GetSize().GetWidth() - size.GetWidth()) / 2 + parent->GetSize().GetX());
    size.SetY((parent->GetSize().GetHeight() - size.GetHeight()) / 2 + parent->GetSize().GetY());

    mvwin(window, size.GetY(), size.GetX());
}

ccInputBox::ccInputBox(ccWindow *parent, const ccSize &size, const string &value)
    : ccWindow(parent, size, 6), value(value), style(0u), focus(true)
{
    ccEventServer::Instance()->RegisterEvent(this, ccEVT_KEY_PRESS);
    ccEventServer::Instance()->RegisterEvent(this, ccEVT_TIMER);

    cpos = value.size();
}

ccInputBox::~ccInputBox() { }

void ccInputBox::Draw(void)
{
    wcolor_set(window, bg_cp, NULL);
    wmove(window, 0, 0);
    wclrtoeol(window);

    if (style & ccINPUT_PASSWORD) {
        string mask;
        mask.resize(value.size(), '*');
        wprintw(window, mask.c_str());
    }
    else wprintw(window, value.c_str());
    ccWindow::Draw();
}

bool ccInputBox::HandleEvent(ccEvent *event)
{
    ccEventKeyPress *event_keypress;
    ccEventPaint *event_paint;

    if (!focus) return false;

    switch (event->GetType()) {
    case ccEVT_KEY_PRESS:
        event_keypress = dynamic_cast<ccEventKeyPress *>(event);
    
        switch (event_keypress->GetKey()) {
        case KEY_LEFT:
            if (cpos > 0) cpos--;
            return true;

        case KEY_RIGHT:
            if (cpos < value.size()) cpos++;
            return true;

        case KEY_HOME:
            cpos = 0;
            return true;

        case KEY_END:
            cpos = value.size();
            return true;

        case 0x7f:
        case KEY_BACKSPACE:
            if (cpos > 0 && value.size()) {
                if (cpos == value.size())
                    value = value.substr(0, cpos - 1);
                else {
                    string s = value.substr(0, cpos - 1);
                    s.append(value.substr(cpos, value.size()));
                    value = s;
                }

                cpos--;
            }

            Draw();
            event_paint = new ccEventPaint(this);
            ccEventServer::Instance()->PostEvent(event_paint);
            return true;

        case 0x14a:
            if (!value.size() || cpos == value.size()) return true;

            if (cpos == 0)
                value = value.substr(1, value.size());
            else {
                string s = value.substr(0, cpos);
                s.append(value.substr(cpos + 1, value.size()));
                value = s;
            }

            Draw();
            event_paint = new ccEventPaint(this);
            ccEventServer::Instance()->PostEvent(event_paint);
            return true;

        case 0x15:
        case KEY_EOL:
            value = "";
            cpos = 0;
            Draw();
            event_paint = new ccEventPaint(this);
            ccEventServer::Instance()->PostEvent(event_paint);
            return true;
        }

        if (value.size() == (unsigned long)(size.GetWidth() - 1)) {
            flash(); beep(); return false;
        }

        if (isprint(event_keypress->GetKey())) {
            char key[2];
            key[0] = event_keypress->GetKey();
            key[1] = '\0';

            if (cpos == value.size())
                value.append(key);
            else value.insert(cpos, key);

            cpos++;

            Draw();
            event_paint = new ccEventPaint(this);
            ccEventServer::Instance()->PostEvent(event_paint);
            return true;
        }

        return false;

    default:
        break;
    }

    return false;
}

ccProgressBar::ccProgressBar(ccWindow *parent, const ccSize &size)
    : ccWindow(parent, size, 6), cvalue(0), mvalue(100) { }

ccProgressBar::~ccProgressBar() { }

void ccProgressBar::Update(uint32_t value)
{
    cvalue = this->size.GetWidth() * value / mvalue;
    Draw();
}

void ccProgressBar::Draw(void)
{
    wcolor_set(window, bg_cp, NULL);
    wmove(window, 0, 0);
    wclrtoeol(window);
    wmove(window, 0, 0);
    wcolor_set(window, 1, NULL);
    for (uint32_t i = 0; i < cvalue; i++) wprintw(window, " ");

    ccWindow::Draw();
}

ccDialog::ccDialog(ccWindow *parent, const ccSize &size, const string &title, const string &blurb)
    : ccWindow(parent, size, 6), title(title), selected(ccBUTTON_ID_NONE), button_width(6), user_id(0)
{
    this->blurb = new ccText(blurb, this->size.GetWidth() - 4);
    ccEventServer::Instance()->RegisterEvent(this, ccEVT_KEY_PRESS);
    CenterOnParent();
}

ccDialog::~ccDialog()
{
    if (blurb) delete blurb;
    for (int i = 0; i < button.size(); i++) delete button[i];
}

void ccDialog::Draw(void)
{
    wcolor_set(window, bg_cp, NULL);
    wmove(window, 1, 2);
    wclrtoeol(window);
    wprintw(window, title.c_str());

    box(window, ACS_VLINE, ACS_HLINE);

    for (int i = 0; i < size.GetWidth() - 4; i++) {
        wmove(window, 2, i + 2);
        waddch(window, ACS_HLINE);
    }

    for (int i = 0; i < blurb->GetLineCount(); i++) {
        wmove(window, 4 + i, 2);
        for (int x = 0; x < size.GetWidth() - 4; x++)
            wprintw(window, " ");
        wmove(window, 4 + i, 2);
        wprintw(window, blurb->GetLine(i).c_str());
    }

    if (button.size()) {
        int offset_x = 0;
        int offset_y = size.GetHeight() - 4;
        int width = button.size() * (button_width + 4);

        if (button.size() == 1)
            offset_x = (size.GetWidth() - width) / 2;
        else {
            width += (button.size() - 1);
            offset_x = (size.GetWidth() - width) / 2;
        }

        for (int i = 0; i < button.size(); i++) {
            wmove(window, offset_y, offset_x);
            waddch(window, (button[i]->HasFocus()) ? ACS_ULCORNER : ' ');
            wmove(window, offset_y + 1, offset_x);
            waddch(window, (button[i]->HasFocus()) ? ACS_VLINE : ' ');
            wmove(window, offset_y + 2, offset_x);
            waddch(window, (button[i]->HasFocus()) ? ACS_LLCORNER : ' ');

            for (int j = 0; j < button_width + 2; j++) {
                wmove(window, offset_y, offset_x + j + 1);
                waddch(window, (button[i]->HasFocus()) ? ACS_HLINE : ' ');
                wmove(window, offset_y + 2, offset_x + j + 1);
                waddch(window, (button[i]->HasFocus()) ? ACS_HLINE : ' ');
            }

            wmove(window, offset_y, offset_x + button_width + 2);
            waddch(window, (button[i]->HasFocus()) ? ACS_URCORNER : ' ');
            wmove(window, offset_y + 1, offset_x + button_width + 2);
            waddch(window, (button[i]->HasFocus()) ? ACS_VLINE : ' ');
            wmove(window, offset_y + 2, offset_x + button_width + 2);
            waddch(window, (button[i]->HasFocus()) ? ACS_LRCORNER : ' ');

            wmove(window, offset_y + 1,
                offset_x + (int)(ceil(((float)button_width - (float)button[i]->GetLabel().size()) / 2.0f)) + 1);
            wprintw(window, button[i]->GetLabel().c_str());

            offset_x += button_width + 4;
        }
    }

    ccWindow::Draw();
}

bool ccDialog::HandleEvent(ccEvent *event)
{
    if (!visible) return false;

    ccEventKeyPress *event_keypress;
    ccEventPaint *event_paint;

    switch (event->GetType()) {
    case ccEVT_KEY_PRESS:
        event_keypress = dynamic_cast<ccEventKeyPress *>(event);

        switch (event_keypress->GetKey()) {
        case 0x009:
            if (GetFocus() == FocusNext())
                FocusPrevious();
            break;

        case 0x104:
            FocusPrevious();
            break;

        case 0x105:
            FocusNext();
            break;

        case 0x00d:
            SetSelected();
            break;

        default:
            return true;
        }

        Draw();
        event_paint = new ccEventPaint(this);
        ccEventServer::Instance()->PostEvent(event_paint);

        return true;
    }

    return false;
}

ccButtonId ccDialog::GetFocus(void)
{
    for (int i = 0; i < button.size(); i++)
        if (button[i]->HasFocus()) return button[i]->GetId();

    return ccBUTTON_ID_NONE;
}

void ccDialog::SetFocus(int index)
{
    button.at(index);

    for (int i = 0; i < button.size(); i++)
        button[i]->SetFocus(false);

    button[index]->SetFocus();
}

void ccDialog::SetFocus(ccButtonId id)
{
    for (int i = 0; i < button.size(); i++) {
        if (button[i]->GetId() == id)
            button[i]->SetFocus();
        else
            button[i]->SetFocus(false);
    }
}

ccButtonId ccDialog::FocusNext(void)
{
    if (!button.size()) return ccBUTTON_ID_NONE;

    ccButtonId id = GetFocus();

    if (id == ccBUTTON_ID_NONE || button.size() == 1) {
        button[0]->SetFocus();
        return button[0]->GetId();
    }

    int i = 0;
    for (; i < button.size(); i++) {
        if (!button[i]->HasFocus()) continue;

        button[i]->SetFocus(false);
        break;
    }

    if (i == button.size()) return ccBUTTON_ID_NONE;
    else if (i == button.size() - 1) button[button.size() - 1]->SetFocus();
    else button[i + 1]->SetFocus();

    return GetFocus();
}

ccButtonId ccDialog::FocusPrevious(void)
{
    if (!button.size()) return ccBUTTON_ID_NONE;

    ccButtonId id = GetFocus();

    if (id == ccBUTTON_ID_NONE || button.size() == 1) {
        button[0]->SetFocus();
        return button[0]->GetId();
    }

    int i = button.size() - 1;
    for (; i > -1; i--) {
        if (!button[i]->HasFocus()) continue;

        button[i]->SetFocus(false);
        break;
    }

    if (i == -1) return ccBUTTON_ID_NONE;
    else if (i == 0) button[0]->SetFocus();
    else button[i - 1]->SetFocus();

    return GetFocus();
}

void ccDialog::SetSelected(void)
{
    selected = GetFocus();
    if (selected == ccBUTTON_ID_NONE) return;

    ccEventDialog *event = new ccEventDialog(this, parent, selected);
    ccEventServer::Instance()->PostEvent(event);
}

void ccDialog::AppendButton(ccButtonId id, const string &label, bool focus)
{
    for (int i = 0; i < button.size(); i++) {
        if (button[i]->GetId() != id) continue;
        return;
    }

    button.push_back(new ccButton(id, label));

    if (label.size() > button_width) button_width = label.size();

    if(focus) SetFocus(id);
}

ccDialogLogin::ccDialogLogin(ccWindow *parent)
    : ccDialog(parent, ccSize(0, 0, 42, 12), "Login", "Administrator password:")
{
    SetBackgroundPair(9);
    AppendButton(ccBUTTON_ID_LOGIN, "Login", true);

    passwd = new ccInputBox(this, ccSize(0, 0, 32, 1));
    passwd->SetPassword();

    Resize();
}

ccDialogProgress::ccDialogProgress(ccWindow *parent, const string &title, bool install)
    : ccDialog(parent, ccSize(0, 0, 42, 11), title, ""),
    install(install), progress1(NULL), progress2(NULL)
{
    SetBackgroundPair(9);

    progress1 = new ccProgressBar(this, ccSize(0, 0, 32, 1));
    if (install) progress2 = new ccProgressBar(this, ccSize(0, 0, 32, 1));

    Resize();
}

void ccDialogProgress::Update(const string &update)
{
    // Downloading: (1/75): xorg-x11-xauth-1.0.1-2.1.i386.rpm: 0
    ccRegEx downloading("^(Downloading): \\(([0-9]*)/([0-9]*)\\): .*: ([0-9]*)", 5);
    // Installing: gnome-mime-data - 2.4.2-3.1.i386 865292/3572212 [1/75]
    ccRegEx installing("^(Installing): (.*) - .* ([0-9]*)/([0-9]*) \\[([0-9]*)/([0-9]*)", 7);
    // Removing: gnome-mime-data
    ccRegEx removing("^(Removing): (.*): ([0-9]*)/([0-9]*)", 5);

    if (install && downloading.Execute(update.c_str()) == 0) {
        ostringstream os;
        os << downloading.GetMatch(1);
        os << " package " << downloading.GetMatch(2);
        os << " of " << downloading.GetMatch(3) << ":";
        SetBlurb(os.str());
        progress1->SetRange(100);
        progress1->Update(atoi(downloading.GetMatch(4)));
        progress2->SetRange(atoi(downloading.GetMatch(3)));
        progress2->Update(atoi(downloading.GetMatch(2)));
    }
    else if (install && installing.Execute(update.c_str()) == 0) {
        ostringstream os;
        os << installing.GetMatch(1);
        os << ": " << installing.GetMatch(2);
        SetBlurb(os.str());
        progress1->SetRange(atoi(installing.GetMatch(4)));
        progress1->Update(atoi(installing.GetMatch(3)));
        progress2->SetRange(atoi(installing.GetMatch(6)));
        progress2->Update(atoi(installing.GetMatch(5)));
    }
    else if (!install && removing.Execute(update.c_str()) == 0) {
        ostringstream os;
        os << removing.GetMatch(1);
        os << ": " << removing.GetMatch(2);
        SetBlurb(os.str());
        progress1->SetRange(atoi(removing.GetMatch(4)));
        progress1->Update(atoi(removing.GetMatch(3)));
    }
    else SetBlurb("Preparing...");
}

ccMenu::ccMenu(ccWindow *parent, const ccSize &size, const string &title)
    : ccWindow(parent, size, 6), title(title), menu_width(0) { }

ccMenu::~ccMenu()
{
    for (int i = 0; i < item.size(); i++) delete item[i];
}

void ccMenu::Draw(void)
{
    if (!visible) return;

    wcolor_set(window, bg_cp, NULL);
    wmove(window, 1, 2);
    wclrtoeol(window);
    wprintw(window, title.c_str());

    int offset_y = 4;
    int offset_x = (size.GetWidth() - menu_width) / 2;

    for (int i = 0; i < item.size(); i++) {
        if (!item[i]->IsVisible()) continue;
        if (item[i]->IsSeperator()) {
            for (int x = 0; x < menu_width; x++) {
                wmove(window, offset_y, offset_x + x);
                waddch(window, ACS_HLINE);
            }

            offset_y++;
            continue;
        }

        wmove(window, offset_y, 1);
        wclrtoeol(window);

        if (item[i]->GetHotkey()) {
            wmove(window, offset_y,
                offset_x - (item[i]->GetHotkeyTitle().size() + 2));
            wcolor_set(window, 8, NULL);
            wprintw(window, item[i]->GetHotkeyTitle().c_str());
            wcolor_set(window, bg_cp, NULL);
        }

        wmove(window, offset_y, offset_x - 1);

        if (item[i]->IsSelected()) {
            wcolor_set(window, 7, NULL);
            wprintw(window, " %s", item[i]->GetTitle().c_str());
            for (int x = 0; x < (menu_width - item[i]->GetTitle().size()) + 1; x++)
                waddch(window, ' ');
            wcolor_set(window, bg_cp, NULL);
        }
        else wprintw(window, " %s", item[i]->GetTitle().c_str());

        offset_y++;
    }

    box(window, ACS_VLINE, ACS_HLINE);

    for (int i = 0; i < size.GetWidth() - 4; i++) {
        wmove(window, 2, i + 2);
        waddch(window, ACS_HLINE);
    }

    ccWindow::Draw();
}

void ccMenu::Resize(void)
{
    ccSize view_size = parent->GetSize();

    int items = 0;
    for (int i = 0; i < item.size(); i++) {
        if (!item[i]->IsVisible()) continue;
        items++;
    }
    size.SetWidth(menu_width + 11);
    size.SetHeight(items + 6);

    size.SetX((view_size.GetWidth() - size.GetWidth()) / 2);
    size.SetY((view_size.GetHeight() - size.GetHeight()) / 2);

    wresize(window, size.GetHeight(), size.GetWidth());
    mvwin(window, size.GetY(), size.GetX());

    wclear(window);
}

bool ccMenu::HandleEvent(ccEvent *event)
{
    return false;
}

void ccMenu::InsertItem(ccMenuItem *item)
{
    this->item.push_back(item);
    CalcMenuWidth();
}

void ccMenu::RemoveItem(ccMenuId id)
{
    for (vector<ccMenuItem *>::iterator i = item.begin(); i != item.end(); i++) {
        if ((*i)->GetId() != id) continue;

        delete (*i);
        item.erase(i);

        CalcMenuWidth();
        break;
    }
}

void ccMenu::SetItemVisible(ccMenuId id, bool visible)
{
    for (vector<ccMenuItem *>::iterator i = item.begin(); i != item.end(); i++) {
        if ((*i)->GetId() != id) continue;

        (*i)->SetVisible(visible);
        CalcMenuWidth();
        break;
    }
}

void ccMenu::CalcMenuWidth(void)
{
    menu_width = 0;

    for (int i = 0; i < item.size(); i++) {
        if (item[i]->GetTitle().size() > menu_width)
            menu_width = item[i]->GetTitle().size();
    }
}

void ccMenu::SelectItem(ccMenuId id)
{
    if (id == ccMENU_ID_SEPERATOR) return;

    for (int i = 0; i < item.size(); i++) {
        if (item[i]->GetId() == id) item[i]->SetSelected();
        else item[i]->SetSelected(false);
    }
}

bool ccMenu::SelectItem(int hotkey)
{
    ccMenuId id = ccMENU_ID_INVALID;

    for (int i = 0; i < item.size(); i++) {
        if (item[i]->GetHotkey() != hotkey ||
            !item[i]->IsVisible()) continue;
        id = item[i]->GetId();
        break;
    }

    if (id != ccMENU_ID_INVALID) {
        SelectItem(id);
        return true;
    }

    return false;
}

void ccMenu::SelectFirst(void)
{
    if (item.size()) SelectItem(item[0]->GetId());
}

void ccMenu::SelectNext(void)
{
    ccMenuId id = GetSelected();

    if (id == ccMENU_ID_INVALID) {
        if (item.size()) item[0]->SetSelected();
        return;
    }

    int i = 0;
    for (; i < item.size(); i++)
        if (item[i]->GetId() == id) break;

    if (i == item.size() - 1) return;

    int deselect = i;
    for (i = i + 1; i < item.size(); i++) {
        if (item[i]->GetId() == ccMENU_ID_SEPERATOR ||
            !item[i]->IsVisible()) continue;
        item[deselect]->SetSelected(false);
        item[i]->SetSelected();
        break;
    }
}

void ccMenu::SelectPrevious(void)
{
    ccMenuId id = GetSelected();

    if (id == ccMENU_ID_INVALID) {
        if (item.size()) item[0]->SetSelected();
        return;
    }

    int i = 0;
    for (; i < item.size(); i++)
        if (item[i]->GetId() == id) break;

    if (i == 0) return;

    int deselect = i;

    for (i = i - 1; i >= 0; i--) {
        if (item[i]->GetId() == ccMENU_ID_SEPERATOR ||
            !item[i]->IsVisible()) continue;
        item[deselect]->SetSelected(false);
        item[i]->SetSelected();
        break;
    }
}

ccMenuId ccMenu::GetSelected(void)
{
    for (int i = 0; i < item.size(); i++) {
        if (item[i]->IsSelected()) return item[i]->GetId();
    }

    return ccMENU_ID_INVALID;
}

ccMenuItem::ccMenuItem(ccMenuId id, const string &title, int hotkey, const string &hotkey_title)
    : id(id), title(title), hotkey(hotkey), hotkey_title(hotkey_title), selected(false), visible(true) { }

ccConsole::ccConsole()
    : ccWindow(NULL, ccSize(0, 0, 80, 24)),
    run(true), proc_exec(NULL), proc_pipe(NULL),
    dialog(NULL), login(NULL), sleep_mode(false)
{
    if (instance) throw ccSingleInstanceException("ccConsole");

    instance = this;

    window = initscr();

    size.SetWidth(COLS);
    size.SetHeight(LINES);

    if (has_colors()) {
        start_color();
        assume_default_colors(COLOR_WHITE, COLOR_BLACK);
        init_pair(1, COLOR_WHITE, COLOR_BLACK);
        init_pair(2, COLOR_CYAN, COLOR_BLACK);
        init_pair(3, COLOR_BLUE, COLOR_BLACK);
        init_pair(4, COLOR_RED, COLOR_BLACK);
        init_pair(5, COLOR_GREEN, COLOR_BLACK);
        init_pair(6, COLOR_BLACK, COLOR_WHITE);
        init_pair(7, COLOR_WHITE, COLOR_BLUE);
        init_pair(8, COLOR_YELLOW, COLOR_WHITE);
        init_pair(9, COLOR_WHITE, COLOR_RED);
    }

    cbreak();
    noecho();
    nonl();
    nodelay(stdscr, TRUE);
    intrflush(stdscr, FALSE);
    keypad(stdscr, TRUE);

    ccSize menu_size(20, 7, 40, 10);
    menu = new ccMenu(this, menu_size, "Welcome!");
    menu->SetVisible(false);

    menu->InsertItem(new ccMenuItem(ccMENU_ID_CON_GUI,
        "Launch Graphics-mode Console", 0x10a, "F2"));
    menu->InsertItem(new ccMenuItem(ccMENU_ID_CON_GUI_INSTALL,
        "Install Graphics-mode Console"));
    menu->InsertItem(new ccMenuItem(ccMENU_ID_CON_GUI_REMOVE,
        "Remove Graphics-mode Console"));
    menu->InsertItem(new ccMenuSpacer());
    menu->InsertItem(new ccMenuItem(ccMENU_ID_UTIL_IPTRAF,
        "Network Analyzer (IPTraf)", 0x10c, "F4"));
    menu->InsertItem(new ccMenuSpacer());
    menu->InsertItem(new ccMenuItem(ccMENU_ID_SYS_REBOOT,
        "System Restart (Reboot)"));
    menu->InsertItem(new ccMenuItem(ccMENU_ID_SYS_SHUTDOWN,
        "System Shutdown (Halt)"));
    menu->InsertItem(new ccMenuSpacer());
    menu->InsertItem(new ccMenuItem(ccMENU_ID_LOGOUT,
        "Logout"));

    if (UpdateGraphicalConsoleItems()) {
        menu->SelectItem(ccMENU_ID_CON_GUI);
        LaunchProcess(menu->GetSelected());
    }
    else {
        menu->SelectFirst();
        menu->SelectNext();
    }

    update_thread = new ccThreadUpdate();
    update_thread->Run();

    ccEventSignal *event = new ccEventSignal(SIGWINCH);
    ccEventServer::Instance()->PostEvent(event);

    login = new ccDialogLogin(this);
}

ccConsole::~ccConsole()
{
    if (instance == this) {
        if (login_thread) {
            login_thread->Destroy();
            login_thread->Wait();
            delete login_thread;
        }

        update_thread->Destroy();
        update_thread->Wait();

        delete update_thread;

        if (proc_exec) delete proc_exec;
        if (proc_pipe) delete proc_pipe;

        instance = NULL;
        endwin();
    }
}

ccConsole *ccConsole::Instance(void)
{
    return instance;
}

int ccConsole::EventLoop(void)
{
    int c;
    ccEventServer *server;
    activity = time(NULL);

    while (run) {
        ncurses_lock.Lock();

        if (isendwin()) {
            ncurses_lock.Unlock();

            usleep(SLEEP_DELAY);
            continue;
        }

        c = getch();

        ncurses_lock.Unlock();

        if (c == ERR || !(server = ccEventServer::Instance())) {
            ccMutexLocker locker(timer_lock);

            if (!idle_pause && time(NULL) > activity + IDLE_TIMEOUT) {
                if (menu->IsVisible()) {
                    menu->SetVisible(false);
                    ncurses_lock.Lock();
                    Draw();
                    ncurses_lock.Unlock();
                }

                ccEventFault *event = new ccEventFault("Access denied");
                ccEventServer::Instance()->PostEvent(event);
            }

            usleep(SLEEP_DELAY);
            continue;
        }

        timer_lock.Lock();
        activity = time(NULL);
        timer_lock.Unlock();

        ccEventKeyPress *event = new ccEventKeyPress(c);
        server->PostEvent(event);
    }

    return 0;
}

bool ccConsole::HandleEvent(ccEvent *event)
{
    ccEventKeyPress *event_keypress;
    ccEventSignal *event_signal;
    ccEventOutput *event_output;
    ccEventFault *event_fault;
    ccEventSysInfo *event_sysinfo;
    ccEventDialog *event_dialog;
    ccEventProcess *event_process;

    ccMutexLocker locker(ncurses_lock);

    switch (event->GetType()) {
    case ccEVT_KEY_PRESS:
        event_keypress = dynamic_cast<ccEventKeyPress *>(event);

        if (!menu->IsVisible()) return false;

        switch (event_keypress->GetKey()) {
        case KEY_DOWN:
            menu->SelectNext();
            Draw();
            return true;

        case KEY_UP:
            menu->SelectPrevious();
            Draw();
            return true;
        }

        if (event_keypress->GetKey() == 0x0d ||
            menu->SelectItem(event_keypress->GetKey())) {
            if (menu->GetSelected() == ccMENU_ID_SYS_REBOOT ||
                menu->GetSelected() == ccMENU_ID_SYS_SHUTDOWN) {
                string blurb;
                if (menu->GetSelected() == ccMENU_ID_SYS_REBOOT)
                    blurb = "Are you sure you want to restart?";
                else
                    blurb = "Are you sure you want to shutdown?";

                dialog = new ccDialog(this, ccSize(0, 0, 40, 11), "Warning!", blurb);
                dialog->SetUserId(menu->GetSelected());
                dialog->SetBackgroundPair(9);
                dialog->AppendButton(ccBUTTON_ID_YES, "Yes");
                dialog->AppendButton(ccBUTTON_ID_NO, "No", true);

                Draw();
                return true;
            }
            else if (menu->GetSelected() == ccMENU_ID_LOGOUT) {
                ccConsole::Instance()->ResetActivityTimer();
                return true;
            }
            
            Draw();
            LaunchProcess(menu->GetSelected());

            return true;
        }

        return false;

    case ccEVT_PROCESS:
        event_process = dynamic_cast<ccEventProcess *>(event);
        if (event_process->GetProcess() == proc_exec) {
            int status = proc_exec->GetExitStatus();
            if ((WIFEXITED(status) && WEXITSTATUS(status) != 0)) {
                cerr << "Process did not exit normally: " << WEXITSTATUS(status) << endl;
                for (int i = 0; i < 3; i++) { cerr << "."; sleep(1); }
            }
            else if (WIFSIGNALED(status) &&
                WTERMSIG(status) != SIGINT && WTERMSIG(status) != SIGTERM) {
                cerr << "Process did not exit normally: " << sys_siglist[WTERMSIG(status)] << endl;
                for (int i = 0; i < 3; i++) { cerr << "."; sleep(1); }
            }

            delete proc_exec;
            proc_exec = NULL;
            idle_pause = false;
            refresh();
            Resize();
        }
        else if (event_process->GetProcess() == proc_pipe) {
            FILE *ph = proc_pipe->GetId();
            int status = proc_pipe->GetExitStatus();

            string text = event_process->GetError();
            if (text.size())
                cerr << text.c_str();
            else {
                text = event_process->GetText();
                if (text.size()) {
                    if (progress) {
                        progress->Update(text);
                        Draw();
                    }
                    cerr << text.c_str();
                }
            }

            if (!ph) {
                delete proc_pipe;
                proc_pipe = NULL;
                if (progress) delete progress;
                progress = NULL;
                UpdateGraphicalConsoleItems();
                menu->SelectFirst();
                Resize();
                idle_pause = false;
            }

            timer_lock.Lock();
            activity = time(NULL);
            timer_lock.Unlock();
        }

        return true;

    case ccEVT_SIGNAL:
        event_signal = dynamic_cast<ccEventSignal *>(event);

        switch (event_signal->GetSignal()) {
        case SIGINT:
        case SIGTERM:
        case SIGHUP:
            if (proc_exec) {
                kill(proc_exec->GetId(), event_signal->GetSignal());
                return true;
            }
            run = false;
            return true;

        case SIGWINCH:
            Resize();
            return true;

        default:
            return false;
        }
        break;

    case ccEVT_SYSINFO:
        event_sysinfo = dynamic_cast<ccEventSysInfo *>(event);

        hostname = event_sysinfo->GetHostname();
        release = event_sysinfo->GetRelease();
        clock = event_sysinfo->GetTime();
        uptime = event_sysinfo->GetUptime();
        load_average = event_sysinfo->GetLoadAverage();
        load_average_color = event_sysinfo->GetLoadAverageColor();
        idle = event_sysinfo->GetIdle();

        Draw();
        return true;

    case ccEVT_OUTPUT:
        event_output = dynamic_cast<ccEventOutput *>(event);

        wmove(window, size.GetHeight() - 3, 1);
        wclrtoeol(window);
        waddch(window, ACS_RARROW);
        wprintw(window, " %s", event_output->GetText().c_str());

        Draw();

        return true;

    case ccEVT_FAULT:
        event_fault = dynamic_cast<ccEventFault *>(event);

        cerr << "Application fault: ";
        cerr << event_fault->GetReason() << endl;

        if (event_fault->GetReason() == string("Access denied")) {
            bool draw = false;

            idle_pause = true;

            if (menu->IsVisible()) {
                draw = true;
                menu->SetVisible(false);
            }

            if (!login) {
                draw = true;
                login = new ccDialogLogin(this);
            }

            Draw();
        }
        else if (event_fault->GetReason() == string("Authenticated")) {
            idle_pause = false;
            login_thread->Wait();
            delete login_thread;
            login_thread = NULL;

            menu->SetVisible();
            Draw();
        }

        return true;

    case ccEVT_DIALOG:
        event_dialog = dynamic_cast<ccEventDialog *>(event);

        if (event_dialog->GetSource() == dialog) {
            ccMenuId id = (ccMenuId)dialog->GetUserId();

            if (dialog->GetSelected() == ccBUTTON_ID_YES) {
                delete dialog; dialog = NULL;
                Draw();
                LaunchProcess(id);
            }
            else {
                delete dialog; dialog = NULL;
                Draw();
            }
        }
        else if (event_dialog->GetSource() == login) {
            login_thread = new ccThreadLogin("root", login->GetPassword());
            login_thread->Run();

            delete login; login = NULL;
            Draw();
        }

        return true;

    case ccEVT_PAINT:
        Refresh();
        return true;
    }

    return false;
}

void ccConsole::Draw(void)
{
    if (idle_pause || !IsVisible()) wclear(window);
    if (!IsVisible()) return;

    wcolor_set(window, 1, NULL);
    wmove(window, 0, 0);
    wclrtoeol(window);
    wmove(window, 1, 0);
    wclrtoeol(window);

    if (hostname.size()) {
        wmove(window, 0, 0);
        wcolor_set(window, 2, NULL);
        wprintw(window, hostname.c_str());
        wcolor_set(window, 1, NULL);
    }

    if (release.size()) {
        wmove(window, 1, 0);
        wprintw(window, release.c_str());
    }

    if (clock.size()) {
        wmove(window, 1, size.GetWidth() - clock.size());
        wprintw(window, clock.c_str());
    }

    if (uptime.size()) {
        wmove(window, size.GetHeight() - 1, 0);
        wclrtoeol(window);
        wprintw(window, "Uptime: %s", uptime.c_str());
    }

    if (load_average.size()) {
        wmove(window, size.GetHeight() - 1,
            size.GetWidth() - (string("Load Average: ").size() + load_average.size()));
        wprintw(window, "Load Average: ");
        wcolor_set(window, load_average_color, NULL);
        wprintw(window, load_average.c_str());
        wcolor_set(window, 1, NULL);
    }

    if (idle.size()) {
        wmove(window, size.GetHeight() - 1,
            (size.GetWidth() - (string("Idle: %").size() + idle.size())) / 2);
        wprintw(window, "Idle: %s%%", idle.c_str());
    }

    string keys("Press Alt-F2 to Alt-F6 for additional shell terminals.");
    wmove(window, size.GetHeight() - 4, (size.GetWidth() - keys.size()) / 2);
    wprintw(window, keys.c_str());
    
    ccWindow::Draw();
    Refresh();
}

void ccConsole::Refresh(void)
{
    ccWindow::Refresh();
    if (!isendwin()) doupdate();
}

void ccConsole::Resize(void)
{
    struct winsize ws;

    if (ioctl(fileno(stdin), TIOCGWINSZ, (char *)&ws) != -1) {
        if (is_term_resized(ws.ws_row, ws.ws_col)) {
            resize_term(ws.ws_row, ws.ws_col);

            size.SetWidth(ws.ws_col);
            size.SetHeight(ws.ws_row);
        }
    }

    wclear(window);
    curs_set(1); curs_set(0);

    for (int i = 0; i < child.size(); i++) child[i]->Resize();

    Draw();
}

bool ccConsole::UpdateGraphicalConsoleItems(void)
{
    menu->SetItemVisible(ccMENU_ID_CON_GUI, false);
    menu->SetItemVisible(ccMENU_ID_CON_GUI_INSTALL, true);
    menu->SetItemVisible(ccMENU_ID_CON_GUI_REMOVE, false);

    struct stat gcon_stat;
    if (stat(PATH_GCONSOLE, &gcon_stat) == 0) {
        menu->SetItemVisible(ccMENU_ID_CON_GUI, true);
        menu->SetItemVisible(ccMENU_ID_CON_GUI_INSTALL, false);
        //menu->SetItemVisible(ccMENU_ID_CON_GUI_REMOVE, true);
        return true;
    }

    return false;
}

void ccConsole::LaunchProcess(ccMenuId id)
{
    string path;
    vector<string> argv;
    bool signal_trap = true;

    switch (id) {
    case ccMENU_ID_CON_GUI:
        path = PATH_XCONSOLE;
        signal_trap = false;
        break;

    case ccMENU_ID_CON_GUI_INSTALL:
        progress = new ccDialogProgress(this, "Installing Packages");

        path = PATH_SUDO " " PATH_TCONSOLE_YUM " install";
        if (proc_pipe) delete proc_pipe;
        proc_pipe = new ccProcessPipe(path, argv);
        proc_pipe->Execute();
        break;

    case ccMENU_ID_CON_GUI_REMOVE:
        progress = new ccDialogProgress(this, "Removing Packages", false);

        path = PATH_SUDO " " PATH_TCONSOLE_YUM " remove";
        if (proc_pipe) delete proc_pipe;
        proc_pipe = new ccProcessPipe(path, argv);
        proc_pipe->Execute();
        break;

    case ccMENU_ID_UTIL_IPTRAF:
        path = PATH_SUDO;
        argv.push_back(PATH_IPTRAF);
        break;

    case ccMENU_ID_SYS_REBOOT:
        path = PATH_SUDO;
        argv.push_back(PATH_REBOOT);
        SetVisible(false);
        Draw();
        run = false;
        break;

    case ccMENU_ID_SYS_SHUTDOWN:
        path = PATH_SUDO;
        argv.push_back(PATH_HALT);
        SetVisible(false);
        Draw();
        run = false;
        break;

    default:
        return;
    }

    if (proc_exec) delete proc_exec;
    proc_exec = new ccProcessExec(path, argv, signal_trap);

    endwin();
    proc_exec->Execute();
}

ccThreadLogin::ccThreadLogin(const string &user, const string &passwd)
    : ccThread(ccThread::ccTHREAD_TYPE_JOINABLE), user(user), passwd(passwd) { }

void *ccThreadLogin::Entry(void)
{
    FILE *ph = popen(PATH_SUDO " " PATH_APP_PASSWD, "w");
    if (!ph) {
        ccEventFault *event = new ccEventFault("Access denied");
        ccEventServer::Instance()->PostEvent(event);
    }
    fprintf(ph, "%s %s", user.c_str(), passwd.c_str());
    fflush(ph);
    int rc = pclose(ph);
    if (rc == 0) {
        ccEventFault *event = new ccEventFault("Authenticated");
        ccEventServer::Instance()->PostEvent(event);
        return NULL;
    }

    ccEventFault *event = new ccEventFault("Access denied");
    ccEventServer::Instance()->PostEvent(event);
    return NULL;
}

ccThreadUpdate::ccThreadUpdate(void)
    : ccThread(ccThread::ccTHREAD_TYPE_JOINABLE) { }

ccThreadUpdate::~ccThreadUpdate() { }

void *ccThreadUpdate::Entry(void)
{
    int i = 0;
    ccFile file;
    ccRegEx rx_issue("^(.*) release (.*).Kernel", 3);
    ccRegEx rx_uptime("^([0-9]*)....([0-9]*)", 3);
    ccRegEx rx_loadavg("^([0-9]*\\.[0-9]*) ([0-9]*\\.[0-9]*) ([0-9]*\\.[0-9]*)", 4);

    sleep(update_interval);

    while (!TestDestroy()) {
        ostringstream os;

        try {
            FILE *ph;
            if(!i) {
                ph = popen(PATH_HOSTNAME, "r");
                if (ph) {
                    os.str("");
                    while (!feof(ph)) {
                        char c = (char)fgetc(ph);
                        if (c == EOF || c == '\0' || c == '\n') break;
                        os << c;
                    }
                    pclose(ph);
                    event_sysinfo.SetHostname(os.str());
                }

                if (rx_issue.Execute(file.Map(PATH_ISSUE)) == 0) {
                    os.str("");
                    os << rx_issue.GetMatch(1) << " " << rx_issue.GetMatch(2);
                    event_sysinfo.SetRelease(os.str());
                }
            }
            if (++i == 60) i = 0;
    
            time_t now = time(NULL);
            struct tm *tm_bits = localtime(&now);
            int hour = (tm_bits->tm_hour > 12) ? tm_bits->tm_hour - 12 : tm_bits->tm_hour;
    
            os.str("");
            os << ((hour) ? hour : 12) << ":"
                << setw(2) << setfill('0') << tm_bits->tm_min << " "
                << ((tm_bits->tm_hour >= 12) ? "PM" : "AM");

            event_sysinfo.SetTime(os);

            unsigned long uptime = 0, seconds = 0, idle = 0;
            if (rx_uptime.Execute(file.Read(PATH_UPTIME)) == 0) {
                uptime = seconds = strtol(rx_uptime.GetMatch(1), NULL, 0);
                idle = strtol(rx_uptime.GetMatch(2), NULL, 0);
            }
            unsigned long days = 0, hours = 0, minutes = 0;

            if (seconds >= 86400) {
                days = seconds / 86400;
                seconds -= days * 86400;
            }
            if (seconds >= 3600) {
                hours = seconds / 3600;
                seconds -= hours * 3600;
            }
            if (seconds >= 60) {
                minutes = seconds / 60;
                seconds -= minutes * 60;
            }

            os.str("");
            os << days << "d "
                << hours << ":" << setw(2) << setfill('0')
                << minutes;

            event_sysinfo.SetUptime(os);

            static float last_load_avg = 0.0f;
            float load_avg = 0.0f;
            if (rx_loadavg.Execute(file.Read(PATH_LOADAVG)) == 0) {
                float one = strtof(rx_loadavg.GetMatch(1), NULL);
                float five = strtof(rx_loadavg.GetMatch(2), NULL);
                float fifteen = strtof(rx_loadavg.GetMatch(3), NULL);
                load_avg = (one + five + fifteen) / 3.0f;
            }
            if (load_avg > last_load_avg) event_sysinfo.SetLoadAverageColor(4);
            else if (load_avg < last_load_avg) event_sysinfo.SetLoadAverageColor(5);
            last_load_avg = load_avg;

            os.str("");
            os << fixed << setprecision(2) << load_avg;

            event_sysinfo.SetLoadAverage(os);

            float idle_percent = (float)idle * 100.0f / (float)uptime;
            if (idle_percent > 100.0f) idle_percent = 99.99f;

            os.str("");
            os << fixed << setprecision(2) << idle_percent;

            event_sysinfo.SetIdle(os);

            ccEventServer::Instance()->PostEvent(new ccEventSysInfo(event_sysinfo));
        }
        catch (ccException &e)
        {
            ccEventServer::Instance()->PostEvent(new ccEventFault(e.what()));
        }
        sleep(update_interval);
    }

    return NULL;
}

ccThreadEvent::ccThreadEvent(void)
    : ccThread(ccThread::ccTHREAD_TYPE_JOINABLE)
{
    server = new ccEventServer();
}

ccThreadEvent::~ccThreadEvent()
{
    if(server) delete server;
}

void *ccThreadEvent::Entry(void)
{
    while (!TestDestroy())
    {
        server->DispatchEvents();
        usleep(SLEEP_DELAY);
    }

    return NULL;
}

ccProcessBase::ccProcessBase(const string &path, const vector<string> &arg)
    : path(path), argv(NULL), thread(NULL)
{
    if (arg.size())
        argv = new char *[arg.size() + 2];
    else
        argv = new char *[2];

    argv[0] = new char[path.size() + 1];
    memset(argv[0], 0, path.size() + 1);
    memcpy(argv[0], path.c_str(), path.size());

    int i = 0;
    for (; i < arg.size(); i++) {
        if (!arg[i].size()) continue;

        argv[i + 1] = new char[arg[i].size() + 1];
        memset(argv[i + 1], 0, arg[i].size() + 1);
        memcpy(argv[i + 1], arg[i].c_str(), arg[i].size());
    }

    argv[i + 1] = NULL;
}

ccProcessBase::~ccProcessBase()
{
    if (argv) {
        for (int i = 0; argv[i]; i++) delete [] argv[i];
        delete [] argv;
    }

    if (thread) {
        thread->Destroy();
        thread->Wait();
        delete thread;
    }
}

ccProcessExec::ccProcessExec(const string &path, const vector<string> &arg, bool signal_trap)
    : ccProcessBase(path, arg), pid(0), signal_trap(signal_trap) { }

void ccProcessExec::Execute(void)
{
    if (!path.size()) throw ccException("Invalid process path");

    thread = new ccThreadProcessExec(NULL, this);
    thread->Run();
}

ccProcessPipe::ccProcessPipe(const string &path, const vector<string> &arg)
    : ccProcessBase(path, arg), ph(NULL) { }

void ccProcessPipe::Execute(void)
{
    if (!path.size()) throw ccException("Invalid process path");

    thread = new ccThreadProcessPipe(NULL, this);
    thread->Run();
}

int ccProcessPipe::Execute(const string &path, vector<string> &output)
{
    FILE *ph;
    if ((ph = popen(path.c_str(), "r")) == NULL) return -1;
    output.clear();
    int text_size = getpagesize();
    char *text = new char[text_size];
    while (!feof(ph)) {
        if (fgets(text, text_size, ph) == NULL) break;
        output.push_back(text);
    }
    
    return pclose(ph);
}

ccThreadProcessBase::ccThreadProcessBase(ccEventClient *parent)
    : ccThread(ccTHREAD_TYPE_JOINABLE), parent(parent) { }

ccThreadProcessExec::ccThreadProcessExec(ccEventClient *parent, ccProcessExec *process)
    : ccThreadProcessBase(parent), process(process) { }

void *ccThreadProcessExec::Entry(void)
{
    pid_t pid;
    ccEventProcess *event = new ccEventProcess(parent, process);

    switch ((pid = fork())) {
    case 0:
        setsid();
        for (int i = 3; i < FD_SETSIZE; i++) close(i);
        if (!process->signal_trap) {
            sigset_t sigset;
            sigemptyset(&sigset);

            sigaddset(&sigset, SIGINT);
            sigaddset(&sigset, SIGTERM);
            sigaddset(&sigset, SIGHUP);
            sigaddset(&sigset, SIGCHLD);
            sigaddset(&sigset, SIGWINCH);

            pthread_sigmask(SIG_UNBLOCK, &sigset, NULL);
        }

        if (execv(process->path.c_str(), process->argv) == -1) {
            event->SetError(strerror(errno));
            ccEventServer::Instance()->PostEvent(event);
            return NULL;
        }

        // Impossible to reach...

    case -1:
        event->SetError(strerror(errno));
        ccEventServer::Instance()->PostEvent(event);
        return NULL;
    }
    
    process->lock.Lock();

    process->pid = pid;
    process->status = 0;

    process->lock.Unlock();

    idle_pause = true;

    while (!TestDestroy()) {
        process->lock.Lock();

        switch (waitpid(process->pid, &process->status, WNOHANG)) {
        case 0:
            break;

        case -1:
            event->SetError(strerror(errno));
        default:
            process->lock.Unlock();

            ccEventServer::Instance()->PostEvent(event);
            return NULL;
        }
    
        process->lock.Unlock();

        usleep(SLEEP_DELAY);
    }

    return NULL;
}

ccThreadProcessPipe::ccThreadProcessPipe(ccEventClient *parent, ccProcessPipe *process)
    : ccThreadProcessBase(parent), process(process) { }

void *ccThreadProcessPipe::Entry(void)
{
    FILE *ph;
    ccEventProcess *event = new ccEventProcess(parent, process);

    if ((ph = popen(process->path.c_str(), "r")) == NULL) {
        event = new ccEventProcess(parent, process);
        event->SetError(strerror(errno));
        ccEventServer::Instance()->PostEvent(event);
        return NULL;
    }

    process->lock.Lock();

    process->ph = ph;
    process->status = 0;

    process->lock.Unlock();

    idle_pause = true;

    int text_size = getpagesize();
    char *text = new char[text_size];

    while (!TestDestroy()) {
        if (fgets(text, text_size, ph) == NULL) break;

        event = new ccEventProcess(parent, process);
        event->SetText(text);
        ccEventServer::Instance()->PostEvent(event);
    }

    process->lock.Lock();

    process->status = pclose(ph);
    process->ph = NULL;

    process->lock.Unlock();

    event = new ccEventProcess(parent, process);
    ccEventServer::Instance()->PostEvent(event);
    return NULL;
}

// vi: ts=4
