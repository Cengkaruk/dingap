VER_MAJOR?=1
VER_MINOR?=0
VERSION="$(VER_MAJOR).$(VER_MINOR)"
LUA_VERSION=5.1.4
CFLAGS?=-pipe -I. -O2 -s
INCLUDE=-I./lua-$(LUA_VERSION)/src
LDFLAGS=-L./lua-$(LUA_VERSION)/src
PORT?="/dev/ttyS1"
BAUD?="2400"
DEFS=-D_EZIO_PORT=\"$(PORT)\" -D_EZIO_BAUD=$(BAUD) -D_EZIO_VERSION=\"$(VERSION)\"
OBJECTS=ezio300.o
SOURCE=eziod.cpp ezio300.cpp
EZIOD_LIBS=-llua -ldl -lsensors
TARGETS=eziod

ifneq ($(shell which sdl-config 2>/dev/null),)
DEFS+=-D_EZIOEMUL
CFLAGS+=$(shell sdl-config --cflags)
SOURCE+=ezioemul.cpp
EZIOEMUL_LIBS=-lutil $(shell sdl-config --libs)
TARGETS+=ezioemul
endif

CXXFLAGS=$(CFLAGS)
DEPS=$(patsubst %.cpp,%.d,$(SOURCE))

all:
	@echo "Compiler: $(CXX) $(CXXFLAGS) $(INCLUDE)"
	@echo "Linker: $(LD) $(LDFLAGS)"
	@echo "Defines: $(DEFS)"
	@echo "EZIOd Libraries: $(EZIOD_LIBS)"
	@echo "EZIOemul Libraries: $(EZIOEMUL_LIBS)"
	$(MAKE) deps
	$(MAKE) lua $(TARGETS)

deps: $(SOURCE)
	@echo "[D] $^"
	@$(CXX) -MD -E $(CXXFLAGS) $(INCLUDE) $(DEFS) $^ > /dev/null

%.o : %.cpp
	@echo "[C] $@"
	@$(CXX) -c $(CXXFLAGS) $(INCLUDE) $(DEFS) -o $@ $<

-include $(DEPS)

lua:
	$(MAKE) -C lua-$(LUA_VERSION) linux

eziod: eziod.o $(OBJECTS) Makefile
	@echo "[L] $@"
	@$(CXX) $(CXXFLAGS) $(INCLUDE) $(LDFLAGS) eziod.o $(OBJECTS) $(EZIOD_LIBS) -o $@

ezioemul: ezioemul.o $(OBJECTS) Makefile
	@echo "[L] $@"
	@$(CXX) $(CXXFLAGS) $(INCLUDE) $(LDFLAGS) ezioemul.o $(OBJECTS) $(EZIOEMUL_LIBS) -o $@

clean:
	rm -f eziod ezioemul *.o *.d

dist-clean:
	$(MAKE) clean
	$(MAKE) -C lua-$(LUA_VERSION) clean

# vi: ts=4
