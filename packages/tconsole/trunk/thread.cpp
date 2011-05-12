#include <string>
#include <errno.h>
#include <pthread.h>
#include <signal.h>

#include "thread.h"

using namespace std;

static unsigned long mid = 0;

ccMutex::ccMutex(void) : state(ccMUTEX_STATE_NONE)
{
	int result;
	if((result = pthread_mutex_init(&mutex, NULL)) != 0)
	{
		throw(ccMutexException("Error creating mutex", result));
	}

	state |= ccMUTEX_STATE_CREATED;
	id = mid++;
}

ccMutex::~ccMutex()
{
	int result;
	if(!(state & ccMUTEX_STATE_CREATED)) return;
	if((result = pthread_mutex_destroy(&mutex)) != 0)
	{
		throw(ccMutexException("Error destroying mutex", result));
	}
}

ccMutex::ccMutexResult ccMutex::Lock(void)
{
	int result = -1;

	if(!(state & ccMUTEX_STATE_CREATED))
	{
		throw(ccMutexException("Invalid mutex", result));
		return ccMUTEX_RESULT_INVALID;
	}

	if((result = pthread_mutex_lock(&mutex)) == 0)
	{
		if(state & ccMUTEX_STATE_DEBUG)
			cerr << "Mutex " << id << ": locked" << endl;
		return ccMUTEX_RESULT_SUCCESS;
	}

	throw(ccMutexException("Error locking mutex", result));
	return ccMUTEX_RESULT_FAILURE;
}

ccMutex::ccMutexResult ccMutex::Unlock(void)
{
	int result = -1;

	if(!(state & ccMUTEX_STATE_CREATED))
	{
		throw(ccMutexException("Invalid mutex", result));
		return ccMUTEX_RESULT_INVALID;
	}

	if((result = pthread_mutex_unlock(&mutex)) == 0)
	{
		if(state & ccMUTEX_STATE_DEBUG)
			cerr << "Mutex " << id << ": unlocked" << endl;
		return ccMUTEX_RESULT_SUCCESS;
	}

	throw(ccMutexException("Error unlocking mutex", result));
	return ccMUTEX_RESULT_FAILURE;
}

ccMutex::ccMutexResult ccMutex::TryLock(void)
{
	int result = -1;

	if(!(state & ccMUTEX_STATE_CREATED))
	{
		throw(ccMutexException("Invalid mutex", result));
		return ccMUTEX_RESULT_INVALID;
	}

	if((result = pthread_mutex_trylock(&mutex)) == EBUSY)
	{
		if(state & ccMUTEX_STATE_DEBUG)
			cerr << "Mutex " << id << ": already locked" << endl;
		return ccMUTEX_RESULT_LOCKED;
	}
	else if(result == 0)
	{
		if(state & ccMUTEX_STATE_DEBUG)
			cerr << "Mutex " << id << ": try locked" << endl;
		return ccMUTEX_RESULT_SUCCESS;
	}

	throw(ccMutexException("Error unlocking mutex", result));
	return ccMUTEX_RESULT_FAILURE;
}

void ccMutex::SetDebug(bool enable)
{
	cerr << "Mutex " << id << ": debug ";

	if(enable)
	{
		state |= ccMUTEX_STATE_DEBUG;
		cerr << "enabled";
	}
	else
	{
		state &= ~ccMUTEX_STATE_DEBUG;
		cerr << "disabled";
	}

	cerr << endl;
}

ccCondition::ccCondition(ccMutex &mutex) : state(ccCOND_STATE_NONE), mutex(mutex)
{
	int result;

	if((result = pthread_cond_init(&cond, NULL)) != 0)
	{
		throw(ccConditionException("Error creating condition", result));
	}

	state |= ccCOND_STATE_CREATED;
}

ccCondition::~ccCondition()
{
	int result;

	if(!(state & ccCOND_STATE_CREATED)) return;
	if((result = pthread_cond_destroy(&cond)) != 0)
	{
		cerr << "Error destroying condition." << endl;
		throw(ccConditionException("Error destroying condition", result));
	}
}

ccCondition::ccConditionResult ccCondition::Wait(void)
{
	int result = -1;

	if(!(state & ccCOND_STATE_CREATED))
	{
		throw(ccConditionException("Invalid condition", result));
		return ccCOND_RESULT_INVALID;
	}

	if((result = pthread_cond_wait(&cond, GetMutex())) == 0)
		return ccCOND_RESULT_SUCCESS;

	throw(ccConditionException("Error waiting on condition", result));
	return ccCOND_RESULT_FAILURE;
}

ccCondition::ccConditionResult ccCondition::Signal(void)
{
	int result = -1;

	if(!(state & ccCOND_STATE_CREATED))
	{
		throw(ccConditionException("Invalid condition", result));
		return ccCOND_RESULT_INVALID;
	}

	if((result = pthread_cond_signal(&cond)) == 0)
		return ccCOND_RESULT_SUCCESS;

	throw(ccConditionException("Error signaling condition", result));
	return ccCOND_RESULT_FAILURE;
}

ccCondition::ccConditionResult ccCondition::Broadcast(void)
{
	int result = -1;

	if(!(state & ccCOND_STATE_CREATED))
	{
		throw(ccConditionException("Invalid condition", result));
		return ccCOND_RESULT_INVALID;
	}

	if((result = pthread_cond_broadcast(&cond)) == 0)
		return ccCOND_RESULT_SUCCESS;

	throw(ccConditionException("Error broadcasting condition", result));
	return ccCOND_RESULT_FAILURE;
}

ccSemaphore::ccSemaphore(int count_init, int count_max)
	: cond(mutex), state(ccSEMA_STATE_NONE)
{
	if((count_init < 0 || count_max < 0) ||
		((count_max > 0) && (count_init > count_max)))
	{
		throw(ccSemaphoreException("Invalid initial or maximal count"));
	}

	this->count_init = count_init;
	this->count_max = count_max;

	if(cond.IsValid() && mutex.IsValid())
		state |= ccSEMA_STATE_CREATED;
}

ccSemaphore::ccSemaphoreResult ccSemaphore::Wait(void)
{
	if(!(state & ccSEMA_STATE_CREATED))
	{
		throw(ccSemaphoreException("Invalid semaphore"));
		return ccSEMA_RESULT_INVALID;
	}

	ccMutexLocker locker(mutex);

	while(count_init == 0)
	{
		if(cond.Wait() != ccCondition::ccCOND_RESULT_SUCCESS)
			return ccSEMA_RESULT_FAILURE;
	}

	count_init--;

	return ccSEMA_RESULT_SUCCESS;
}

ccSemaphore::ccSemaphoreResult ccSemaphore::TryWait(void)
{
	if(!(state & ccSEMA_STATE_CREATED))
	{
		throw(ccSemaphoreException("Invalid semaphore"));
		return ccSEMA_RESULT_INVALID;
	}

	ccMutexLocker locker(mutex);

	if(count_init == 0) return ccSEMA_RESULT_BUSY;

	count_init--;

	return ccSEMA_RESULT_SUCCESS;
}

ccSemaphore::ccSemaphoreResult ccSemaphore::Post(void)
{
	if(!(state & ccSEMA_STATE_CREATED))
	{
		throw(ccSemaphoreException("Invalid semaphore"));
		return ccSEMA_RESULT_INVALID;
	}

	ccMutexLocker locker(mutex);

	if(count_max > 0 && count_init == count_max) return ccSEMA_RESULT_OVERFLOW;

	count_init++;

	return cond.Signal() == ccCondition::ccCOND_RESULT_SUCCESS ?
		ccSEMA_RESULT_SUCCESS : ccSEMA_RESULT_FAILURE;
}

void *ccThreadEntry(void *thread)
{
	return ccThread::ThreadEntry((ccThread *)thread);
}

void *ccThread::ThreadEntry(ccThread *thread)
{
	static int sig_list[] = {
		SIGHUP, SIGINT, SIGQUIT, SIGPIPE, SIGALRM, SIGTERM, SIGCHLD, SIGWINCH,
		SIGVTALRM, SIGPROF, 0
	};

	sigset_t mask;
	sigemptyset(&mask);

	for(int i = 0; sig_list[i]; i++) sigaddset(&mask, sig_list[i]);
	
	pthread_sigmask(SIG_BLOCK, &mask, 0);

	thread->run_semaphore.Wait();

	thread->exit_code = thread->Entry();
	thread->Exit(thread->exit_code);

	cerr << "Error: ccThread::Exit() can't return." << endl;
	return NULL;
}

ccThread::ccThread(ccThreadType type)
	: type(type), state(ccTHREAD_STATE_NONE)
{
	Create();
}

ccThread::~ccThread()
{
	if(state & ccTHREAD_STATE_ATTR)
	{
		pthread_attr_destroy(&attr);
	}
}

ccThread::ccThreadResult ccThread::Create(void)
{
	int result;

	if((result = pthread_attr_init(&attr)) != 0)
	{
		throw(ccThreadException(-1, "Error initializing thread attributes", result));
		return ccTHREAD_RESULT_FAILURE;
	}

	state |= ccTHREAD_STATE_ATTR;

	if(type == ccTHREAD_TYPE_DETACHED)
	{
		if((result = pthread_attr_setdetachstate(&attr, PTHREAD_CREATE_DETACHED)) != 0)
		{
			throw(ccThreadException(-1, "Error creating detached thread", result));
			return ccTHREAD_RESULT_FAILURE;
		}
	}

	if(pthread_create(&thread, &attr, ccThreadEntry, (void *)this) != 0)
	{
		throw(ccThreadException(-1, "Error creating thread", result));
		return ccTHREAD_RESULT_FAILURE;
	}

	state |= ccTHREAD_STATE_CREATED;

	return ccTHREAD_RESULT_SUCCESS;
}

ccThread::ccThreadResult ccThread::Run(void)
{
	if(run_semaphore.Post() == ccSemaphore::ccSEMA_RESULT_SUCCESS)
		return ccTHREAD_RESULT_SUCCESS;

	return ccTHREAD_RESULT_FAILURE;
}

void ccThread::Destroy(void)
{
	ccMutexLocker locker(state_mutex);
	state |= ccTHREAD_STATE_DESTROY;
}

bool ccThread::TestDestroy(void)
{
	ccMutexLocker locker(state_mutex);
	if(state & ccTHREAD_STATE_DESTROY) return true;
	return false;
}

void *ccThread::Wait(void)
{
	if(type != ccTHREAD_TYPE_JOINABLE) return NULL;

	void *result;
	if(pthread_join(thread, &result) == 0)
		return result;

	cerr << "Error waiting on thread." << endl;
	return NULL;
}

// vi: ts=4
