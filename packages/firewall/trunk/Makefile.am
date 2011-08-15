AUTOMAKE_OPTIONS = foreign

ACLOCAL_AMFLAGS = -I m4
AM_CFLAGS = ${CFLAGS} ${extra_CFLAGS}

firewall_SOURCES = firewall.c ifconfig.c iptables.c xshared.c
firewall_LDFLAGS = -rdynamic
firewall_CFLAGS = ${AM_CFLAGS}
firewall_LDADD = -lip4tc -lxtables -lm

firewall6_SOURCES = firewall.c ifconfig.c ip6tables.c xshared.c
firewall6_LDFLAGS = -rdynamic
firewall6_CFLAGS = ${AM_CFLAGS} -DFIREWALL_IPV6
firewall6_LDADD = -lip6tc -lxtables -lm

sbin_PROGRAMS = firewall

if ENABLE_IPV6
sbin_PROGRAMS += firewall6
endif

CLEANFILES = firewall.spec
