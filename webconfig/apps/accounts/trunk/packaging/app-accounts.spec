
Name: app-accounts-core
Group: ClearOS/Libraries
Version: 5.9.9.0
Release: 1%{dist}
Summary: Accounts base engine - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-accounts-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core

%description
The accounts base engine provides... blah blah blah

This package provides the core API and libraries.

%prep
%setup -q -n app-accounts-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/accounts
cp -r * %{buildroot}/usr/clearos/apps/accounts/

install -d -m 0755 %{buildroot}/var/clearos/accounts
install -d -m 0755 %{buildroot}/var/clearos/accounts/drivers
install -d -m 0755 %{buildroot}/var/clearos/accounts/plugins

%post
logger -p local6.notice -t installer 'app-accounts-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/accounts/deploy/install ] && /usr/clearos/apps/accounts/deploy/install
fi

[ -x /usr/clearos/apps/accounts/deploy/upgrade ] && /usr/clearos/apps/accounts/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-accounts-core - uninstalling'
    [ -x /usr/clearos/apps/accounts/deploy/uninstall ] && /usr/clearos/apps/accounts/deploy/uninstall
fi

exit 0

%files
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
