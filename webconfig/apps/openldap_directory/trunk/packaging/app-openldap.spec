
Name: app-openldap
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1
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
Requires: app-base-core
Requires: app-cron-core
Requires: app-groups-core
Requires: app-network-core
Requires: app-samba-core
Requires: app-users-core
Requires: openldap >= 2.4.19
Requires: openldap-clients >= 2.4.19
Requires: openldap-servers >= 2.4.19
Requires: webconfig-php-ldap

%description core
OpenLDAP directory driver... blah blah.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openldap
cp -r * %{buildroot}/usr/clearos/apps/openldap/


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
/usr/clearos/apps/openldap/deploy
/usr/clearos/apps/openldap/language
/usr/clearos/apps/openldap/libraries
