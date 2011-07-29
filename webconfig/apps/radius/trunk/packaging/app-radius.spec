
Name: app-radius
Group: ClearOS/Apps
Version: 5.9.9.3
Release: 2.1%{dist}
Summary: RADIUS Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-groups
Requires: app-users
Requires: app-network

%description
RADIUS provides additional authentication mechanisms for the system.

%package core
Summary: RADIUS Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: app-openldap-directory-core
Requires: app-samba-extension-core
Requires: freeradius
Requires: freeradius-ldap
Requires: freeradius-utils

%description core
RADIUS provides additional authentication mechanisms for the system.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/radius
cp -r * %{buildroot}/usr/clearos/apps/radius/

install -d -m 0755 %{buildroot}/etc/raddb/clearos-certs
install -D -m 0640 packaging/clearos-clients.conf %{buildroot}/etc/raddb/clearos-clients.conf
install -D -m 0640 packaging/clearos-eap.conf %{buildroot}/etc/raddb/clearos-eap.conf
install -D -m 0640 packaging/clearos-inner-tunnel %{buildroot}/etc/raddb/sites-available/clearos-inner-tunnel
install -D -m 0640 packaging/clearos-users %{buildroot}/etc/raddb/clearos-users

%post
logger -p local6.notice -t installer 'app-radius - installing'

%post core
logger -p local6.notice -t installer 'app-radius-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/radius/deploy/install ] && /usr/clearos/apps/radius/deploy/install
fi

[ -x /usr/clearos/apps/radius/deploy/upgrade ] && /usr/clearos/apps/radius/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-radius - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-radius-core - uninstalling'
    [ -x /usr/clearos/apps/radius/deploy/uninstall ] && /usr/clearos/apps/radius/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/radius/controllers
/usr/clearos/apps/radius/htdocs
/usr/clearos/apps/radius/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/radius/packaging
%exclude /usr/clearos/apps/radius/tests
%dir /usr/clearos/apps/radius
%dir /etc/raddb/clearos-certs
/usr/clearos/apps/radius/deploy
/usr/clearos/apps/radius/language
/usr/clearos/apps/radius/libraries
%attr(0640,root,radiusd) /etc/raddb/clearos-clients.conf
%attr(0640,root,radiusd) /etc/raddb/clearos-eap.conf
%attr(0640,root,radiusd) /etc/raddb/sites-available/clearos-inner-tunnel
%attr(0640,root,radiusd) /etc/raddb/clearos-users
