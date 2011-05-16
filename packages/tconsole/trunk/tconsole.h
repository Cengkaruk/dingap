#ifndef _LAUNCHER_H
#define _LAUNCHER_H

#define VER_MAJOR				3
#define VER_MINOR				0

#ifndef PATH_XCONSOLE
#define PATH_XCONSOLE			"/usr/bin/startx"
#endif

#ifndef PATH_TCONSOLE
#define PATH_TCONSOLE			"/usr/sbin/console_start"
#endif

#ifndef PATH_TCONSOLE_YUM
#define PATH_TCONSOLE_YUM		"/usr/sbin/tc-yum"
#endif

#ifndef PATH_RPM
#define PATH_RPM				"/bin/rpm"
#endif

#ifndef PATH_IPTRAF
#define PATH_IPTRAF				"/usr/bin/iptraf"
#endif

#ifndef PATH_REBOOT
#define PATH_REBOOT				"/sbin/reboot"
#endif

#ifndef PATH_HALT
#define PATH_HALT				"/sbin/halt"
#endif

#ifndef PATH_SUDO
#define PATH_SUDO				"/usr/bin/sudo"
#endif

#ifndef PATH_APP_PASSWD
#define PATH_APP_PASSWD         "/usr/sbin/app-passwd"
#endif

#ifndef PATH_GCONSOLE
#define PATH_GCONSOLE           "/usr/bin/gconsole"
#endif

#ifndef PATH_HOSTNAME
#define PATH_HOSTNAME           "/bin/hostname"
#endif

#ifndef PATH_ISSUE
#define PATH_ISSUE              "/etc/issue"
#endif

#ifndef PATH_UPTIME
#define PATH_UPTIME             "/proc/uptime"
#endif

#include <exception>
#include <vector>
#include <sstream>

#include "thread.h"

using namespace std;

enum ccEventType
{
	ccEVT_KEY_PRESS,
	ccEVT_SIGNAL,
	ccEVT_SYSINFO,
	ccEVT_OUTPUT,
	ccEVT_FAULT,
	ccEVT_PROCESS,
	ccEVT_DIALOG,
	ccEVT_PAINT,
	ccEVT_TIMER
};

class ccEventClient;

class ccEvent
{
public:
	ccEvent(ccEventType type, ccEventClient *src, ccEventClient *dst);
	virtual ~ccEvent() { };

	ccEventType GetType(void) { return type; };
	ccEventClient *GetSource(void) { return src; };
	ccEventClient *GetDestination(void) { return dst; };

	virtual const string ToString(void)
	{
		ostringstream os;
		os << "ccEvent 0x" << hex << type << ", src: " << src << ", dst: " << dst;
		return os.str();
	};

	ccEvent& operator=(const ccEvent &e);

protected:
	ccEventType type;
	ccEventClient *src;
	ccEventClient *dst;
};

class ccEventKeyPress : public ccEvent
{
public:
	ccEventKeyPress(int key, ccEventClient *src = NULL);

	int GetKey(void) { return key; };

protected:
	int key;
};

class ccEventSignal : public ccEvent
{
public:
	ccEventSignal(int sig);

	int GetSignal(void) { return sig; };

	virtual const string ToString(void)
	{
		ostringstream os;
		os << ccEvent::ToString() << ", sig: " << sig;
		return os.str();
	};

protected:
	int sig;
};

class ccEventOutput : public ccEvent
{
public:
	ccEventOutput(const string &text, ccEventClient *src = NULL);
	ccEventOutput(ostringstream &stream, ccEventClient *src = NULL);

	string GetText(void) { return text; };

protected:
	string text;
};

class ccEventFault : public ccEvent
{
public:
	ccEventFault(const string &reason);

	string GetReason(void) { return reason; };

protected:
	string reason;
};

class ccEventSysInfo : public ccEvent
{
public:
	ccEventSysInfo(ccEventClient *src = NULL)
		: ccEvent(ccEVT_SYSINFO, src, NULL), sys_loadavg_color(1) { };

	void SetHostname(const string &hostname) { this->hostname = hostname; };
	void SetRelease(const string &release) { this->release = release; };
	void SetTime(ostringstream &stream) { sys_time = stream.str(); };
	void SetUptime(ostringstream &stream) { sys_uptime = stream.str(); };
	void SetLoadAverage(ostringstream &stream) { sys_loadavg = stream.str(); };
	void SetLoadAverageColor(int color) { sys_loadavg_color = color; };
	void SetIdle(ostringstream &stream) { sys_idle = stream.str(); };

	const string GetHostname(void) { return hostname; };
	const string GetRelease(void) { return release; };
	const string GetTime(void) { return sys_time; };
	const string GetUptime(void) { return sys_uptime; };
	const string GetLoadAverage(void) { return sys_loadavg; };
	int GetLoadAverageColor(void) { return sys_loadavg_color; };
	const string GetIdle(void) { return sys_idle; };

protected:
	string hostname;
	string release;
	string sys_time;
	string sys_uptime;
	string sys_loadavg;
	int sys_loadavg_color;
	string sys_idle;
};

class ccProcessBase;

class ccEventProcess : public ccEvent
{
public:
	ccEventProcess(ccEventClient *dst, ccProcessBase *process)
		: ccEvent(ccEVT_PROCESS, NULL, dst), process(process) { };

	const string GetText(void) { return text; };
	const string GetError(void) { return error; };
	ccProcessBase *GetProcess(void) { return process; };

	void SetText(const string &text) { this->text = text; };
	void SetError(const string &error) { this->error = error; };

protected:
	string text;
	string error;
	ccProcessBase *process;
};

enum ccButtonId
{
	ccBUTTON_ID_NONE = 0,
	ccBUTTON_ID_OK,
	ccBUTTON_ID_CANCEL,
	ccBUTTON_ID_YES,
	ccBUTTON_ID_NO,
	ccBUTTON_ID_LOGIN
};

class ccEventDialog : public ccEvent
{
public:
	ccEventDialog(ccEventClient *src, ccEventClient *dst, ccButtonId id)
		: ccEvent(ccEVT_DIALOG, src, dst), id(id) { };

	ccButtonId GetId(void) { return id; };

protected:
	ccButtonId id;
};

class ccEventPaint : public ccEvent
{
public:
	ccEventPaint(ccEventClient *src = NULL)
		: ccEvent(ccEVT_PAINT, src, NULL) { };
};

class ccTimer;
class ccEventTimer : public ccEvent
{
public:
	ccEventTimer(ccEventClient *dst)
		: ccEvent(ccEVT_TIMER, NULL, dst) { };
	ccTimer *GetTimer(void) { return timer; };

protected:
	ccTimer *timer;
};

class ccEventClient
{
public:
	ccEventClient(void);
	virtual ~ccEventClient();

	virtual bool HandleEvent(ccEvent *event);
};

class ccEventServer
{
public:
	ccEventServer(void);
	~ccEventServer();

	void PostEvent(ccEvent *event);
	static ccEventServer *Instance(void);
	void RegisterEvent(ccEventClient *client, ccEventType type);
	void UnregisterClient(ccEventClient *client);

protected:
	friend class ccThreadEvent;

	void DispatchEvents(void);

	static ccEventServer *instance;

	ccMutex queue_lock;
	vector<ccEvent *> queue;
	typedef pair<ccEventClient *, ccEventType> client_pair;
	vector<client_pair> client;
};

class ccSize;
class ccPoint
{
public:
	ccPoint(void) : x(0), y(0) { };
	ccPoint(int x, int y) : x(x), y(y) { };

	int GetX(void) { return x; };
	int GetY(void) { return y; };

	int SetX(int x) { this->x = x; };
	int SetY(int y) { this->y = y; };

	ccPoint& operator=(const ccPoint &p)
	{
		this->x = p.x;
		this->y = p.y;
		return *this;
	};

	virtual const string ToString(void)
	{
		ostringstream os;
		os << "ccPoint x: " << setw(3) << x << ", y: " << setw(3) << y;
		return os.str();
	};

protected:
	friend class ccSize;

	int x;
	int y;
};

class ccSize : public ccPoint
{
public:
	ccSize(void) : ccPoint(x, y), w(0), h(0) { };
	ccSize(int x, int y, int w, int h) : ccPoint(x, y), w(w), h(h) { };

	int GetWidth(void) { return w; };
	int GetHeight(void) { return h; };

	int SetWidth(int w) { this->w = w; };
	int SetHeight(int h) { this->h = h; };

	ccSize& operator=(const ccPoint &p)
	{
		this->x = p.x;
		this->y = p.y;
		return *this;
	};

	ccSize& operator=(const ccSize &s)
	{
		this->x = s.x;
		this->y = s.y;
		this->w = s.w;
		this->h = s.h;
		return *this;
	};

	virtual const string ToString(void)
	{
		ostringstream os;
		os << "ccSize x: " << setw(3) << x << ", y: " << setw(3) << y;
		os << ", w: " << setw(3) << w << ", h: " << setw(3) << h;
		return os.str();
	};

protected:
	int w;
	int h;
};

class ccText
{
public:
	ccText(const string &text, int width);

	void SetText(const string &text) { this->text = text; Resize(); };
	void SetWidth(int width) { this->width = width; Resize(); };

	int GetWidth(void) { return width; };

	void Resize(void);

	int GetLineCount(void) { return _lines.size(); };
	string GetLine(int line) { _lines.at(line); return _lines[line]; };

protected:
	int width;
	string text;
	vector<string> _lines;
};

class ccTimer : public ccThread
{
public:
	ccTimer(ccEventClient *parent, bool one_shot = false);
	~ccTimer() { Stop(); };

	bool Start(uint32_t usec);
	bool Stop(void);
	bool IsRunning(void) { return running; };

	void *Entry(void);

protected:
	ccEventClient *parent;
	uint32_t usec;
	bool running;
	bool one_shot;
};

class ccThreadLogin : public ccThread
{
public:
	ccThreadLogin(const string &user, const string &passwd);
	void *Entry(void);

protected:
    string user;
    string passwd;
};

class ccWindow : public ccEventClient
{
public:
	ccWindow(ccWindow *parent, const ccSize &size, int bg_cp = -1);
	virtual ~ccWindow();

	ccSize& GetSize(void) { return size; };
	void SetSize(const ccSize &size)
	{
		this->size = size;
		mvwin(window, this->size.GetY(), this->size.GetX());
	};

	virtual void Draw(void);
	virtual void Refresh(void);
	virtual void Resize(void) { };

	bool IsVisible(void) { return visible; };
	virtual void SetVisible(bool visible = true)
	{
		this->visible = visible;
		if(parent) parent->Clear();
	};

	void Clear(void) { if(window) wclear(window); };
	void CenterOnParent(void);
	void SetBackgroundPair(int bg_cp)
	{
		this->bg_cp = bg_cp; wbkgd(window, COLOR_PAIR(bg_cp));
	};

protected:
	bool visible;
	ccSize size;
	vector<ccWindow *> child;
	ccWindow *parent;
	WINDOW *window;
	int bg_cp;

	void AddChild(ccWindow *w) { child.push_back(w); };
	void RemoveChild(ccWindow *w);
};

class ccButton
{
public:
	ccButton(ccButtonId id, const string &label)
		: id(id), label(label), focus(false) { };

	ccButtonId GetId(void) { return id; };
	string GetLabel(void) { return label; };

	void SetFocus(bool focus = true) { this->focus = focus; };
	bool HasFocus(void) { return focus; };

protected:
	ccButtonId id;
	string label;
	bool focus;
};

#define ccINPUT_READONLY		0x0001
#define ccINPUT_PASSWORD		0x0002

class ccInputBox : public ccWindow
{
public:
	ccInputBox(ccWindow *parent, const ccSize &size, const string &value = "");
	~ccInputBox();

	void SetReadOnly(bool readonly = true)
	{
		if(readonly) style |= ccINPUT_READONLY;
		else style &= ~ccINPUT_READONLY;
	};
	bool IsReadOnly(void) { return (bool)(style & ccINPUT_READONLY); };

	void SetPassword(bool password = true)
	{
		if(password) style |= ccINPUT_PASSWORD;
		else style &= ~ccINPUT_PASSWORD;
	};
	bool IsPassword(void) { return (bool)(style & ccINPUT_PASSWORD); };

	void SetValue(const string &value) { this->value = value; };
	string GetValue(void) { return value; };

	void Draw(void);
	bool HandleEvent(ccEvent *event);

protected:
	string value;
	uint32_t style;
	bool focus;
	size_t cpos;
};

class ccProgressBar : public ccWindow
{
public:
	ccProgressBar(ccWindow *parent, const ccSize &size);
	~ccProgressBar();

	void SetRange(uint32_t value) { this->mvalue = value; };
	void Update(uint32_t value);

	void Draw(void);

protected:
	uint32_t cvalue;
	uint32_t mvalue;
};

class ccDialog : public ccWindow
{
public:
	ccDialog(ccWindow *parent, const ccSize &size, const string &title, const string &blurb);
	~ccDialog();

	void SetBlurb(const string &blurb) { this->blurb->SetText(blurb); Draw(); };

	void SetUserId(int id) { user_id = id; };
	int GetUserId(void) { return user_id; };

	virtual void Draw(void);
	bool HandleEvent(ccEvent *event);
	virtual void Resize(void) { CenterOnParent(); };

	ccButtonId GetFocus(void);

	void SetFocus(int index);
	void SetFocus(ccButtonId id);

	ccButtonId FocusNext(void);
	ccButtonId FocusPrevious(void);

	void SetSelected(void);
	ccButtonId GetSelected(void) { return selected; };

	void AppendButton(ccButtonId id, const string &label, bool focus = false);

protected:
	int user_id;
	string title;
	ccText *blurb;
	ccButtonId selected;
	vector<ccButton *> button;
	int button_width;
};

class ccDialogLogin : public ccDialog
{
public:
	ccDialogLogin(ccWindow *parent);

	string GetPassword(void) { return passwd->GetValue(); };
	virtual void Resize(void)
	{
		CenterOnParent();

		passwd->CenterOnParent();
		ccSize _size = passwd->GetSize();
		_size.SetY(_size.GetY() + 1);
		passwd->SetSize(_size);
	}

protected:
	ccInputBox *passwd;
};

class ccDialogProgress : public ccDialog
{
public:
	ccDialogProgress(ccWindow *parent, const string &title, bool install = true);

	virtual void Resize(void)
	{
		CenterOnParent();
		if (install) {
			progress1->CenterOnParent();
			ccSize _size = progress1->GetSize();
			_size.SetY(_size.GetY() + 1);
			progress1->SetSize(_size);

			progress2->CenterOnParent();
			_size = progress2->GetSize();
			_size.SetY(_size.GetY() + 3);
			progress2->SetSize(_size);
		} else {
			progress1->CenterOnParent();
			ccSize _size = progress1->GetSize();
			_size.SetY(_size.GetY() + 2);
			progress1->SetSize(_size);
		}
	};

	void Update(const string &update);

protected:
	bool install;
	ccProgressBar *progress1;
	ccProgressBar *progress2;
};

class ccMenuItem;

enum ccMenuId
{
	ccMENU_ID_INVALID = -1,
	ccMENU_ID_CON_GUI,
	ccMENU_ID_CON_GUI_INSTALL,
	ccMENU_ID_CON_GUI_REMOVE,
	ccMENU_ID_UTIL_IPTRAF,
	ccMENU_ID_SYS_REBOOT,
	ccMENU_ID_SYS_SHUTDOWN,
	ccMENU_ID_LOGOUT,
	ccMENU_ID_SEPERATOR = 0xff
};

class ccMenu : public ccWindow
{
public:
	ccMenu(ccWindow *parent, const ccSize &size, const string &title);
	~ccMenu();

	void Draw(void);
	void Resize(void);
	bool HandleEvent(ccEvent *event);

	void InsertItem(ccMenuItem *item);
	void RemoveItem(ccMenuId id);
	void SetItemVisible(ccMenuId id, bool visible = true);
	void SelectItem(ccMenuId id);
	bool SelectItem(int hotkey);
	void SelectFirst(void);
	void SelectNext(void);
	void SelectPrevious(void);
	ccMenuId GetSelected(void);

protected:
	string title;
	string blurb;
	int menu_width;
	vector<ccMenuItem *> item;

	void CalcMenuWidth(void);
};

class ccMenuItem
{
public:
	ccMenuItem(ccMenuId id, const string &title, int hotkey = 0, const string &hotkey_title = "");

	ccMenuId GetId(void) { return id; };
	string GetTitle(void) { return title; };
	int GetHotkey(void) { return hotkey; };
	string GetHotkeyTitle(void) { return hotkey_title; };
	void SetSelected(bool selected = true) { this->selected = selected; };
	bool IsSelected(void) { return selected; };
	virtual bool IsSeperator(void) { return false; };

	void SetVisible(bool visible = true) { this->visible = visible; };
	bool IsVisible(void) { return visible; };

protected:
	ccMenuId id;
	bool selected;
	bool visible;
	string title;
	string hotkey_title;
	int hotkey;
};

class ccMenuSpacer : public ccMenuItem
{
public:
	ccMenuSpacer(void) : ccMenuItem(ccMENU_ID_SEPERATOR, "") { };

	bool IsSeperator(void) { return true; };
};

class ccThreadEvent;
class ccThreadUpdate;
class ccProcessExec;
class ccProcessPipe;

class ccConsole : public ccWindow
{
public:
	ccConsole();
	~ccConsole();

	static ccConsole *Instance(void);

	int EventLoop(void);
	bool HandleEvent(ccEvent *event);

	void Draw(void);
	void Refresh(void);
	void Resize(void);

	void ResetActivityTimer(void)
	{
		timer_lock.Lock();
		activity = 0;
		timer_lock.Unlock();
	};

protected:
	bool run;
	bool sleep_mode;
	ccMutex ncurses_lock;
	ccMutex timer_lock;
	static ccConsole *instance;
	ccThreadUpdate *update_thread;
	ccThreadLogin *login_thread;
	string hostname;
	string release;
	string clock;
	string uptime;
	string load_average;
	int load_average_color;
	string idle;
	ccProcessExec *proc_exec;
	ccProcessPipe *proc_pipe;
	ccMenu *menu;
	ccDialog *dialog;
	ccDialogLogin *login;
	ccDialogProgress *progress;
	time_t activity;

	bool UpdateGraphicalConsoleItems(void);
	void LaunchProcess(ccMenuId id);
};

class ccThreadEvent : public ccThread
{
public:
	ccThreadEvent(void);
	~ccThreadEvent();

	void *Entry(void);

protected:
	ccEventServer *server;
};

class ccThreadUpdate : public ccThread
{
public:
	ccThreadUpdate(void);
	~ccThreadUpdate();

	void *Entry(void);

protected:
	ccEventSysInfo event_sysinfo;
};

class ccThreadProcessBase;
class ccThreadProcessExec;
class ccThreadProcessPipe;

class ccProcessBase
{
public:
	ccProcessBase(const string &path, const vector<string> &arg);
	~ccProcessBase();

	virtual void Execute(void) { thread = NULL; };

	string GetPath(void) { return path; };
	int GetExitStatus(void)
	{
		ccMutexLocker locker(lock);
		return status;
	};

protected:
	friend class ccThreadProcessBase;
	friend class ccThreadProcessExec;
	friend class ccThreadProcessPipe;

	char **argv;
	string path;
	int status;
	ccThreadProcessBase *thread;
	ccMutex lock;
};

class ccProcessExec : public ccProcessBase
{
public:
	ccProcessExec(const string &path, const vector<string> &arg, bool signal_trap = true);

	void Execute(void);

	pid_t GetId(void) { return pid; };

protected:
	friend class ccThreadProcessExec;

	pid_t pid;
	bool signal_trap;
};

class ccProcessPipe : public ccProcessBase
{
public:
	ccProcessPipe(const string &path, const vector<string> &arg);

	void Execute(void);
	static int Execute(const string &path, vector<string> &output);

	FILE *GetId(void) { return ph; };

protected:
	friend class ccThreadProcessPipe;

	FILE *ph;
};

class ccThreadProcessBase : public ccThread
{
public:
	ccThreadProcessBase(ccEventClient *parent);

	virtual void *Entry(void) { return NULL; };

protected:
	ccEventClient *parent;
};

class ccThreadProcessExec : public ccThreadProcessBase
{
public:
	ccThreadProcessExec(ccEventClient *parent, ccProcessExec *process);

	void *Entry(void);

protected:
	ccProcessExec *process;
};

class ccThreadProcessPipe : public ccThreadProcessBase
{
public:
	ccThreadProcessPipe(ccEventClient *parent, ccProcessPipe *process);

	void *Entry(void);

protected:
	ccProcessPipe *process;
};

#endif

// vi: ts=4

