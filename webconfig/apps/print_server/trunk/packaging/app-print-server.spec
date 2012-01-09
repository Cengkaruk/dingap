
Name: app-print-server
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Advanced Print Server
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-groups
Requires: app-users
Requires: app-network

%description
Print Server description...

%package core
Summary: Advanced Print Server - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-certificate-manager-core
Requires: app-groups
Requires: app-print-server-plugin-core
Requires: app-users
Requires: cups >= 1.4.2
Requires: csplugin-filewatch

%description core
Print Server description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/print_server
cp -r * %{buildroot}/usr/clearos/apps/print_server/

install -d -m 0755 %{buildroot}/etc/clearos/print_server.d
install -d -m 0755 %{buildroot}/var/clearos/print_server
install -d -m 0755 %{buildroot}/var/clearos/print_server/backup/
install -D -m 0644 packaging/cups.php %{buildroot}/var/clearos/base/daemon/cups.php
install -D -m 0644 packaging/filewatch-print-server-configuration.conf %{buildroot}/etc/clearsync.d/filewatch-print-server-configuration.conf
install -D -m 0644 packaging/filewatch-print-server-network.conf %{buildroot}/etc/clearsync.d/filewatch-print-server-network.conf

%post
logger -p local6.notice -t installer 'app-print-server - installing'

%post core
logger -p local6.notice -t installer 'app-print-server-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/print_server/deploy/install ] && /usr/clearos/apps/print_server/deploy/install
fi

[ -x /usr/clearos/apps/print_server/deploy/upgrade ] && /usr/clearos/apps/print_server/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-print-server - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-print-server-core - uninstalling'
    [ -x /usr/clearos/apps/print_server/deploy/uninstall ] && /usr/clearos/apps/print_server/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/print_server/controllers
/usr/clearos/apps/print_server/htdocs
/usr/clearos/apps/print_server/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/print_server/packaging
%exclude /usr/clearos/apps/print_server/tests
%dir /usr/clearos/apps/print_server
%dir /etc/clearos/print_server.d
%dir /var/clearos/print_server
%dir /var/clearos/print_server/backup/
/usr/clearos/apps/print_server/deploy
/usr/clearos/apps/print_server/language
/usr/clearos/apps/print_server/libraries
/var/clearos/base/daemon/cups.php
/etc/clearsync.d/filewatch-print-server-configuration.conf
/etc/clearsync.d/filewatch-print-server-network.conf
