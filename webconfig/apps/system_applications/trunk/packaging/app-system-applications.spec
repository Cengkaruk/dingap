
Name: app-system-applications
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Applications Overview
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Applications overview... blah blah blah

%package core
Summary: Applications Overview - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
Applications overview... blah blah blah

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/system_applications
cp -r * %{buildroot}/usr/clearos/apps/system_applications/


%post
logger -p local6.notice -t installer 'app-system-applications - installing'

%post core
logger -p local6.notice -t installer 'app-system-applications-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/system_applications/deploy/install ] && /usr/clearos/apps/system_applications/deploy/install
fi

[ -x /usr/clearos/apps/system_applications/deploy/upgrade ] && /usr/clearos/apps/system_applications/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-system-applications - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-system-applications-core - uninstalling'
    [ -x /usr/clearos/apps/system_applications/deploy/uninstall ] && /usr/clearos/apps/system_applications/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/system_applications/controllers
/usr/clearos/apps/system_applications/htdocs
/usr/clearos/apps/system_applications/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/system_applications/packaging
%exclude /usr/clearos/apps/system_applications/tests
%dir /usr/clearos/apps/system_applications
/usr/clearos/apps/system_applications/deploy
/usr/clearos/apps/system_applications/language
/usr/clearos/apps/system_applications/libraries
