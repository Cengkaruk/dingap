#include <sys/types.h>
#include <sys/inotify.h>

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <errno.h>

int main(int argc, char *argv[])
{
	int id = inotify_init();
	if (id < 0) {
		fprintf(stderr, "inotify_init: %s\n", strerror(errno));
		return 1;
	}

	int wd = inotify_add_watch(id, "/tmp", IN_CLOSE_WRITE);
	if (wd < 0) {
		fprintf(stderr, "inotify_add_watch: %s\n", strerror(errno));
		close(id);
		return 1;
	}

	fprintf(stdout, "inotify watching: /tmp\n");

	ssize_t bytes;
	long page_size = getpagesize();
	uint8_t *buffer = malloc(page_size);
	struct inotify_event *iev = (struct inotify_event *)buffer;

	for ( ;; ) {
		bytes = read(id, (void *)iev, page_size);
		if (bytes < 0) {
			fprintf(stderr, "read: %s\n", strerror(errno));
			break;
		}
		fprintf(stdout, "inotify event: [wd: %d, mask: %8x, cookie: %8x, len: %d, name: %s\n",
			iev->wd, iev->mask, iev->cookie, iev->len, (iev->len > 1) ? iev->name : "(null)");
	}

	close(id);

	return 0;
}

// vi: ts=4
