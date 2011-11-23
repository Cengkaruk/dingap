#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.63])
AC_INIT([csplugin-filesync], [1.0], [http://www.clearfoundation.com/docs/developer/bug_tracker/])
AM_INIT_AUTOMAKE([csplugin-filesync], [1.0])
AC_CONFIG_SRCDIR([csplugin-filesync.cpp])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_MACRO_DIR([m4])

# Checks for programs.
AC_PROG_CXX
AC_PROG_INSTALL
AM_PROG_LIBTOOL
AC_PROG_RANLIB

# Checks for libraries.
AC_CHECK_LIB([rsync], [rs_strerror], [], [AC_MSG_ERROR([librsync not found.])])
AC_CHECK_LIB([crypto], [AES_encrypt], [], [AC_MSG_ERROR([libcrypto not found.])])

# Checks for header files.
AC_CHECK_HEADERS([clearsync/csplugin.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_C_INLINE
AC_TYPE_SIZE_T
AC_TYPE_OFF_T
AC_TYPE_UINT32_T
AC_TYPE_UINT8_T

# Checks for library functions.
AC_CHECK_FUNCS([memset select])

# Output files
AC_CONFIG_FILES([Makefile csplugin-filesync.spec])
AC_OUTPUT
