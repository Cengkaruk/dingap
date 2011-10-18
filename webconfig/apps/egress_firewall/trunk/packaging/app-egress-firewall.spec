
Name: app-egress-firewall
Group: ClearOS/Apps
Version: 5.9.9.5
Release: 1%{dist}
Summary: Egress Firewall
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
The Egress Firewall app allows you to block certain kinds of traffic from leaving your network.

%package core
Summary: Egress Firewall - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-firewall-core
Requires: app-network-core

%description core
The Egress Firewall app allows you to block certain kinds of traffic from leaving your network.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/egress_firewall
cp -r * %{buildroot}/usr/clearos/apps/egress_firewall/


%post
logger -p local6.notice -t installer 'app-egress-firewall - installing'

%post core
logger -p local6.notice -t installer 'app-egress-firewall-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/egress_firewall/deploy/install ] && /usr/clearos/apps/egress_firewall/deploy/install
fi

[ -x /usr/clearos/apps/egress_firewall/deploy/upgrade ] && /usr/clearos/apps/egress_firewall/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-egress-firewall - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-egress-firewall-core - uninstalling'
    [ -x /usr/clearos/apps/egress_firewall/deploy/uninstall ] && /usr/clearos/apps/egress_firewall/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/egress_firewall/controllers
/usr/clearos/apps/egress_firewall/htdocs
/usr/clearos/apps/egress_firewall/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/egress_firewall/packaging
%exclude /usr/clearos/apps/egress_firewall/tests
%dir /usr/clearos/apps/egress_firewall
/usr/clearos/apps/egress_firewall/deploy
/usr/clearos/apps/egress_firewall/language
/usr/clearos/apps/egress_firewall/libraries
