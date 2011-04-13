
Name: app-mode
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: System Mode
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
The mode manager... master/slave/standalone.

%package core
Summary: System Mode - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
The mode manager... master/slave/standalone.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/mode
cp -r * %{buildroot}/usr/clearos/apps/mode/


%post
logger -p local6.notice -t installer 'app-mode - installing'

%post core
logger -p local6.notice -t installer 'app-mode-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/mode/deploy/install ] && /usr/clearos/apps/mode/deploy/install
fi

[ -x /usr/clearos/apps/mode/deploy/upgrade ] && /usr/clearos/apps/mode/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mode - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mode-core - uninstalling'
    [ -x /usr/clearos/apps/mode/deploy/uninstall ] && /usr/clearos/apps/mode/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/mode/controllers
/usr/clearos/apps/mode/htdocs
/usr/clearos/apps/mode/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/mode/packaging
%exclude /usr/clearos/apps/mode/tests
%dir /usr/clearos/apps/mode
/usr/clearos/apps/mode/deploy
/usr/clearos/apps/mode/language
/usr/clearos/apps/mode/libraries
