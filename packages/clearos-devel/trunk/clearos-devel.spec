#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: clearos-devel
Version: 6.0
Release: 0.4%{dist}
Summary: ClearOS developer tools
License: GPLv3
Group: ClearOS/Tools
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
# FIXME: these are from EPEL, so we'll have a broken repo with these dpes
Requires: phpdoc
Requires: php-phpunit-PHPUnit
Requires: php-pear-PHP-CodeSniffer
Requires: subversion
Requires: rpm-build
BuildArch: noarch 
BuildRoot: %_tmppath/%name-%version-buildroot

%description
ClearOS developer tools

#------------------------------------------------------------------------------
# B U I L D
#------------------------------------------------------------------------------

%prep
%setup
%build

#------------------------------------------------------------------------------
# I N S T A L L  F I L E S
#------------------------------------------------------------------------------

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

mkdir -p -m 755 $RPM_BUILD_ROOT/usr/bin
install -m 755 clearos $RPM_BUILD_ROOT/usr/bin

#------------------------------------------------------------------------------
# F I L E S
#------------------------------------------------------------------------------

%files
%defattr(-,root,root)
/usr/bin/clearos
