#include <asm/types.h>

#include <sys/socket.h>

#include <linux/netlink.h>
#include <linux/rtnetlink.h>

#include <stdio.h>
#include <string.h>
#include <errno.h>

int main(int argc, char *argv[])
{
	int fd;
	struct sockaddr_nl sa;

	memset(&sa, 0, sizeof(sa));
	sa.nl_family = AF_NETLINK;
	sa.nl_groups = RTMGRP_NEIGH;

	fd = socket(AF_NETLINK, SOCK_RAW, NETLINK_ROUTE);
	if (fd == -1) {
		fprintf(stderr, "socket: %s", strerror(errno));
		return 1;
	}
	if (bind(fd, (struct sockaddr *) &sa, sizeof(sa)) == -1) {
		fprintf(stderr, "bind: %s", strerror(errno));
		return 1;
	}

	int len;
	char buf[4096];
	struct iovec iov = { buf, sizeof(buf) };
	struct msghdr msg = { (void *)&sa, sizeof(sa), &iov, 1, NULL, 0, 0 };
	struct nlmsghdr *nh;

	for ( ;; ) {
		len = recvmsg(fd, &msg, 0);

		for (nh = (struct nlmsghdr *)buf;
			NLMSG_OK(nh, len); nh = NLMSG_NEXT(nh, len)) {

			if (nh->nlmsg_type == NLMSG_DONE) {
				fprintf(stderr, "End of multi-part message.\n");
				return 0;
			}

			if (nh->nlmsg_type == NLMSG_ERROR) {
				fprintf(stderr, "Error.\n");
				return 1;
			}

			fprintf(stderr, "Received message, type: %d\n", nh->nlmsg_type);
		}
	}

	return 0;
}
