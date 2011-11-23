
Name: app-openldap-directory
Group: ClearOS/Apps
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: Directory Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-users
Requires: app-groups

%description
The directory server is an implementation (using OpenLDAP) of the Lightweight Directory Access Protocol.  The directory contains information on user records, computers, access controls etc. that can be accessed by services running on the server in addition to other systems access the directory remotely across a network.

%package core
Summary: Directory Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Provides: system-accounts
Provides: system-accounts-driver
Provides: system-groups-driver
Provides: system-users-driver
Requires: app-base-core
Requires: app-accounts-core
Requires: app-groups-core
Requires: app-ldap-core
Requires: app-network-core
Requires: app-openldap-core
Requires: app-users-core
Requires: authconfig
Requires: nss-pam-ldapd
Requires: nscd
Requires: openldap >= 2.4.19
Requires: openldap-clients >= 2.4.19
Requires: openldap-servers >= 2.4.19
Requires: pam_ldap
Requires: webconfig-php-ldap

%description core
The directory server is an implementation (using OpenLDAP) of the Lightweight Directory Access Protocol.  The directory contains information on user records, computers, access controls etc. that can be accessed by services running on the server in addition to other systems access the directory remotely across a network.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap_directory
cp -r * %{buildroot}/usr/clearos/apps/openldap_directory/

install -d -m 0755 %{buildroot}/var/clearos/openldap_directory
install -d -m 0755 %{buildroot}/var/clearos/openldap_directory/backup
install -d -m 0755 %{buildroot}/var/clearos/openldap_directory/extensions
install -D -m 0644 packaging/nslcd.conf %{buildroot}/var/clearos/ldap/synchronize/nslcd.conf
install -D -m 0644 packaging/openldap_directory.php %{buildroot}/var/clearos/accounts/drivers/openldap_directory.php
install -D -m 0644 packaging/pam_ldap.conf %{buildroot}/var/clearos/ldap/synchronize/pam_ldap.conf

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
%dir /var/clearos/openldap_directory
%dir /var/clearos/openldap_directory/backup
%dir /var/clearos/openldap_directory/extensions
/usr/clearos/apps/openldap_directory/deploy
/usr/clearos/apps/openldap_directory/language
/usr/clearos/apps/openldap_directory/libraries
/var/clearos/ldap/synchronize/nslcd.conf
/var/clearos/accounts/drivers/openldap_directory.php
/var/clearos/ldap/synchronize/pam_ldap.conf
