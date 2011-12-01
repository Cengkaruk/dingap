
Name: app-web-access-control
Group: ClearOS/Apps
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: Web Access Control
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-web-proxy

%description
Time-based Access Control allows an administer to enforce time-of-day web access to groups or computers (IP or MAC address) using the web proxy.

%package core
Summary: Web Access Control - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-web-proxy-core

%description core
Time-based Access Control allows an administer to enforce time-of-day web access to groups or computers (IP or MAC address) using the web proxy.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/web_access_control
cp -r * %{buildroot}/usr/clearos/apps/web_access_control/


%post
logger -p local6.notice -t installer 'app-web-access-control - installing'

%post core
logger -p local6.notice -t installer 'app-web-access-control-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/web_access_control/deploy/install ] && /usr/clearos/apps/web_access_control/deploy/install
fi

[ -x /usr/clearos/apps/web_access_control/deploy/upgrade ] && /usr/clearos/apps/web_access_control/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-web-access-control - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-web-access-control-core - uninstalling'
    [ -x /usr/clearos/apps/web_access_control/deploy/uninstall ] && /usr/clearos/apps/web_access_control/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/web_access_control/controllers
/usr/clearos/apps/web_access_control/htdocs
/usr/clearos/apps/web_access_control/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/web_access_control/packaging
%exclude /usr/clearos/apps/web_access_control/tests
%dir /usr/clearos/apps/web_access_control
/usr/clearos/apps/web_access_control/deploy
/usr/clearos/apps/web_access_control/language
/usr/clearos/apps/web_access_control/libraries
