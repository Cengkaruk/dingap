#ifndef _EXCEPTIONS_H
#define _EXCEPTIONS_H

#include <exception> 
#include <string>
#include <iostream> 
#include <sstream>

#include <errno.h>

using namespace std;

class ccException : public exception
{
public:
	ccException(const string &reason) : reason(reason) { }
	ccException(const ostringstream &reason) : reason(reason.str()) { }
	virtual ~ccException(void) throw() { }

	virtual const char *what() const throw() { return reason.c_str(); }
	virtual const string& std_what() const throw() { return reason; }

private:
	string reason;
};
#if 0
class ccServiceFaultException : public ccException
{
public:
	ccServiceFaultException()
		: ccException("Application Fault") { }
	~ccServiceFaultException() throw() { }

	void SetFault(const string &code, const string &reason)
	{ this->code = code, this->reason = reason; }

	string GetCode(void) { return code; }
	string GetReason(void) { return reason; }

private:
	string code;
	string reason;
};
#endif
class ccLocaleException : public ccException
{
public:
	ccLocaleException(const string &tag)
		: ccException("Locale tag not found"), tag(tag) { }
	~ccLocaleException() throw() { }

	string GetTag(void) { return tag; }

private:
	string tag;
};

class ccThreadException : public ccException
{
public:
	ccThreadException(long thread_id, const string &reason, int error)
		: ccException(reason), id(id), reason(reason), error(error) { }
	~ccThreadException() throw() { }

	long GetId(void) { return id; }
	int GetError(void) { return error; }
	string GetReason(void) { return reason; }

private:
	long id;
	int error;
	string reason;
};

class ccMutexException : public ccException
{
public:
	ccMutexException(const string &reason, int error)
		: ccException(reason), reason(reason), error(error) { }
	~ccMutexException() throw() { }

	int GetError(void) { return error; }
	string GetReason(void) { return reason; }

private:
	int error;
	string reason;
};

class ccConditionException : public ccException
{
public:
	ccConditionException(const string &reason, int error)
		: ccException(reason), reason(reason), error(error) { }
	~ccConditionException() throw() { }

	int GetError(void) { return error; }
	string GetReason(void) { return reason; }

private:
	int error;
	string reason;
};

class ccSemaphoreException : public ccException
{
public:
	ccSemaphoreException(const string &reason)
		: ccException(reason), reason(reason) { }
	~ccSemaphoreException() throw() { }

	string GetReason(void) { return reason; }

private:
	string reason;
};

class ccSingleInstanceException : public ccException
{
public:
	ccSingleInstanceException(const string &classname)
		: ccException(classname), classname(classname) { }
	~ccSingleInstanceException() throw() { }

	string GetClassName(void) { return classname; }

private:
	string classname;
};

#endif

// vi: ts=4

