#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: clearos-coding-standard
Version: 5.9.9.0
Release: 1%{dist}
Summary: ClearOS coding standard for PHP CodeSniffer
License: GPLv3
Group: ClearOS/Tools
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
# FIXME: this is from EPEL, so we'll have a broken repo with these dpes
Requires: php-pear-PHP-CodeSniffer
BuildArch: noarch 
BuildRoot: %_tmppath/%name-%version-buildroot

%description
ClearOS coding standard for PHP CodeSniffer

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

mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/clearos/devel/code_sniffer

cp -r ClearOS $RPM_BUILD_ROOT/usr/share/clearos/devel/code_sniffer

#------------------------------------------------------------------------------
# F I L E S
#------------------------------------------------------------------------------

%files
%defattr(-,root,root)
%dir /usr/share/clearos/devel
%dir /usr/share/clearos/devel/code_sniffer
/usr/share/clearos/devel
