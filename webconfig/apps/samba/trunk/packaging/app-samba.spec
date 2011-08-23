
Name: app-samba
Group: ClearOS/Apps
Version: 5.9.9.4
Release: 2%{dist}
Summary: Windows Settings
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
Requires: samba >= 3.5.4

%description
Translation missing (samba_app_long_description)

%package core
Summary: Windows Settings - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: app-openldap-directory-core
Requires: samba-client >= 3.5.4
Requires: samba-winbind-clients >= 3.5.4

%description core
Translation missing (samba_app_long_description)

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/samba
cp -r * %{buildroot}/usr/clearos/apps/samba/

install -d -m 0755 %{buildroot}/var/clearos/samba
install -d -m 0755 %{buildroot}/var/clearos/samba/backup
install -D -m 0755 packaging/add-samba-directories %{buildroot}/usr/sbin/add-samba-directories
install -D -m 0755 packaging/samba-init %{buildroot}/usr/sbin/samba-init
install -D -m 0644 packaging/smb.ldap.conf %{buildroot}/var/clearos/ldap/synchronize/smb.ldap.conf
install -D -m 0644 packaging/smb.winbind.conf %{buildroot}/var/clearos/ldap/synchronize/smb.winbind.conf

%post
logger -p local6.notice -t installer 'app-samba - installing'

%post core
logger -p local6.notice -t installer 'app-samba-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/samba/deploy/install ] && /usr/clearos/apps/samba/deploy/install
fi

[ -x /usr/clearos/apps/samba/deploy/upgrade ] && /usr/clearos/apps/samba/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-samba - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-samba-core - uninstalling'
    [ -x /usr/clearos/apps/samba/deploy/uninstall ] && /usr/clearos/apps/samba/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/samba/controllers
/usr/clearos/apps/samba/htdocs
/usr/clearos/apps/samba/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/samba/packaging
%exclude /usr/clearos/apps/samba/tests
%dir /usr/clearos/apps/samba
%dir /var/clearos/samba
%dir /var/clearos/samba/backup
/usr/clearos/apps/samba/deploy
/usr/clearos/apps/samba/language
/usr/clearos/apps/samba/libraries
/usr/sbin/add-samba-directories
/usr/sbin/samba-init
/var/clearos/ldap/synchronize/smb.ldap.conf
/var/clearos/ldap/synchronize/smb.winbind.conf
