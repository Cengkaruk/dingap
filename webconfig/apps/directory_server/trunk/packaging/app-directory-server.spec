
Name: app-directory-server
Group: ClearOS/Apps
Version: 5.9.9.4
Release: 2%{dist}
Summary: Directory Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Directory server... blah blah.

%package core
Summary: Directory Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Provides: system-accounts
Requires: app-base-core
Requires: app-accounts-core
Requires: app-groups-core
Requires: app-network-core
Requires: app-samba-core
Requires: app-users-core
Requires: nss-pam-ldapd
Requires: nscd
Requires: openldap >= 2.4.19
Requires: openldap-clients >= 2.4.19
Requires: openldap-servers >= 2.4.19
Requires: pam_ldap
Requires: samba-winbind-clients
Requires: webconfig-php-ldap

%description core
Directory server... blah blah.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/directory_server
cp -r * %{buildroot}/usr/clearos/apps/directory_server/


%post
logger -p local6.notice -t installer 'app-directory-server - installing'

%post core
logger -p local6.notice -t installer 'app-directory-server-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/directory_server/deploy/install ] && /usr/clearos/apps/directory_server/deploy/install
fi

[ -x /usr/clearos/apps/directory_server/deploy/upgrade ] && /usr/clearos/apps/directory_server/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-directory-server - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-directory-server-core - uninstalling'
    [ -x /usr/clearos/apps/directory_server/deploy/uninstall ] && /usr/clearos/apps/directory_server/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/directory_server/controllers
/usr/clearos/apps/directory_server/htdocs
/usr/clearos/apps/directory_server/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/directory_server/packaging
%exclude /usr/clearos/apps/directory_server/tests
%dir /usr/clearos/apps/directory_server
/usr/clearos/apps/directory_server/deploy
/usr/clearos/apps/directory_server/language
/usr/clearos/apps/directory_server/libraries
