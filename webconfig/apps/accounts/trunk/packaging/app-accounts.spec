
Name: app-accounts
Group: ClearOS/Apps
Version: 5.9.9.2
Release: 2.2%{dist}
Summary: Accounts base engine
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-mode-core

%description
The accounts base engine provides... blah blah blah

%package core
Summary: Accounts base engine - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-mode-core
Requires: app-storage-core
Requires: system-accounts-driver

%description core
The accounts base engine provides... blah blah blah

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/accounts
cp -r * %{buildroot}/usr/clearos/apps/accounts/

install -d -m 0755 %{buildroot}/var/clearos/accounts
install -d -m 0755 %{buildroot}/var/clearos/accounts/drivers
install -d -m 0755 %{buildroot}/var/clearos/accounts/plugins
install -D -m 0755 packaging/accounts-init %{buildroot}/usr/sbin/accounts-init
install -D -m 0644 packaging/storage-home-default.conf %{buildroot}/etc/clearos/storage.d/home-default.conf
install -D -m 0644 packaging/storage-home.php %{buildroot}/var/clearos/storage/plugins/home.php

%post
logger -p local6.notice -t installer 'app-accounts - installing'

%post core
logger -p local6.notice -t installer 'app-accounts-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/accounts/deploy/install ] && /usr/clearos/apps/accounts/deploy/install
fi

[ -x /usr/clearos/apps/accounts/deploy/upgrade ] && /usr/clearos/apps/accounts/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-accounts - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-accounts-core - uninstalling'
    [ -x /usr/clearos/apps/accounts/deploy/uninstall ] && /usr/clearos/apps/accounts/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/accounts/controllers
/usr/clearos/apps/accounts/htdocs
/usr/clearos/apps/accounts/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/accounts/packaging
%exclude /usr/clearos/apps/accounts/tests
%dir /usr/clearos/apps/accounts
%dir /var/clearos/accounts
%dir /var/clearos/accounts/drivers
%dir /var/clearos/accounts/plugins
/usr/clearos/apps/accounts/deploy
/usr/clearos/apps/accounts/language
/usr/clearos/apps/accounts/libraries
/usr/sbin/accounts-init
/etc/clearos/storage.d/home-default.conf
/var/clearos/storage/plugins/home.php
