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
Requires: clearos-base
Requires: clearos-coding-standard
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

%post

# FIXME: this is just a hack to get the basics going in
# Developer 2 release
/usr/sbin/addsudo /bin/cat clearos-devel
/usr/sbin/addsudo /bin/chmod clearos-devel
/usr/sbin/addsudo /bin/chown clearos-devel
/usr/sbin/addsudo /bin/cp clearos-devel
/usr/sbin/addsudo /bin/kill clearos-devel
/usr/sbin/addsudo /bin/ls clearos-devel
/usr/sbin/addsudo /bin/mkdir clearos-devel
/usr/sbin/addsudo /bin/mv clearos-devel
/usr/sbin/addsudo /bin/rm clearos-devel
/usr/sbin/addsudo /bin/touch clearos-devel
/usr/sbin/addsudo /sbin/chkconfig clearos-devel
/usr/sbin/addsudo /sbin/shutdown clearos-devel
/usr/sbin/addsudo /sbin/service clearos-devel
/usr/sbin/addsudo /usr/bin/api clearos-devel
/usr/sbin/addsudo /usr/bin/file clearos-devel
/usr/sbin/addsudo /usr/bin/find clearos-devel
/usr/sbin/addsudo /usr/bin/head clearos-devel
/usr/sbin/addsudo /usr/bin/chfn clearos-devel
/usr/sbin/addsudo /usr/bin/du clearos-devel
/usr/sbin/addsudo /usr/sbin/app-passwd clearos-devel
/usr/sbin/addsudo /usr/sbin/app-realpath clearos-devel
/usr/sbin/addsudo /usr/sbin/app-rename clearos-devel
/usr/sbin/addsudo /usr/sbin/userdel clearos-devel

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
