Name: syswatch
Version: 5.9.9.1
Release: 2%{dist}
Summary: Network and system monitor module
License: GPL
Group: System Environment/Daemons
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
Requires: perl
BuildArch: noarch
BuildRoot: %_tmppath/%name-%version-buildroot

%description
Network and system monitor module

%prep
%setup
%build

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

mkdir -p -m 755 $RPM_BUILD_ROOT/etc/rc.d/init.d
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/logrotate.d
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/sbin
mkdir -p -m 755 $RPM_BUILD_ROOT/var/lib/syswatch

install -m 644 syswatch.conf $RPM_BUILD_ROOT/etc/syswatch
install -m 644 syswatch.logrotate $RPM_BUILD_ROOT/etc/logrotate.d/syswatch
install -m 755 syswatch.init $RPM_BUILD_ROOT/etc/rc.d/init.d/syswatch
install -m 755 syswatch $RPM_BUILD_ROOT/usr/sbin/

%post
if ( [ $1 == 1 ] && [ ! -e /etc/system/pre5x ] ); then
	/sbin/chkconfig --add syswatch
fi

exit 0

%preun
if [ $1 = 0 ]; then
	service syswatch stop >/dev/null 2>&1
	chkconfig --del syswatch
fi

exit 0

%postun
if [ $1 == 1 ]; then
	service syswatch condrestart >/dev/null 2>&1
fi

exit 0

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
%config(noreplace) /etc/syswatch
/etc/logrotate.d/syswatch
/etc/rc.d/init.d/syswatch
/usr/sbin/syswatch
/var/lib/syswatch
