
Name: app-ldap
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: LDAP Manager
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
The LDAP mode manager... master/slave/standalone.

%package core
Summary: LDAP Manager - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-mode-core
Requires: system-ldap-driver

%description core
The LDAP mode manager... master/slave/standalone.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/ldap
cp -r * %{buildroot}/usr/clearos/apps/ldap/


%post
logger -p local6.notice -t installer 'app-ldap - installing'

%post core
logger -p local6.notice -t installer 'app-ldap-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/ldap/deploy/install ] && /usr/clearos/apps/ldap/deploy/install
fi

[ -x /usr/clearos/apps/ldap/deploy/upgrade ] && /usr/clearos/apps/ldap/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ldap - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ldap-core - uninstalling'
    [ -x /usr/clearos/apps/ldap/deploy/uninstall ] && /usr/clearos/apps/ldap/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/ldap/controllers
/usr/clearos/apps/ldap/htdocs
/usr/clearos/apps/ldap/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/ldap/packaging
%exclude /usr/clearos/apps/ldap/tests
%dir /usr/clearos/apps/ldap
/usr/clearos/apps/ldap/deploy
/usr/clearos/apps/ldap/language
/usr/clearos/apps/ldap/libraries
