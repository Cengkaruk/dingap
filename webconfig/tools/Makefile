NAME := UNDEFINED

WORKDIR := $(shell pwd)

SPECFILE = $(firstword $(wildcard $(NAME)/packaging/*.spec))

RPM_DEFINES := --define "_sourcedir $(WORKDIR)" \
		--define "_specdir $(WORKDIR)" \
		--define "_builddir $(WORKDIR)" \
		--define "_srcrpmdir $(WORKDIR)" \
		--define "_rpmdir $(WORKDIR)"

RPM_WITH_DIRS = rpmbuild $(RPM_DEFINES)
QUERY_FORMAT = $(shell sed -n 's/^Source:\s*\(.*\).tar.gz/\1/ip' $(SPECFILE) | head -1)
NAME_VER = $(shell rpm $(RPM_DEFINES) -q --qf "$(QUERY_FORMAT)\n" --specfile $(SPECFILE)| head -1)
SOURCE_RPM = $(shell rpm $(RPM_DEFINES) -q --qf "%{NAME}-%{VERSION}-%{RELEASE}.src.rpm\n" --specfile $(SPECFILE)| head -1)

ifeq ($(wildcard $(NAME)),)
$(error "$(NAME) directory not found")
endif

ifeq ($(SPECFILE),)
$(error "No spec file found for $(NAME)")
endif

ifeq ($(NAME_VER),)
$(error "$(SPECFILE) doesn't contain valid source")
endif

ifeq ($(SOURCE_RPM),)
$(error "$(SPECFILE) doesn't produce a source rpm")
endif

ifneq ($(wildcard $(NAME_VER)),)
$(error "$(NAME_VER) directory already exists")
endif

.PHONY: source srpm $(SOURCE_RPM) $(NAME_VER)

$(NAME_VER).tar.gz:
	@mv $(NAME) $(NAME_VER)
	@tar --exclude-vcs --dereference --create --gzip --file $(NAME_VER).tar.gz $(NAME_VER)
	@mv $(NAME_VER) $(NAME)

source: $(NAME_VER).tar.gz

$(SOURCE_RPM): $(NAME_VER).tar.gz
	@$(RPM_WITH_DIRS) --nodeps -bs $(SPECFILE)

srpm: $(SOURCE_RPM)
