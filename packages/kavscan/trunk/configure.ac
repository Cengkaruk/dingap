#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.63])
AC_INIT([kavscan], [1.0], [http://www.clearfoundation.com/docs/developer/bug_tracker/])
AM_INIT_AUTOMAKE([kavscan], [1.0])
AC_CONFIG_SRCDIR([kavscan.cpp])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_MACRO_DIR([m4])

# Checks for programs.
AC_PROG_CXX
AC_PROG_CC
AC_PROG_INSTALL

# Checks for libraries.
AC_CHECK_LIB([dl], [dlopen], [], [
	AC_MSG_ERROR([dlopen not found but is required.])])
AC_CHECK_LIB([expat], [XML_ParserCreate], [], [
	AC_MSG_ERROR([XML_ParserCreate not found but is required.])])
AC_CHECK_LIB([pthread], [pthread_create], [], [
	AC_MSG_ERROR([pthread_create not found but is required.])])

# Checks for header files.
AC_CHECK_HEADERS([fcntl.h limits.h netinet/in.h stdint.h stdlib.h string.h sys/socket.h sys/time.h syslog.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_TYPE_PID_T
AC_TYPE_SIZE_T
AC_TYPE_SSIZE_T
AC_TYPE_UINT32_T
AC_TYPE_UINT8_T

# Checks for library functions.
AC_FUNC_LSTAT_FOLLOWS_SLASHED_SYMLINK
AC_CHECK_FUNCS([gettimeofday memchr memset pathconf realpath select socket strcasecmp strerror])

# SDK location
AC_ARG_WITH([kavsdk],
	AS_HELP_STRING([--with-kavsdk=PATH],
	[Path to KAV SDK directory]),
	[KAVSDK="$withval"; KAVSDK="$withval";])

AC_SUBST([KAVSDK])

# Output
AC_CONFIG_FILES([Makefile kavscan.spec])
AC_OUTPUT
