AUTOMAKE_OPTIONS = foreign
ACLOCAL_AMFLAGS = -I m4

EXTRA_DIST = csplugin-example.conf

AM_CFLAGS = ${CFLAGS}
AM_CXXFLAGS = ${CXXFLAGS}

lib_LTLIBRARIES = libcsplugin-example.la

libcsplugin_example_la_SOURCES = csplugin-example.cpp
libcsplugin_example_la_CXXFLAGS = ${AM_CXXFLAGS}
