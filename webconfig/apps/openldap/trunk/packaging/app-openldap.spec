
Name: app-openldap-core
Group: ClearOS/Libraries
Version: 5.9.9.0
Release: 1%{dist}
Summary: LDAP Manager..... - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-openldap-%{version}.tar.gz
Buildarch: noarch
Provides: system-ldap-driver
Requires: app-base-core

%description
LDAP Manager...blah blah blah

This package provides the core API and libraries.

%prep
%setup -q -n app-openldap-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap
cp -r * %{buildroot}/usr/clearos/apps/openldap/

install -d -m 0755 %{buildroot}/var/clearos/openldap
install -d -m 0755 %{buildroot}/var/clearos/openldap/provision
install -d -m 0755 %{buildroot}/var/clearos/openldap/synchronize

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
%dir /var/clearos/openldap/provision
%dir /var/clearos/openldap/synchronize
/usr/clearos/apps/openldap/deploy
/usr/clearos/apps/openldap/language
/usr/clearos/apps/openldap/libraries
