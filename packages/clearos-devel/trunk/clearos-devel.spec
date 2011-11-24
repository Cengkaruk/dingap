Name: clearos-devel
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: ClearOS developer tools
License: GPLv3
Group: ClearOS/Tools
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
# FIXME: make deps from EPEL optional -- we have broken repos right now.
# "Development tools" in comps.xml
Requires: autoconf
Requires: automake
Requires: binutils
Requires: bison
Requires: flex
Requires: gcc
Requires: gcc-c++
Requires: gettext
Requires: libtool
Requires: make
Requires: patch
Requires: pkgconfig
Requires: redhat-rpm-config
Requires: rpm-build
Requires: byacc
Requires: cscope
Requires: ctags
Requires: cvs
Requires: diffstat
Requires: doxygen
Requires: elfutils
Requires: gcc-gfortran
Requires: git
Requires: indent
Requires: intltool
Requires: patchutils
Requires: rcs
Requires: subversion
Requires: swig
Requires: systemtap
Requires: ElectricFence
Requires: ant
Requires: babel
Requires: babel
Requires: bzr
Requires: chrpath
Requires: cmake
Requires: compat-gcc-34
Requires: compat-gcc-34-c++
Requires: compat-gcc-34-g77
Requires: dejagnu
Requires: expect
Requires: gcc-gnat
Requires: gcc-java
Requires: gcc-objc
Requires: gcc-objc++
Requires: imake
Requires: jpackage-utils
Requires: libstdc++-docs
Requires: mercurial
Requires: mod_dav_svn
Requires: nasm
Requires: perltidy
Requires: python-docs
Requires: rpmdevtools
Requires: rpmlint
Requires: systemtap-sdt-devel
Requires: systemtap-server
# Other handy tools
Requires: rsync
# App and install test development
Requires: clearos-base
Requires: clearos-coding-standard
Requires: phpdoc
Requires: php-phpunit-PHPUnit
Requires: php-pear-PHP-CodeSniffer
Requires: syslinux
# Build system
Requires: mock
Requires: plague-client
BuildRoot: %_tmppath/%name-%version-buildroot

%description
ClearOS developer tools

%prep
%setup
%build

%install

mkdir -p -m 755 $RPM_BUILD_ROOT%{_sysconfdir}/mock
mkdir -p -m 755 $RPM_BUILD_ROOT%{_sysconfdir}/yum.repos.d
mkdir -p -m 755 $RPM_BUILD_ROOT%{_bindir}

%ifarch x86_64
install -m 644 clearos-6-x86_64-base.cfg $RPM_BUILD_ROOT%{_sysconfdir}/mock/
%else
install -m 644 clearos-6-i386-base.cfg $RPM_BUILD_ROOT%{_sysconfdir}/mock/
%endif

#install -m 644 clearos-devel.repo $RPM_BUILD_ROOT%{_sysconfdir}/yum.repos.d
install -m 755 clearos $RPM_BUILD_ROOT%{_bindir}


%files
%defattr(-,root,root)
%ifarch x86_64
%{_sysconfdir}/mock/clearos-6-x86_64-base.cfg     
%else
%{_sysconfdir}/mock/clearos-6-i386-base.cfg     
%endif
# %{_sysconfdir}/yum.repos.d/clearos-devel.repo
%{_bindir}/clearos
