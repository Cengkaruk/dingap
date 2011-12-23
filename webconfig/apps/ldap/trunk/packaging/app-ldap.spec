
Name: app-ldap
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: LDAP Engine - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Source: app-ldap-%{version}.tar.gz
Buildarch: noarch

%description
The LDAP Engine provides a common framework for all the available LDAP implementations including OpenLDAP and Active Directory.

%package core
Summary: LDAP Engine - APIs and install
Requires: app-base-core
Requires: app-mode-core
Requires: openssl
Requires: system-ldap-driver

%description core
The LDAP Engine provides a common framework for all the available LDAP implementations including OpenLDAP and Active Directory.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/ldap
cp -r * %{buildroot}/usr/clearos/apps/ldap/

install -d -m 0755 %{buildroot}/var/clearos/ldap
install -d -m 0755 %{buildroot}/var/clearos/ldap/synchronize
install -D -m 0755 packaging/ldap-init %{buildroot}/usr/sbin/ldap-init
install -D -m 0755 packaging/ldap-synchronize %{buildroot}/usr/sbin/ldap-synchronize
install -D -m 0755 packaging/poststart-ldap %{buildroot}/usr/sbin/poststart-ldap
install -D -m 0755 packaging/prestart-ldap %{buildroot}/usr/sbin/prestart-ldap

%post core
logger -p local6.notice -t installer 'app-ldap-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/ldap/deploy/install ] && /usr/clearos/apps/ldap/deploy/install
fi

[ -x /usr/clearos/apps/ldap/deploy/upgrade ] && /usr/clearos/apps/ldap/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ldap-core - uninstalling'
    [ -x /usr/clearos/apps/ldap/deploy/uninstall ] && /usr/clearos/apps/ldap/deploy/uninstall
fi

exit 0

%files core
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
/usr/sbin/ldap-synchronize
/usr/sbin/poststart-ldap
/usr/sbin/prestart-ldap
