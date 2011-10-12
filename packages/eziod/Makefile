SOURCE=./trunk
RPMDIR=~/rpms
TARGET?=i386
VER_RELEASE=8
VER_MAJOR=1
VER_MINOR=0
VERSION=$(VER_MAJOR).$(VER_MINOR)

all:
	make rpm

rpm:
	rm -f eziod-$(VERSION)
	ln -sf $(SOURCE) eziod-$(VERSION)
	tar -cvzhf eziod-$(VERSION).tar.gz eziod-$(VERSION)
	mv eziod-$(VERSION).tar.gz $(RPMDIR)/SOURCES
	sed -e 's/@VER_MAJOR@/$(VER_MAJOR)/' -e 's/@VER_MINOR@/$(VER_MINOR)/' \
		-e 's/@VER_RELEASE@/$(VER_RELEASE)/' \
		eziod.spec.in > eziod.spec
	rpmbuild --target=$(TARGET) -ba eziod.spec

clean:
	rm -f eziod.spec
	find . -maxdepth 1 -type l | xargs rm -f

