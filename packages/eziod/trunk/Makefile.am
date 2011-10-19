AUTOMAKE_OPTIONS = foreign

ACLOCAL_AMFLAGS = -I m4
AM_CXXFLAGS = ${CXXFLAGS}

EXTRA_DIST = ezio.h ezio300.h ezioex.h eziod.conf eziod.init eziod.modules icon5x8.cpp scripts/get_dyndnsinfo.php

sbin_PROGRAMS = eziod

eziod_SOURCES = ezio300.cpp eziod.cpp
eziod_CXXFLAGS = ${AM_CXXFLAGS} -D_EZIO_PORT=\"/dev/ttyS1\" -D_EZIO_BAUD=2400
