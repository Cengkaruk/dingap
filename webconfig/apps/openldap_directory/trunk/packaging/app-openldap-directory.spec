
Name: app-openldap-directory-core
Group: ClearOS/Libraries
Version: 5.9.9.3
Release: 2.1%{dist}
Summary: OpenLDAP Directory - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-openldap-directory-%{version}.tar.gz
Buildarch: noarch
Provides: system-accounts-driver
Provides: system-groups-driver
Provides: system-users-driver
Requires: app-base-core
Requires: app-accounts-core
Requires: app-groups-core
Requires: app-ldap-core
Requires: app-network-core
Requires: app-openldap-core
Requires: app-samba-core
Requires: app-users-core
Requires: authconfig
Requires: nss-pam-ldapd
Requires: nscd
Requires: openldap >= 2.4.19
Requires: openldap-clients >= 2.4.19
Requires: openldap-servers >= 2.4.19
Requires: pam_ldap
Requires: samba-winbind-clients
Requires: webconfig-php-ldap

%description
OpenLDAP Directory description...

This package provides the core API and libraries.

%prep
%setup -q -n app-openldap-directory-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap_directory
cp -r * %{buildroot}/usr/clearos/apps/openldap_directory/

install -d -m 0755 %{buildroot}/var/clearos/openldap_directory
install -d -m 0755 %{buildroot}/var/clearos/openldap_directory/extensions
install -D -m 0644 packaging/nslcd.conf %{buildroot}/var/clearos/ldap/synchronize/nslcd.conf
install -D -m 0644 packaging/openldap_directory.php %{buildroot}/var/clearos/accounts/drivers/openldap_directory.php
install -D -m 0644 packaging/pam_ldap.conf %{buildroot}/var/clearos/ldap/synchronize/pam_ldap.conf

%post
logger -p local6.notice -t installer 'app-openldap-directory-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/openldap_directory/deploy/install ] && /usr/clearos/apps/openldap_directory/deploy/install
fi

[ -x /usr/clearos/apps/openldap_directory/deploy/upgrade ] && /usr/clearos/apps/openldap_directory/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap-directory-core - uninstalling'
    [ -x /usr/clearos/apps/openldap_directory/deploy/uninstall ] && /usr/clearos/apps/openldap_directory/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/openldap_directory/packaging
%exclude /usr/clearos/apps/openldap_directory/tests
%dir /usr/clearos/apps/openldap_directory
%dir /var/clearos/openldap_directory
%dir /var/clearos/openldap_directory/extensions
/usr/clearos/apps/openldap_directory/deploy
/usr/clearos/apps/openldap_directory/language
/usr/clearos/apps/openldap_directory/libraries
/var/clearos/ldap/synchronize/nslcd.conf
/var/clearos/accounts/drivers/openldap_directory.php
/var/clearos/ldap/synchronize/pam_ldap.conf
