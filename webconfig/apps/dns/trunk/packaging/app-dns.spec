
Name: app-dns
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: DNS Server
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
The local DNS server can be used for mapping IP addresses on your network to hostnames.

%package core
Summary: DNS Server - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: dnsmasq >= 2.48
Requires: net-tools

%description core
The local DNS server can be used for mapping IP addresses on your network to hostnames.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/dns
cp -r * %{buildroot}/usr/clearos/apps/dns/

install -D -m 0644 packaging/dnsmasq.php %{buildroot}/var/clearos/base/daemon/dnsmasq.php

%post
logger -p local6.notice -t installer 'app-dns - installing'

%post core
logger -p local6.notice -t installer 'app-dns-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/dns/deploy/install ] && /usr/clearos/apps/dns/deploy/install
fi

[ -x /usr/clearos/apps/dns/deploy/upgrade ] && /usr/clearos/apps/dns/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-dns - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-dns-core - uninstalling'
    [ -x /usr/clearos/apps/dns/deploy/uninstall ] && /usr/clearos/apps/dns/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/dns/controllers
/usr/clearos/apps/dns/htdocs
/usr/clearos/apps/dns/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/dns/packaging
%exclude /usr/clearos/apps/dns/tests
%dir /usr/clearos/apps/dns
/usr/clearos/apps/dns/deploy
/usr/clearos/apps/dns/language
/usr/clearos/apps/dns/libraries
/var/clearos/base/daemon/dnsmasq.php
