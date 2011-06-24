
Name: app-network
Group: ClearOS/Apps
Version: 5.9.9.2
Release: 3.1%{dist}
Summary: Network configuration tool
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Network description... blah blah

%package core
Summary: Network configuration tool - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: avahi
Requires: bind-utils
Requires: dhclient
Requires: ethtool
Requires: net-tools
Requires: ppp
Requires: rp-pppoe

%description core
Network description... blah blah

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/network
cp -r * %{buildroot}/usr/clearos/apps/network/

install -d -m 0755 %{buildroot}/var/clearos/network
install -d -m 0755 %{buildroot}/var/clearos/network/backup
install -D -m 0644 packaging/dhclient-exit-hooks %{buildroot}/etc/dhclient-exit-hooks
install -D -m 0644 packaging/network.conf %{buildroot}/etc/clearos/network.conf

%post
logger -p local6.notice -t installer 'app-network - installing'

%post core
logger -p local6.notice -t installer 'app-network-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/network/deploy/install ] && /usr/clearos/apps/network/deploy/install
fi

[ -x /usr/clearos/apps/network/deploy/upgrade ] && /usr/clearos/apps/network/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-network - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-network-core - uninstalling'
    [ -x /usr/clearos/apps/network/deploy/uninstall ] && /usr/clearos/apps/network/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/network/controllers
/usr/clearos/apps/network/htdocs
/usr/clearos/apps/network/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/network/packaging
%exclude /usr/clearos/apps/network/tests
%dir /usr/clearos/apps/network
%dir /var/clearos/network
%dir /var/clearos/network/backup
/usr/clearos/apps/network/deploy
/usr/clearos/apps/network/language
/usr/clearos/apps/network/libraries
/etc/dhclient-exit-hooks
%config(noreplace) /etc/clearos/network.conf
