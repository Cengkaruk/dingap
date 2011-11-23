
Name: app-openldap-core
Group: ClearOS/Libraries
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: OpenLDAP Driver - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-openldap-%{version}.tar.gz
Buildarch: noarch
Provides: system-ldap-driver
Requires: app-base-core
Requires: app-mode-core
Requires: app-network-core
Requires: openldap-servers >= 2.4.19
Requires: openssl

%description
The OpenLDAP Driver description

This package provides the core API and libraries.

%prep
%setup -q -n app-openldap-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap
cp -r * %{buildroot}/usr/clearos/apps/openldap/

install -d -m 0755 %{buildroot}/var/clearos/openldap
install -d -m 0755 %{buildroot}/var/clearos/openldap/backup
install -d -m 0755 %{buildroot}/var/clearos/openldap/provision
install -D -m 0644 packaging/schema/RADIUS-LDAPv3.schema %{buildroot}/etc/openldap/schema/RADIUS-LDAPv3.schema
install -D -m 0644 packaging/schema/clearcenter.schema %{buildroot}/etc/openldap/schema/clearcenter.schema
install -D -m 0644 packaging/schema/clearfoundation.schema %{buildroot}/etc/openldap/schema/clearfoundation.schema
install -D -m 0644 packaging/schema/horde.schema %{buildroot}/etc/openldap/schema/horde.schema
install -D -m 0644 packaging/schema/kolab2.schema %{buildroot}/etc/openldap/schema/kolab2.schema
install -D -m 0644 packaging/schema/pcn.schema %{buildroot}/etc/openldap/schema/pcn.schema
install -D -m 0644 packaging/schema/rfc2307bis.schema %{buildroot}/etc/openldap/schema/rfc2307bis.schema
install -D -m 0644 packaging/schema/rfc2739.schema %{buildroot}/etc/openldap/schema/rfc2739.schema
install -D -m 0644 packaging/schema/zarafa.schema %{buildroot}/etc/openldap/schema/zarafa.schema
install -D -m 0644 packaging/slapd.php %{buildroot}/var/clearos/base/daemon/slapd.php

%post
logger -p local6.notice -t installer 'app-openldap-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/openldap/deploy/install ] && /usr/clearos/apps/openldap/deploy/install
fi

[ -x /usr/clearos/apps/openldap/deploy/upgrade ] && /usr/clearos/apps/openldap/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap-core - uninstalling'
    [ -x /usr/clearos/apps/openldap/deploy/uninstall ] && /usr/clearos/apps/openldap/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/openldap/packaging
%exclude /usr/clearos/apps/openldap/tests
%dir /usr/clearos/apps/openldap
%dir /var/clearos/openldap
%dir /var/clearos/openldap/backup
%dir /var/clearos/openldap/provision
/usr/clearos/apps/openldap/deploy
/usr/clearos/apps/openldap/language
/usr/clearos/apps/openldap/libraries
/etc/openldap/schema/RADIUS-LDAPv3.schema
/etc/openldap/schema/clearcenter.schema
/etc/openldap/schema/clearfoundation.schema
/etc/openldap/schema/horde.schema
/etc/openldap/schema/kolab2.schema
/etc/openldap/schema/pcn.schema
/etc/openldap/schema/rfc2307bis.schema
/etc/openldap/schema/rfc2739.schema
/etc/openldap/schema/zarafa.schema
/var/clearos/base/daemon/slapd.php
