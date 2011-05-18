#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.63])
AC_INIT([tconsole], [3.0], [http://www.clearfoundation.com/docs/developer/bug_tracker/])
AM_INIT_AUTOMAKE([tconsole], [3.0])
AC_CONFIG_SRCDIR([tconsole.h])
AC_CONFIG_HEADERS([config.h])

# Checks for programs.
AC_PROG_CXX

# Checks for libraries.
AC_CHECK_LIB([m], [ceil])
AC_CHECK_LIB([ncursesw], [wprintw])
AC_CHECK_LIB([pthread], [pthread_create])

# Checks for header files.
AC_CHECK_HEADERS([fcntl.h stdint.h stdlib.h string.h sys/ioctl.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_TYPE_PID_T
AC_TYPE_SIZE_T
AC_TYPE_SSIZE_T
AC_CHECK_DECLS([sys_siglist])
AC_TYPE_UINT32_T

# Checks for library functions.
AC_FUNC_ERROR_AT_LINE
AC_FUNC_FORK
AC_FUNC_MMAP
AC_FUNC_REALLOC
AC_CHECK_FUNCS([getpagesize memset munmap regcomp setenv setlocale strerror strtol])

AC_CONFIG_FILES([tconsole.spec Makefile utils/Makefile])
AC_OUTPUT
