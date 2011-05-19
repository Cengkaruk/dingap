Name: clearos-devel
Version: 5.9.9.0
Release: 1%{dist}
Summary: ClearOS developer tools
License: GPLv3
Group: ClearOS/Tools
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
# FIXME: make deps from EPEL optional -- we have broken repos right now.
Requires: clearos-base
Requires: clearos-coding-standard
Requires: mock
Requires: phpdoc
Requires: php-phpunit-PHPUnit
Requires: php-pear-PHP-CodeSniffer
Requires: plague-client
Requires: rpm-build
Requires: rsync
Requires: subversion
BuildArch: noarch 
BuildRoot: %_tmppath/%name-%version-buildroot

%description
ClearOS developer tools

%prep
%setup
%build

%install

mkdir -p -m 755 $RPM_BUILD_ROOT/etc/mock
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/bin

install -m 644 clearos-6-i386-base.cfg $RPM_BUILD_ROOT/etc/mock/
#install -m 644 clearos-6-x86_64-base.cfg $RPM_BUILD_ROOT/etc/mock/
install -m 755 clearos $RPM_BUILD_ROOT/usr/bin

%files
%defattr(-,root,root)
/etc/mock/clearos-6-i386-base.cfg     
#/etc/mock/clearos-6-x86_64-base.cfg   
/usr/bin/clearos
