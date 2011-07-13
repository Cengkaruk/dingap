
Name: app-shutdown
Group: ClearOS/Apps
Version: 5.9.9.3
Release: 1%{dist}
Summary: Shutdown - Restart
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
A simple tool to shutdown or restart your system.

%package core
Summary: Shutdown - Restart - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
A simple tool to shutdown or restart your system.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/shutdown
cp -r * %{buildroot}/usr/clearos/apps/shutdown/


%post
logger -p local6.notice -t installer 'app-shutdown - installing'

%post core
logger -p local6.notice -t installer 'app-shutdown-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/shutdown/deploy/install ] && /usr/clearos/apps/shutdown/deploy/install
fi

[ -x /usr/clearos/apps/shutdown/deploy/upgrade ] && /usr/clearos/apps/shutdown/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-shutdown - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-shutdown-core - uninstalling'
    [ -x /usr/clearos/apps/shutdown/deploy/uninstall ] && /usr/clearos/apps/shutdown/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/shutdown/controllers
/usr/clearos/apps/shutdown/htdocs
/usr/clearos/apps/shutdown/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/shutdown/packaging
%exclude /usr/clearos/apps/shutdown/tests
%dir /usr/clearos/apps/shutdown
/usr/clearos/apps/shutdown/deploy
/usr/clearos/apps/shutdown/language
/usr/clearos/apps/shutdown/libraries
