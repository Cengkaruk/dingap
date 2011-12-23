
Name: app-password-policies
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Password Policies
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

%description
Password Policies description...

%package core
Summary: Password Policies - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-openldap-core

%description core
Password Policies description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/password_policies
cp -r * %{buildroot}/usr/clearos/apps/password_policies/


%post
logger -p local6.notice -t installer 'app-password-policies - installing'

%post core
logger -p local6.notice -t installer 'app-password-policies-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/password_policies/deploy/install ] && /usr/clearos/apps/password_policies/deploy/install
fi

[ -x /usr/clearos/apps/password_policies/deploy/upgrade ] && /usr/clearos/apps/password_policies/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-password-policies - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-password-policies-core - uninstalling'
    [ -x /usr/clearos/apps/password_policies/deploy/uninstall ] && /usr/clearos/apps/password_policies/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/password_policies/controllers
/usr/clearos/apps/password_policies/htdocs
/usr/clearos/apps/password_policies/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/password_policies/packaging
%exclude /usr/clearos/apps/password_policies/tests
%dir /usr/clearos/apps/password_policies
/usr/clearos/apps/password_policies/deploy
/usr/clearos/apps/password_policies/language
/usr/clearos/apps/password_policies/libraries
