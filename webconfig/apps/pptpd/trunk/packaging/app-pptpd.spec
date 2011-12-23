
Name: app-pptpd
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: PPTP Server
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-incoming-firewall
Requires: app-groups
Requires: app-users
Requires: app-network

%description
PPTP Server description... wordsmith please.

%package core
Summary: PPTP Server - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-network-core
Requires: app-pptpd-plugin-core
Requires: app-samba-extension-core
Requires: app-incoming-firewall-core
Requires: csplugin-routewatch
Requires: pptpd >= 1.3.4
Requires: samba-winbind

%description core
PPTP Server description... wordsmith please.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/pptpd
cp -r * %{buildroot}/usr/clearos/apps/pptpd/

install -d -m 0755 %{buildroot}/etc/clearos/pptpd.d
install -d -m 0755 %{buildroot}/var/clearos/pptpd
install -d -m 0755 %{buildroot}/var/clearos/pptpd/backup
install -D -m 0644 packaging/authorize %{buildroot}/etc/clearos/pptpd.d/authorize
install -D -m 0644 packaging/pptpd.php %{buildroot}/var/clearos/base/daemon/pptpd.php

%post
logger -p local6.notice -t installer 'app-pptpd - installing'

%post core
logger -p local6.notice -t installer 'app-pptpd-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/pptpd/deploy/install ] && /usr/clearos/apps/pptpd/deploy/install
fi

[ -x /usr/clearos/apps/pptpd/deploy/upgrade ] && /usr/clearos/apps/pptpd/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-pptpd - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-pptpd-core - uninstalling'
    [ -x /usr/clearos/apps/pptpd/deploy/uninstall ] && /usr/clearos/apps/pptpd/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/pptpd/controllers
/usr/clearos/apps/pptpd/htdocs
/usr/clearos/apps/pptpd/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/pptpd/packaging
%exclude /usr/clearos/apps/pptpd/tests
%dir /usr/clearos/apps/pptpd
%dir /etc/clearos/pptpd.d
%dir /var/clearos/pptpd
%dir /var/clearos/pptpd/backup
/usr/clearos/apps/pptpd/deploy
/usr/clearos/apps/pptpd/language
/usr/clearos/apps/pptpd/libraries
%config(noreplace) /etc/clearos/pptpd.d/authorize
/var/clearos/base/daemon/pptpd.php
