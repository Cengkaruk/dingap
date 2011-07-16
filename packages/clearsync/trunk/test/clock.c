#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <string.h>
#include <errno.h>

int main(int argc, char *argv[])
{
//	clockid_t id = CLOCK_REALTIME;
//	clockid_t id = CLOCK_MONOTONIC;
	clockid_t id = CLOCK_MONOTONIC_RAW;
//	clockid_t id = CLOCK_PROCESS_CPUTIME_ID;
//	clockid_t id = CLOCK_THREAD_CPUTIME_ID;

	struct timespec ts_res;
	if (clock_getres(id, &ts_res) < 0) {
		fprintf(stderr, "clock_getres: %s\n", strerror(errno));
		exit(EXIT_FAILURE);
	}
	fprintf(stdout, "clock_getres: %ldns\n", ts_res.tv_nsec);

	sleep(3);

	struct timespec ts_now;
	if (clock_gettime(id, &ts_now) < 0) {
		fprintf(stderr, "clock_gettime: %s\n", strerror(errno));
		exit(EXIT_FAILURE);
	}
	fprintf(stdout, "clock_gettime: %lds+%ldns\n", ts_now.tv_sec, ts_now.tv_nsec);

	struct timespec ts_mytime;
	memset(&ts_mytime, 0, sizeof(struct timespec));
	if (clock_settime(id, &ts_mytime) < 0) {
		fprintf(stderr, "clock_settime: %s\n", strerror(errno));
		exit(EXIT_FAILURE);
	}
}

// vi: ts=4
