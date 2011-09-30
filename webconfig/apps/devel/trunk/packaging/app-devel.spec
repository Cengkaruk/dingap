
Name: app-devel
Group: ClearOS/Apps
Version: 5.9.9.5
Release: 1%{dist}
Summary: Translation missing (devel_app_name)
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Translation missing (devel_app_description)

%package core
Summary: Translation missing (devel_app_name) - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
Translation missing (devel_app_description)

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/devel
cp -r * %{buildroot}/usr/clearos/apps/devel/


%post
logger -p local6.notice -t installer 'app-devel - installing'

%post core
logger -p local6.notice -t installer 'app-devel-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/devel/deploy/install ] && /usr/clearos/apps/devel/deploy/install
fi

[ -x /usr/clearos/apps/devel/deploy/upgrade ] && /usr/clearos/apps/devel/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-devel - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-devel-core - uninstalling'
    [ -x /usr/clearos/apps/devel/deploy/uninstall ] && /usr/clearos/apps/devel/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/devel/controllers
/usr/clearos/apps/devel/htdocs
/usr/clearos/apps/devel/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/devel/packaging
%exclude /usr/clearos/apps/devel/tests
%dir /usr/clearos/apps/devel
/usr/clearos/apps/devel/deploy
/usr/clearos/apps/devel/language
/usr/clearos/apps/devel/libraries
