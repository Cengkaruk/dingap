NAME := UNDEFINED

SPECFILE = $(firstword $(wildcard $(NAME)/packaging/*.spec))

WORKDIR := $(shell pwd)

RPM_DEFINES := --define "_sourcedir $(WORKDIR)" \
		--define "_specdir $(WORKDIR)" \
		--define "_builddir $(WORKDIR)" \
		--define "_srcrpmdir $(WORKDIR)" \
		--define "_rpmdir $(WORKDIR)"

RPM_WITH_DIRS = rpmbuild $(RPM_DEFINES)

NAME_VER := $(shell rpm $(RPM_DEFINES) -q --qf "%{NAME}-%{VERSION}\n" --specfile $(SPECFILE)| head -1)

$(NAME):
	$(error "$(NAME) directory does not contain packaging information")

source: $(NAME)
	@mv $(NAME) $(NAME_VER)
	tar --exclude-vcs --dereference --create --gzip --verbose --file $(NAME_VER).tar.gz $(NAME_VER)
	@mv $(NAME_VER) $(NAME)

srpm: source
	$(RPM_WITH_DIRS) --nodeps -bs $(SPECFILE)
