
Name: app-print-server-plugin
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Print Server Administrator Accounts - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Source: app-print-server-plugin-%{version}.tar.gz
Buildarch: noarch

%description
Provides Print Server Adminstrator option in the User Manager.

%package core
Summary: Print Server Administrator Accounts - APIs and install
Requires: app-base-core
Requires: app-accounts-core

%description core
Provides Print Server Adminstrator option in the User Manager.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/print_server_plugin
cp -r * %{buildroot}/usr/clearos/apps/print_server_plugin/

install -D -m 0644 packaging/print_server.php %{buildroot}/var/clearos/accounts/plugins/print_server.php

%post core
logger -p local6.notice -t installer 'app-print-server-plugin-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/print_server_plugin/deploy/install ] && /usr/clearos/apps/print_server_plugin/deploy/install
fi

[ -x /usr/clearos/apps/print_server_plugin/deploy/upgrade ] && /usr/clearos/apps/print_server_plugin/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-print-server-plugin-core - uninstalling'
    [ -x /usr/clearos/apps/print_server_plugin/deploy/uninstall ] && /usr/clearos/apps/print_server_plugin/deploy/uninstall
fi

exit 0

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/print_server_plugin/packaging
%exclude /usr/clearos/apps/print_server_plugin/tests
%dir /usr/clearos/apps/print_server_plugin
/usr/clearos/apps/print_server_plugin/deploy
/usr/clearos/apps/print_server_plugin/language
/usr/clearos/apps/print_server_plugin/libraries
/var/clearos/accounts/plugins/print_server.php
