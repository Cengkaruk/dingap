
Name: app-openldap
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: OpenLDAP Directory
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
OpenLDAP Directory...blah blah blah

%package core
Summary: OpenLDAP Directory - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Provides: system-directory-driver
Requires: app-base-core

%description core
OpenLDAP Directory...blah blah blah

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap
cp -r * %{buildroot}/usr/clearos/apps/openldap/

install -d -m 0755 %{buildroot}/var/clearos/openldap
install -d -m 0755 %{buildroot}/var/clearos/openldap/provision
install -d -m 0755 %{buildroot}/var/clearos/openldap/synchronize
install -D -m 0644 packaging/schema/RADIUS-LDAPv3.schema %{buildroot}/etc/openldap/schema/RADIUS-LDAPv3.schema
install -D -m 0644 packaging/schema/clearcenter.schema %{buildroot}/etc/openldap/schema/clearcenter.schema
install -D -m 0644 packaging/schema/clearfoundation.schema %{buildroot}/etc/openldap/schema/clearfoundation.schema
install -D -m 0644 packaging/schema/horde.schema %{buildroot}/etc/openldap/schema/horde.schema
install -D -m 0644 packaging/schema/kolab2.schema %{buildroot}/etc/openldap/schema/kolab2.schema
install -D -m 0644 packaging/schema/pcn.schema %{buildroot}/etc/openldap/schema/pcn.schema
install -D -m 0644 packaging/schema/rfc2307bis.schema %{buildroot}/etc/openldap/schema/rfc2307bis.schema
install -D -m 0644 packaging/schema/rfc2739.schema %{buildroot}/etc/openldap/schema/rfc2739.schema
install -D -m 0644 packaging/schema/samba.schema %{buildroot}/etc/openldap/schema/samba.schema
install -D -m 0644 packaging/schema/zarafa.schema %{buildroot}/etc/openldap/schema/zarafa.schema

%post
logger -p local6.notice -t installer 'app-openldap - installing'

%post core
logger -p local6.notice -t installer 'app-openldap-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/openldap/deploy/install ] && /usr/clearos/apps/openldap/deploy/install
fi

[ -x /usr/clearos/apps/openldap/deploy/upgrade ] && /usr/clearos/apps/openldap/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openldap-core - uninstalling'
    [ -x /usr/clearos/apps/openldap/deploy/uninstall ] && /usr/clearos/apps/openldap/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/openldap/controllers
/usr/clearos/apps/openldap/htdocs
/usr/clearos/apps/openldap/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/openldap/packaging
%exclude /usr/clearos/apps/openldap/tests
%dir /usr/clearos/apps/openldap
%dir /var/clearos/openldap
%dir /var/clearos/openldap/provision
%dir /var/clearos/openldap/synchronize
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
/etc/openldap/schema/samba.schema
/etc/openldap/schema/zarafa.schema
