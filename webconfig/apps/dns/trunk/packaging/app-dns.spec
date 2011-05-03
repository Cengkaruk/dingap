
Name: app-dns
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: DNS Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network
Requires: dnsmasq >= 2.48
Requires: net-tools >= 1.60

%description
The local DNS server can be used for mapping IP addresses on your network to hostnames.

%package core
Summary: DNS Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
The local DNS server can be used for mapping IP addresses on your network to hostnames.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/dns
cp -r * %{buildroot}/usr/clearos/apps/dns/


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
