
Name: app-groups
Group: ClearOS/Apps
Version: 5.9.9.2
Release: 2.2%{dist}
Summary: Group manager
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-users

%description
Group manager description blah blah...

%package core
Summary: Group manager - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-accounts-core
Requires: system-groups-driver

%description core
Group manager description blah blah...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/groups
cp -r * %{buildroot}/usr/clearos/apps/groups/


%post
logger -p local6.notice -t installer 'app-groups - installing'

%post core
logger -p local6.notice -t installer 'app-groups-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/groups/deploy/install ] && /usr/clearos/apps/groups/deploy/install
fi

[ -x /usr/clearos/apps/groups/deploy/upgrade ] && /usr/clearos/apps/groups/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-groups - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-groups-core - uninstalling'
    [ -x /usr/clearos/apps/groups/deploy/uninstall ] && /usr/clearos/apps/groups/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/groups/controllers
/usr/clearos/apps/groups/htdocs
/usr/clearos/apps/groups/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/groups/packaging
%exclude /usr/clearos/apps/groups/tests
%dir /usr/clearos/apps/groups
/usr/clearos/apps/groups/deploy
/usr/clearos/apps/groups/language
/usr/clearos/apps/groups/libraries
