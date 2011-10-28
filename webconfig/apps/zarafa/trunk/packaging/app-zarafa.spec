
Name: app-zarafa
Group: ClearOS/Apps
Version: 5.9.9.3
Release: 2.1%{dist}
Summary: Zarafa Standard
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-groups
Requires: app-users
Requires: app-network
Requires: app-postfix

%description
Translation missing (zarafa_app_description)

%package core
Summary: Zarafa Standard - APIs and install
Group: ClearOS/Libraries
License: GPLv3
Requires: app-base-core
Requires: app-zarafa-extension-core
Requires: app-openldap-directory-core
Requires: system-mysql-server
Requires: zarafa
Requires: zarafa-webaccess

%description core
Translation missing (zarafa_app_description)

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/zarafa
cp -r * %{buildroot}/usr/clearos/apps/zarafa/

install -D -m 0644 packaging/zarafa-dagent.php %{buildroot}/var/clearos/base/daemon/zarafa-dagent.php
install -D -m 0644 packaging/zarafa-gateway.php %{buildroot}/var/clearos/base/daemon/zarafa-gateway.php
install -D -m 0644 packaging/zarafa-ical.php %{buildroot}/var/clearos/base/daemon/zarafa-ical.php
install -D -m 0644 packaging/zarafa-indexer.php %{buildroot}/var/clearos/base/daemon/zarafa-indexer.php
install -D -m 0644 packaging/zarafa-monitor.php %{buildroot}/var/clearos/base/daemon/zarafa-monitor.php
install -D -m 0644 packaging/zarafa-server.php %{buildroot}/var/clearos/base/daemon/zarafa-server.php
install -D -m 0644 packaging/zarafa-spooler.php %{buildroot}/var/clearos/base/daemon/zarafa-spooler.php

%post
logger -p local6.notice -t installer 'app-zarafa - installing'

%post core
logger -p local6.notice -t installer 'app-zarafa-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/zarafa/deploy/install ] && /usr/clearos/apps/zarafa/deploy/install
fi

[ -x /usr/clearos/apps/zarafa/deploy/upgrade ] && /usr/clearos/apps/zarafa/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-zarafa - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-zarafa-core - uninstalling'
    [ -x /usr/clearos/apps/zarafa/deploy/uninstall ] && /usr/clearos/apps/zarafa/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/zarafa/controllers
/usr/clearos/apps/zarafa/htdocs
/usr/clearos/apps/zarafa/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/zarafa/packaging
%exclude /usr/clearos/apps/zarafa/tests
%dir /usr/clearos/apps/zarafa
/usr/clearos/apps/zarafa/deploy
/usr/clearos/apps/zarafa/language
/usr/clearos/apps/zarafa/libraries
/var/clearos/base/daemon/zarafa-dagent.php
/var/clearos/base/daemon/zarafa-gateway.php
/var/clearos/base/daemon/zarafa-ical.php
/var/clearos/base/daemon/zarafa-indexer.php
/var/clearos/base/daemon/zarafa-monitor.php
/var/clearos/base/daemon/zarafa-server.php
/var/clearos/base/daemon/zarafa-spooler.php
