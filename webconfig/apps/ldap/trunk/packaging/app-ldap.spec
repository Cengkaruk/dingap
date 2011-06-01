
Name: app-ldap-core
Group: ClearOS/Libraries
Version: 5.9.9.2
Release: 1%{dist}
Summary: LDAP Manager - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-ldap-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: app-mode-core
Requires: openssl
Requires: system-ldap-driver

%description
The LDAP mode manager... master/slave/standalone.

This package provides the core API and libraries.

%prep
%setup -q -n app-ldap-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/ldap
cp -r * %{buildroot}/usr/clearos/apps/ldap/

install -d -m 0755 %{buildroot}/var/clearos/ldap
install -d -m 0755 %{buildroot}/var/clearos/ldap/synchronize
install -D -m 0755 packaging/ldap-init %{buildroot}/usr/sbin/ldap-init
install -D -m 0755 packaging/ldap-manager %{buildroot}/usr/sbin/ldap-manager
install -D -m 0755 packaging/ldap-synchronize %{buildroot}/usr/sbin/ldap-synchronize

%post
logger -p local6.notice -t installer 'app-ldap-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/ldap/deploy/install ] && /usr/clearos/apps/ldap/deploy/install
fi

[ -x /usr/clearos/apps/ldap/deploy/upgrade ] && /usr/clearos/apps/ldap/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ldap-core - uninstalling'
    [ -x /usr/clearos/apps/ldap/deploy/uninstall ] && /usr/clearos/apps/ldap/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/ldap/packaging
%exclude /usr/clearos/apps/ldap/tests
%dir /usr/clearos/apps/ldap
%dir /var/clearos/ldap
%dir /var/clearos/ldap/synchronize
/usr/clearos/apps/ldap/deploy
/usr/clearos/apps/ldap/language
/usr/clearos/apps/ldap/libraries
/usr/sbin/ldap-init
/usr/sbin/ldap-manager
/usr/sbin/ldap-synchronize
