#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.63])
AC_INIT([clearsync], [1.2], [http://www.clearfoundation.com/docs/developer/bug_tracker/])
AM_INIT_AUTOMAKE([clearsync], [1.2])
AC_CONFIG_SRCDIR([csconf.cpp])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_MACRO_DIR([m4])

# Checks for programs.
AC_PROG_CXX
AC_PROG_CC
AC_PROG_INSTALL
AM_PROG_LIBTOOL

# Checks for libraries.
AC_CHECK_LIB([rt], [timer_create], [], [
	AC_MSG_ERROR([timer_create not found but is required.])])
AC_CHECK_LIB([dl], [dlopen], [], [
	AC_MSG_ERROR([dlopen not found but is required.])])
AC_CHECK_LIB([expat], [XML_ParserCreate], [], [
	AC_MSG_ERROR([XML_ParserCreate not found but is required.])])
AC_CHECK_LIB([pthread], [pthread_create], [], [
	AC_MSG_ERROR([pthread_create not found but is required.])])
AC_CHECK_LIB([crypto], [CRYPTO_set_locking_callback], [], [
	AC_MSG_ERROR([CRYPTO_set_locking_callback not found but is required.])])

# Check for optional parameters
AC_ARG_ENABLE(debug, AS_HELP_STRING([--enable-debug], [Enable heavy debug output]))

AS_IF([test "x$enable_debug" = "xyes"], [
    AC_DEFINE([_CS_DEBUG], [1], [Define to enable heavy debug output.])
])

# Checks for header files.
AC_CHECK_HEADERS([limits.h stddef.h stdint.h stdlib.h string.h sys/socket.h sys/time.h syslog.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_C_INLINE
AC_TYPE_PID_T
AC_TYPE_SIZE_T
AC_TYPE_SSIZE_T
AC_TYPE_UINT32_T
AC_TYPE_UINT8_T

# Checks for library functions.
AC_FUNC_MALLOC
AC_FUNC_REALLOC
AC_CHECK_FUNCS([clock_gettime endpwent getpagesize gettimeofday localtime_r memset pathconf socket strcasecmp strerror])

AC_CONFIG_FILES([Makefile clearsync.spec])
AC_OUTPUT
