
Name: app-bandwidth
Group: ClearOS/Apps
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Bandwidth Manager
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
Bandwidth Manager is an essential tool for administrators who wish to implement Quality of Service for services such as browsing, VoIP and SSH so that no one individual or application can adversely affect the performance of the entire network.

%package core
Summary: Bandwidth Manager - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: app-firewall-core

%description core
Bandwidth Manager is an essential tool for administrators who wish to implement Quality of Service for services such as browsing, VoIP and SSH so that no one individual or application can adversely affect the performance of the entire network.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/bandwidth
cp -r * %{buildroot}/usr/clearos/apps/bandwidth/

install -d -m 0755 %{buildroot}/var/clearos/bandwidth
install -d -m 0755 %{buildroot}/var/clearos/bandwidth/backup/
install -D -m 0644 packaging/bandwidth.conf %{buildroot}/etc/clearos/bandwidth.conf

%post
logger -p local6.notice -t installer 'app-bandwidth - installing'

%post core
logger -p local6.notice -t installer 'app-bandwidth-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/bandwidth/deploy/install ] && /usr/clearos/apps/bandwidth/deploy/install
fi

[ -x /usr/clearos/apps/bandwidth/deploy/upgrade ] && /usr/clearos/apps/bandwidth/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-bandwidth - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-bandwidth-core - uninstalling'
    [ -x /usr/clearos/apps/bandwidth/deploy/uninstall ] && /usr/clearos/apps/bandwidth/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/bandwidth/controllers
/usr/clearos/apps/bandwidth/htdocs
/usr/clearos/apps/bandwidth/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/bandwidth/packaging
%exclude /usr/clearos/apps/bandwidth/tests
%dir /usr/clearos/apps/bandwidth
%dir /var/clearos/bandwidth
%dir /var/clearos/bandwidth/backup/
/usr/clearos/apps/bandwidth/deploy
/usr/clearos/apps/bandwidth/language
/usr/clearos/apps/bandwidth/libraries
/etc/clearos/bandwidth.conf
