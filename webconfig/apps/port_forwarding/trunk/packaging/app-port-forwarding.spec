
Name: app-port-forwarding
Group: ClearOS/Apps
Version: 5.9.9.4
Release: 1.1%{dist}
Summary: Port Forwarding
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
This is the description for port forwarding...

%package core
Summary: Port Forwarding - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-firewall-core
Requires: app-network-core

%description core
This is the description for port forwarding...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/port_forwarding
cp -r * %{buildroot}/usr/clearos/apps/port_forwarding/


%post
logger -p local6.notice -t installer 'app-port-forwarding - installing'

%post core
logger -p local6.notice -t installer 'app-port-forwarding-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/port_forwarding/deploy/install ] && /usr/clearos/apps/port_forwarding/deploy/install
fi

[ -x /usr/clearos/apps/port_forwarding/deploy/upgrade ] && /usr/clearos/apps/port_forwarding/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-port-forwarding - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-port-forwarding-core - uninstalling'
    [ -x /usr/clearos/apps/port_forwarding/deploy/uninstall ] && /usr/clearos/apps/port_forwarding/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/port_forwarding/controllers
/usr/clearos/apps/port_forwarding/htdocs
/usr/clearos/apps/port_forwarding/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/port_forwarding/packaging
%exclude /usr/clearos/apps/port_forwarding/tests
%dir /usr/clearos/apps/port_forwarding
/usr/clearos/apps/port_forwarding/deploy
/usr/clearos/apps/port_forwarding/language
/usr/clearos/apps/port_forwarding/libraries
