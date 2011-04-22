
Name: app-openldap-accounts
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: OpenLDAP Directory Driver
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
OpenLDAP directory driver... blah blah.

%package core
Summary: OpenLDAP Directory Driver - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Provides: system-accounts
Requires: app-base-core
Requires: app-accounts-core
Requires: app-groups-core
Requires: app-network-core
Requires: app-samba-core
Requires: app-users-core
Requires: nss-pam-ldapd
Requires: nscd
Requires: openldap >= 2.4.19
Requires: openldap-clients >= 2.4.19
Requires: openldap-servers >= 2.4.19
Requires: pam_ldap
Requires: samba-winbind-clients
Requires: webconfig-php-ldap

%description core
OpenLDAP directory driver... blah blah.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap_accounts
cp -r * %{buildroot}/usr/clearos/apps/openldap_accounts/

install -D -m 0644 packaging/openldap.php %{buildroot}/var/clearos/accounts/drivers/openldap.php

%post
logger -p local6.notice -t installer 'app-openldap-accounts - installing'

%post core
logger -p local6.notice -t installer 'app-openldap-accounts-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/openldap_accounts/deploy/install ] && /usr/clearos/apps/openldap_accounts/deploy/install
fi

[ -x /usr/clearos/apps/openldap_accounts/deploy/upgrade ] && /usr/clearos/apps/openldap_accounts/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap-accounts - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap-accounts-core - uninstalling'
    [ -x /usr/clearos/apps/openldap_accounts/deploy/uninstall ] && /usr/clearos/apps/openldap_accounts/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/openldap_accounts/controllers
/usr/clearos/apps/openldap_accounts/htdocs
/usr/clearos/apps/openldap_accounts/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/openldap_accounts/packaging
%exclude /usr/clearos/apps/openldap_accounts/tests
%dir /usr/clearos/apps/openldap_accounts
/usr/clearos/apps/openldap_accounts/deploy
/usr/clearos/apps/openldap_accounts/language
/usr/clearos/apps/openldap_accounts/libraries
/var/clearos/accounts/drivers/openldap.php
