
Name: app-network-visualiser
Group: ClearOS/Apps
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: Network Visualiser
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Captures and displays data flow traversing your network in real-time, displaying source, destination and protocol information in addition to either bandwidth usage or total data captured over an interval.

%package core
Summary: Network Visualiser - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: jnettop

%description core
Captures and displays data flow traversing your network in real-time, displaying source, destination and protocol information in addition to either bandwidth usage or total data captured over an interval.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/network_visualiser
cp -r * %{buildroot}/usr/clearos/apps/network_visualiser/

install -D -m 0755 packaging/jnettop.conf %{buildroot}/etc/jnettop.conf

%post
logger -p local6.notice -t installer 'app-network-visualiser - installing'

%post core
logger -p local6.notice -t installer 'app-network-visualiser-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/network_visualiser/deploy/install ] && /usr/clearos/apps/network_visualiser/deploy/install
fi

[ -x /usr/clearos/apps/network_visualiser/deploy/upgrade ] && /usr/clearos/apps/network_visualiser/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-network-visualiser - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-network-visualiser-core - uninstalling'
    [ -x /usr/clearos/apps/network_visualiser/deploy/uninstall ] && /usr/clearos/apps/network_visualiser/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/network_visualiser/controllers
/usr/clearos/apps/network_visualiser/htdocs
/usr/clearos/apps/network_visualiser/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/network_visualiser/packaging
%exclude /usr/clearos/apps/network_visualiser/tests
%dir /usr/clearos/apps/network_visualiser
/usr/clearos/apps/network_visualiser/deploy
/usr/clearos/apps/network_visualiser/language
/usr/clearos/apps/network_visualiser/libraries
%config(noreplace) /etc/jnettop.conf
