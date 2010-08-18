packages := $(shell find . -maxdepth 1 -type d -name "app-*" | xargs -n 1 basename | sort )

all: ${packages}

${packages}:: clean Makefile.spec
	@if test ! -f $@/.ignore; then \
		if test -f $@/MANIFEST; then \
			if test ! -f "$@/Makefile.spec"; then \
				cp BUILD/Makefile.spec $@ || exit 1; \
			fi; \
			make -f Makefile.spec -C $@ all || exit 1; \
		fi; \
		if test -f $@/$@.spec; then \
			if test ! -f $@/MANIFEST; then \
				cp BUILD/Makefile.spec $@ || exit 1; \
				make -f Makefile.spec -C $@ tgz; \
			fi; \
			./BUILD/cpsrc ./BUILD/.config $@/$@.spec ~/rpms/SOURCES && \
			rpmbuild -ba $@/$@.spec; \
		fi; \
	fi

.config:
	@(cd BUILD && ./configure)

Makefile.spec: .config
	@(cd BUILD && ./subst .config Makefile.spec.in Makefile.spec)

touch:
	find . -name "MANIFEST*" | xargs touch

clean:
	$(foreach package, ${packages}, $(shell rm -f ${package}/Makefile.spec))
	$(foreach package, ${packages}, $(shell rm -f ${package}/$(package).spec))
	rm -rf /var/tmp/app-buildroot-${USER}
	rm -f BUILD/Makefile.spec
	find -type l | xargs rm -f

# vi: ts=4
