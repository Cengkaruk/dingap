#define _GNU_SOURCE
#define _MIN_UID	500
#define _MAX_UID	65533

#include <pwd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>

int main(void)
{
	struct passwd pw, *pwp;
	char *buf = NULL;
	int r, pages = 0;
	long page_size = getpagesize();
	if (page_size == -1L) page_size = 4096;
	unsigned long accounts = 0UL;

	setpwent();
	for ( ;; ) {
		r = getpwent_r(&pw, buf, page_size * pages, &pwp);
		if (r == ENOENT) break;
		else if (r == ERANGE) {
			pages++;
			buf = realloc(buf, page_size * pages);
			if (!buf) {
				fprintf(stderr, "realloc: %s", strerror(ENOMEM));
				endpwent();
				exit(EXIT_FAILURE);
			}
			fprintf(stderr, "increased buffer to: %ld bytes\n",
				page_size * pages);
			continue;
		}
		else if (r != 0) {
			fprintf(stderr, "getpwent_r: %s\n", strerror(errno));
			endpwent();
			if (buf) free(buf);
			exit(EXIT_FAILURE);
		}

		if (pwp->pw_uid < _MIN_UID || pwp->pw_uid > _MAX_UID) continue;
		printf("%s (%d)\n", pwp->pw_name, pwp->pw_uid);
		accounts++;
	}
	endpwent();
	if (buf) free(buf);
	printf("accounts: %lu\n", accounts);
	exit(EXIT_SUCCESS);
}

