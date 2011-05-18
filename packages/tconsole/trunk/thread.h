#ifndef _THREAD_H
#define _THREAD_H

#include <iostream>
#include <string>
#include <errno.h>
#include <pthread.h>

#include "exceptions.h"

using namespace std;

#define ccMUTEX_STATE_NONE				0x00000000
#define ccMUTEX_STATE_CREATED			0x00000001
#define ccMUTEX_STATE_DEBUG				0x00000002

class ccMutex
{
public:
	ccMutex(void);
	~ccMutex();

	enum ccMutexResult {
		ccMUTEX_RESULT_SUCCESS,
		ccMUTEX_RESULT_FAILURE,
		ccMUTEX_RESULT_LOCKED,
		ccMUTEX_RESULT_INVALID
	};

	ccMutexResult Lock(void);
	ccMutexResult Unlock(void);
	ccMutexResult TryLock(void);

	bool IsValid(void) { return (state & ccMUTEX_STATE_CREATED); };
	void SetDebug(bool enable = true);
	unsigned long GetId(void) { return id; };

protected:
	unsigned long state;
	unsigned long id;
	pthread_mutex_t mutex;
	pthread_mutexattr_t attr;

	friend class ccCondition;
};

class ccMutexLocker
{
public:
	ccMutexLocker(ccMutex &mutex) : mutex(mutex) { if (mutex.IsValid()) mutex.Lock(); };
	~ccMutexLocker() { if (mutex.IsValid()) mutex.Unlock(); };

protected:
	ccMutex &mutex;
};

#define ccCOND_STATE_NONE				0x00000000
#define ccCOND_STATE_CREATED			0x00000001

class ccCondition
{
public:
	ccCondition(ccMutex &mutex);
	~ccCondition();

	enum ccConditionResult {
		ccCOND_RESULT_SUCCESS,
		ccCOND_RESULT_FAILURE,
		ccCOND_RESULT_INVALID
	};

	ccConditionResult Wait(void);

	ccConditionResult Signal(void);
	ccConditionResult Broadcast(void);

	bool IsValid(void) { return (state & ccCOND_STATE_CREATED); };

private:
	ccMutex &mutex;
	pthread_cond_t cond;
	unsigned long state;

	pthread_mutex_t *GetMutex(void) const { return &mutex.mutex; };
};

#define ccSEMA_STATE_NONE		0x00000000
#define ccSEMA_STATE_CREATED	0x00000001

class ccSemaphore
{
public:
	ccSemaphore(int count_init = 0, int count_max = 0);

	enum ccSemaphoreResult {
		ccSEMA_RESULT_SUCCESS,
		ccSEMA_RESULT_FAILURE,
		ccSEMA_RESULT_BUSY,
		ccSEMA_RESULT_OVERFLOW,
		ccSEMA_RESULT_INVALID
	};

	ccSemaphoreResult Wait(void);
	ccSemaphoreResult TryWait(void);

	ccSemaphoreResult Post(void);

	bool IsValid(void) { return (state & ccSEMA_STATE_CREATED); };

private:
	ccMutex mutex;
	ccCondition cond;
	int count_init;
	int count_max;
	unsigned long state;
};

#define ccTHREAD_STATE_NONE		0x00000000
#define ccTHREAD_STATE_ATTR		0x00000001
#define ccTHREAD_STATE_CREATED	0x00000002
#define ccTHREAD_STATE_DESTROY	0x00000004

extern "C"
{
	void *ccThreadEntry(void *thread);
}

class ccThread
{
public:
	enum ccThreadType {
		ccTHREAD_TYPE_JOINABLE,
		ccTHREAD_TYPE_DETACHED
	};

	enum ccThreadResult {
		ccTHREAD_RESULT_SUCCESS,
		ccTHREAD_RESULT_FAILURE,
		ccTHREAD_RESULT_INVALID_TYPE
	};

	ccThread(ccThreadType type);
	virtual ~ccThread();

	ccThreadResult Run(void);

	static void *ThreadEntry(ccThread *thread);

	void Destroy(void);
	void *Wait(void);

	pthread_t GetId(void) const { return thread; };
	bool IsValid(void) { return (state & ccSEMA_STATE_CREATED); };

protected:
	ccThreadResult Create(void);
	virtual void *Entry(void) { return NULL; }
	bool TestDestroy(void);
	void Exit(void *exit_code) { pthread_exit(exit_code); };

private:
	void *exit_code;
	ccThreadType type;
	pthread_t thread;
	pthread_attr_t attr;
	unsigned int state;
	ccMutex state_mutex;
	ccSemaphore run_semaphore;
};

// vi: ts=4

#endif
