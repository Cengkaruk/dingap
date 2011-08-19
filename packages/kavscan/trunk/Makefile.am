AUTOMAKE_OPTIONS = foreign

ACLOCAL_AMFLAGS = -I m4
AM_CXXFLAGS = ${CXXFLAGS} -I ${KAVSDK}/include
AM_LDFLAGS = ${LDFLAGS} -L ${KAVSDK}/lib

EXTRA_DIST = ksutil.h kavscan.conf init.d/kavdscan

bin_PROGRAMS = kavscan
kavscan_SOURCES = kavscan.cpp ksutil.cpp
kavscan_LDADD = -lsdk8l3
kavscan_CXXFLAGS = ${AM_CXXFLAGS}

sbin_PROGRAMS = kavdscan
kavdscan_SOURCES = kavdscan.cpp ksutil.cpp
kavdscan_CXXFLAGS = ${AM_CXXFLAGS}

