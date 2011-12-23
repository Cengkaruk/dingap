
Name: app-ntp
Group: ClearOS/Apps
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: NTP Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
NTP Server description...

%package core
Summary: NTP Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: ntp >= 4.2.4

%description core
NTP Server description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/ntp
cp -r * %{buildroot}/usr/clearos/apps/ntp/

install -d -m 0755 %{buildroot}/var/clearos/ntp
install -D -m 0644 packaging/ntpd.php %{buildroot}/var/clearos/base/daemon/ntpd.php

%post
logger -p local6.notice -t installer 'app-ntp - installing'

%post core
logger -p local6.notice -t installer 'app-ntp-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/ntp/deploy/install ] && /usr/clearos/apps/ntp/deploy/install
fi

[ -x /usr/clearos/apps/ntp/deploy/upgrade ] && /usr/clearos/apps/ntp/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ntp - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ntp-core - uninstalling'
    [ -x /usr/clearos/apps/ntp/deploy/uninstall ] && /usr/clearos/apps/ntp/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/ntp/controllers
/usr/clearos/apps/ntp/htdocs
/usr/clearos/apps/ntp/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/ntp/packaging
%exclude /usr/clearos/apps/ntp/tests
%dir /usr/clearos/apps/ntp
%dir /var/clearos/ntp
/usr/clearos/apps/ntp/deploy
/usr/clearos/apps/ntp/language
/usr/clearos/apps/ntp/libraries
/var/clearos/base/daemon/ntpd.php
