#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.63])
AC_INIT([eziod], [1.1], [http://www.clearfoundation.com/docs/developer/bug_tracker/])
AM_INIT_AUTOMAKE([eziod], [1.1])
AC_CONFIG_SRCDIR([ezio.h])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_MACRO_DIR([m4])

# Checks for programs.
AC_PROG_CXX
AC_PROG_CC
AC_PROG_INSTALL

# Checks for libraries.
AC_CHECK_LIB([lua], [lua_newstate], [], [
    AC_MSG_ERROR([LUA library not found but is required.])])
AC_CHECK_LIB([sensors], [sensors_init], [], [
    AC_MSG_ERROR([lm-sensors library not found but is required.])])

# Checks for header files.
AC_CHECK_HEADERS([arpa/inet.h fcntl.h netinet/in.h stdint.h stdlib.h string.h sys/ioctl.h sys/param.h sys/socket.h sys/statvfs.h sys/time.h syslog.h termios.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_TYPE_INT32_T
AC_TYPE_SIZE_T
AC_TYPE_SSIZE_T
AC_TYPE_UINT32_T
AC_TYPE_UINT8_T

# Checks for library functions.
AC_CHECK_FUNCS([getpagesize gettimeofday inet_ntoa memchr memset select socket strdup strerror])

# Generate these files...
AC_CONFIG_FILES([Makefile eziod.spec])

AC_OUTPUT
