#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <stdint.h>
#include <string.h>
#include <errno.h>
#include <fcntl.h>
#include <signal.h>

#include <sys/types.h>
#include <sys/stat.h>

extern int errno;

int main(int argc, char *argv[])
{
	signal(SIGTERM, SIG_IGN);
	size_t chunk_size = getpagesize() * 4;
	//size_t chunk_size = getpagesize();
	uint8_t buffer[chunk_size];
	ssize_t bytes, total = 0;
	char tick = '.';
	//fcntl(0, F_SETFL, O_NONBLOCK);
	for ( ;; ) {
		bytes = read(0, buffer, chunk_size);
		if (bytes == 0 || bytes == -1) break;
		//fputc('.', stdout);
		//fflush(stdout);
		total += bytes;
	}
	fprintf(stdout, "\n%d bytes read\n", total);
}

/*
 * vi: ts=4
 * */
