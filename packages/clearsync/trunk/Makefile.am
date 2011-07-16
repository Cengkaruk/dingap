AUTOMAKE_OPTIONS = foreign

ACLOCAL_AMFLAGS = -I m4
AM_CXXFLAGS = ${CXXFLAGS} -I./include

EXTRA_DIST = csmain.h

docdir = $(datadir)/doc/@PACKAGE@
doc_DATA = LICENSE

lib_LTLIBRARIES = libclearsync.la

libclearsync_la_SOURCES = csconf.cpp csevent.cpp cslog.cpp csplugin.cpp \
	csthread.cpp cstimer.cpp csutil.cpp
libclearsync_la_CXXFLAGS = ${AM_CXXFLAGS} -D_CS_INTERNAL=1
libclearsync_la_includedir = $(includedir)/clearsync
libclearsync_la_include_HEADERS = include/clearsync/csconf.h include/clearsync/csevent.h \
	include/clearsync/csexception.h include/clearsync/cslog.h include/clearsync/csplugin.h \
	include/clearsync/csthread.h include/clearsync/cstimer.h include/clearsync/csutil.h

sbin_PROGRAMS = clearsyncd

clearsyncd_SOURCES = csmain.cpp
clearsyncd_LDADD = libclearsync.la
clearsyncd_CXXFLAGS = ${AM_CXXFLAGS} -D_CS_INTERNAL=1

