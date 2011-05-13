
Name: app-openldap-directory
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
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap_directory
cp -r * %{buildroot}/usr/clearos/apps/openldap_directory/

install -D -m 0644 packaging/openldap_directory.php %{buildroot}/var/clearos/accounts/drivers/openldap_directory.php

%post
logger -p local6.notice -t installer 'app-openldap-directory - installing'

%post core
logger -p local6.notice -t installer 'app-openldap-directory-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/openldap_directory/deploy/install ] && /usr/clearos/apps/openldap_directory/deploy/install
fi

[ -x /usr/clearos/apps/openldap_directory/deploy/upgrade ] && /usr/clearos/apps/openldap_directory/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap-directory - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap-directory-core - uninstalling'
    [ -x /usr/clearos/apps/openldap_directory/deploy/uninstall ] && /usr/clearos/apps/openldap_directory/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/openldap_directory/controllers
/usr/clearos/apps/openldap_directory/htdocs
/usr/clearos/apps/openldap_directory/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/openldap_directory/packaging
%exclude /usr/clearos/apps/openldap_directory/tests
%dir /usr/clearos/apps/openldap_directory
/usr/clearos/apps/openldap_directory/deploy
/usr/clearos/apps/openldap_directory/language
/usr/clearos/apps/openldap_directory/libraries
/var/clearos/accounts/drivers/openldap_directory.php
