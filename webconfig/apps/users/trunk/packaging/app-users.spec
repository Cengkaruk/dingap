
Name: app-users
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: User manager
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts

%description
User manager description blah blah blah ...

%package core
Summary: User manager - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-accounts-core

%description core
User manager description blah blah blah ...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/users
cp -r * %{buildroot}/usr/clearos/apps/users/


%post
logger -p local6.notice -t installer 'app-users - installing'

%post core
logger -p local6.notice -t installer 'app-users-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/users/deploy/install ] && /usr/clearos/apps/users/deploy/install
fi

[ -x /usr/clearos/apps/users/deploy/upgrade ] && /usr/clearos/apps/users/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-users - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-users-core - uninstalling'
    [ -x /usr/clearos/apps/users/deploy/uninstall ] && /usr/clearos/apps/users/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/users/controllers
/usr/clearos/apps/users/htdocs
/usr/clearos/apps/users/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/users/packaging
%exclude /usr/clearos/apps/users/tests
%dir /usr/clearos/apps/users
/usr/clearos/apps/users/deploy
/usr/clearos/apps/users/language
/usr/clearos/apps/users/libraries
