#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.63])
AC_INIT([firewall], [1.4.7], [http://www.clearfoundation.com/docs/developer/bug_tracker/])
AM_INIT_AUTOMAKE([firewall], [1.4.7])
AC_CONFIG_SRCDIR([firewall.c])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_MACRO_DIR([m4])

# Checks for programs.
AC_PROG_CC
AC_PROG_INSTALL
AM_PROG_LIBTOOL

# Checks for libraries.
AC_CHECK_LIB([lua], [lua_newstate], [], [
    AC_MSG_ERROR([LUA library not found but is required.])])

# Checks for header files.
AC_CHECK_HEADERS([arpa/inet.h fcntl.h lauxlib.h limits.h lua.h lualib.h netdb.h netinet/in.h stdlib.h string.h sys/ioctl.h syslog.h sys/param.h sys/socket.h sys/time.h unistd.h], [], [
    AC_MSG_ERROR([Required header file not found.])])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_TYPE_SIZE_T

# Checks for library functions.
AC_FUNC_MALLOC
AC_CHECK_FUNCS([atexit gethostbyname getpagesize gettimeofday inet_ntoa memset pow socket strchr strdup strerror strncasecmp uname])

# Enable/disable features
AC_ARG_ENABLE([ipv4],
    AS_HELP_STRING([--disable-ipv4], [Do not build iptables]),
    [enable_ipv4="$enableval"], [enable_ipv4="yes"])
AC_ARG_ENABLE([ipv6],
    AS_HELP_STRING([--disable-ipv6], [Do not build ip6tables]),
    [enable_ipv6="$enableval"], [enable_ipv6="yes"])
AC_ARG_WITH([xtlibdir],
    AS_HELP_STRING([--with-xtlibdir=PATH],
    [Where to find the Xtables extensions [[LIBEXECDIR/xtables]]]),
    [xtlibdir="$withval"],
    [xtlibdir="${libexecdir}/xtables"])

# Extra CFLAGS
extra_CFLAGS="-DXTABLES_LIBDIR=\\\"\${xtlibdir}\\\" -DXTABLES_INTERNAL"

# Automake conditionals
AM_CONDITIONAL([ENABLE_IPV4], [test "$enable_ipv4" = "yes"])
AM_CONDITIONAL([ENABLE_IPV6], [test "$enable_ipv6" = "yes"])

# Automake substitutions
AC_SUBST([xtlibdir])
AC_SUBST([extra_CFLAGS])

# Generate these files...
AC_CONFIG_FILES([Makefile firewall.spec])

AC_OUTPUT
