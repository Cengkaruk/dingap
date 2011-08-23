
Name: app-user-profile
Group: ClearOS/Apps
Version: 5.9.9.4
Release: 2%{dist}
Summary: User Profile
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-groups

%description
User Profile description...

%package core
Summary: User Profile - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-accounts-core
Requires: system-users-driver

%description core
User Profile description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/user_profile
cp -r * %{buildroot}/usr/clearos/apps/user_profile/


%post
logger -p local6.notice -t installer 'app-user-profile - installing'

%post core
logger -p local6.notice -t installer 'app-user-profile-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/user_profile/deploy/install ] && /usr/clearos/apps/user_profile/deploy/install
fi

[ -x /usr/clearos/apps/user_profile/deploy/upgrade ] && /usr/clearos/apps/user_profile/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-user-profile - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-user-profile-core - uninstalling'
    [ -x /usr/clearos/apps/user_profile/deploy/uninstall ] && /usr/clearos/apps/user_profile/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/user_profile/controllers
/usr/clearos/apps/user_profile/htdocs
/usr/clearos/apps/user_profile/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/user_profile/packaging
%exclude /usr/clearos/apps/user_profile/tests
%dir /usr/clearos/apps/user_profile
/usr/clearos/apps/user_profile/deploy
/usr/clearos/apps/user_profile/language
/usr/clearos/apps/user_profile/libraries
