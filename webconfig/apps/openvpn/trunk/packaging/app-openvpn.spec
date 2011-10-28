
Name: app-openvpn
Group: ClearOS/Apps
Version: 5.9.9.1
Release: 1%{dist}
Summary: OpenVPN
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-groups
Requires: app-users
Requires: app-network

%description
OpenVPN long description...

%package core
Summary: OpenVPN - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: app-keys-extension-core
Requires: openvpn >= 2.1.4

%description core
OpenVPN long description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openvpn
cp -r * %{buildroot}/usr/clearos/apps/openvpn/

install -d -m 0755 %{buildroot}/var/clearos/openvpn
install -d -m 0755 %{buildroot}/var/clearos/openvpn/backup
install -D -m 0644 packaging/openvpn.php %{buildroot}/var/clearos/base/daemon/openvpn.php

%post
logger -p local6.notice -t installer 'app-openvpn - installing'

%post core
logger -p local6.notice -t installer 'app-openvpn-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/openvpn/deploy/install ] && /usr/clearos/apps/openvpn/deploy/install
fi

[ -x /usr/clearos/apps/openvpn/deploy/upgrade ] && /usr/clearos/apps/openvpn/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openvpn - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openvpn-core - uninstalling'
    [ -x /usr/clearos/apps/openvpn/deploy/uninstall ] && /usr/clearos/apps/openvpn/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/openvpn/controllers
/usr/clearos/apps/openvpn/htdocs
/usr/clearos/apps/openvpn/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/openvpn/packaging
%exclude /usr/clearos/apps/openvpn/tests
%dir /usr/clearos/apps/openvpn
%dir /var/clearos/openvpn
%dir /var/clearos/openvpn/backup
/usr/clearos/apps/openvpn/deploy
/usr/clearos/apps/openvpn/language
/usr/clearos/apps/openvpn/libraries
/var/clearos/base/daemon/openvpn.php
