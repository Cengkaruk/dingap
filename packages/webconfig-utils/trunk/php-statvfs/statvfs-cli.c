#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <sys/types.h>
#include <sys/statvfs.h>

int main(int argc, char *argv[])
{
	int i;
	struct statvfs stats;

	for(i = 1; i < argc; i++) {
		if (statvfs(argv[i], &stats) != 0) continue;
		fsblkcnt_t kblocks = ((unsigned long long)stats.f_frsize * (unsigned long long)stats.f_blocks) / 1024ul;
		fsblkcnt_t kfree = ((unsigned long long)stats.f_frsize * (unsigned long long)stats.f_bfree) / 1024ul;
		fsblkcnt_t kavail = ((unsigned long long)stats.f_frsize * (unsigned long long)stats.f_bavail) / 1024ul;
		fsblkcnt_t kused = kblocks - kfree;
		printf("%u:%u\n", kused, kblocks);
	}
	exit(EXIT_SUCCESS);
}
