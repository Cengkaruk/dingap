
Name: app-print-server
Group: ClearOS/Apps
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: Print Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
Print Server description...

%package core
Summary: Print Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-certificate-manager-core
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
install -D -m 0644 packaging/filewatch-print-server.conf %{buildroot}/etc/clearsync.d/filewatch-print-server.conf

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
/etc/clearsync.d/filewatch-print-server.conf
