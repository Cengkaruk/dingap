AUTOMAKE_OPTIONS = foreign

ACLOCAL_AMFLAGS = -I m4
AM_CXXFLAGS = ${CXXFLAGS} -I./include

SUBDIRS = plugin
lib_LTLIBRARIES = libclearsync.la

libclearsync_la_CXXFLAGS = ${AM_CXXFLAGS} -D_CS_INTERNAL=1
libclearsync_la_SOURCES = csconf.cpp csevent.cpp cslog.cpp csplugin.cpp \
	csthread.cpp cstimer.cpp csutil.cpp

sbin_PROGRAMS = clearsyncd

clearsyncd_SOURCES = csmain.cpp
clearsyncd_LDADD = libclearsync.la
clearsyncd_CXXFLAGS = ${AM_CXXFLAGS} -D_CS_INTERNAL=1

