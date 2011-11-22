#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.63])
AC_INIT([csplugin-filewatch], [1.0], [http://www.clearfoundation.com/docs/developer/bug_tracker/])
AM_INIT_AUTOMAKE([csplugin-filewatch], [1.0])
AC_CONFIG_SRCDIR([csplugin-filewatch.cpp])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_MACRO_DIR([m4])

# Checks for programs.
AC_PROG_CXX
AC_PROG_INSTALL
AM_PROG_LIBTOOL
AC_PROG_RANLIB

# Checks for libraries.

# Checks for header files.
AC_HEADER_STDBOOL
AC_CHECK_HEADERS([limits.h sys/time.h sys/inotify.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_C_INLINE
AC_TYPE_SIZE_T
AC_TYPE_SSIZE_T
AC_TYPE_UINT32_T
AC_TYPE_UINT8_T

# Checks for library functions.
AC_CHECK_FUNCS([memset strdup strerror strncasecmp realloc realpath])

# Output
AC_CONFIG_FILES([Makefile csplugin-filewatch.spec])
AC_OUTPUT
