
Name: app-nat-firewall
Group: ClearOS/Apps
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: 1-to-1 NAT
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
1-to-1 NAT maps a public IP address to a private IP address allowing access to systems behind the firewall via a public IP address.  This feature requires additional public IP addresses from your Internet Service Provider (ISP).

%package core
Summary: 1-to-1 NAT - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-firewall-core
Requires: app-network-core

%description core
1-to-1 NAT maps a public IP address to a private IP address allowing access to systems behind the firewall via a public IP address.  This feature requires additional public IP addresses from your Internet Service Provider (ISP).

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/nat_firewall
cp -r * %{buildroot}/usr/clearos/apps/nat_firewall/


%post
logger -p local6.notice -t installer 'app-nat-firewall - installing'

%post core
logger -p local6.notice -t installer 'app-nat-firewall-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/nat_firewall/deploy/install ] && /usr/clearos/apps/nat_firewall/deploy/install
fi

[ -x /usr/clearos/apps/nat_firewall/deploy/upgrade ] && /usr/clearos/apps/nat_firewall/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-nat-firewall - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-nat-firewall-core - uninstalling'
    [ -x /usr/clearos/apps/nat_firewall/deploy/uninstall ] && /usr/clearos/apps/nat_firewall/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/nat_firewall/controllers
/usr/clearos/apps/nat_firewall/htdocs
/usr/clearos/apps/nat_firewall/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/nat_firewall/packaging
%exclude /usr/clearos/apps/nat_firewall/tests
%dir /usr/clearos/apps/nat_firewall
/usr/clearos/apps/nat_firewall/deploy
/usr/clearos/apps/nat_firewall/language
/usr/clearos/apps/nat_firewall/libraries
