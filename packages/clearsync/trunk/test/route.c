#include <asm/types.h>

#include <sys/socket.h>
#include <sys/time.h>

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
	sa.nl_groups = RTMGRP_IPV4_ROUTE | RTMGRP_IPV6_ROUTE;

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
	struct rtmsg *rth;
	struct timeval tv;

	gettimeofday(&tv, NULL);

	for ( ;; ) {
		len = recvmsg(fd, &msg, 0);

		for (nh = (struct nlmsghdr *)buf;
			NLMSG_OK(nh, len); nh = NLMSG_NEXT(nh, len)) {

			if (nh->nlmsg_type == NLMSG_NOOP) continue;

			if (nh->nlmsg_type == NLMSG_DONE) {
				fprintf(stderr, "End of multi-part message.\n");
				return 0;
			}

			if (nh->nlmsg_type == NLMSG_ERROR) {
				fprintf(stderr, "Error.\n");
				return 1;
			}

			if (nh->nlmsg_type == NLMSG_OVERRUN) {
				fprintf(stderr, "Overrun.\n");
				return 1;
			}

			if (nh->nlmsg_type != RTM_NEWROUTE &&
				nh->nlmsg_type != RTM_DELROUTE) continue;

			rth = (struct rtmsg *)NLMSG_DATA(nh);
			if (rth->rtm_table != 254) continue;

			//fprintf(stderr, "family: %hhu\n", rth->rtm_family);
			if (rth->rtm_family != AF_INET &&
				rth->rtm_family != AF_INET6) continue;
			if (rth->rtm_family == AF_INET6) {
				fprintf(stderr, "No IPv6 support (yet).\n");
				continue;
			}

			switch (nh->nlmsg_type) {
			case RTM_NEWROUTE:
				gettimeofday(&tv, NULL);
				fprintf(stderr, "Route added.\n");
				break;
			case RTM_DELROUTE:
				gettimeofday(&tv, NULL);
				fprintf(stderr, "Route deleted\n");
				break;
			default:
				fprintf(stderr, "Received message, type: %d\n",
					nh->nlmsg_type);
			}
		}
	}

	return 0;
}
