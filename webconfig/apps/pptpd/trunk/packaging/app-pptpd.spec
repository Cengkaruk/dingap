
Name: app-pptpd
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: PPTP VPN Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
The PPTP provides a VPN server for end users.

%package core
Summary: PPTP VPN Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: pptpd >= 1.3.4

%description core
The PPTP provides a VPN server for end users.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/pptpd
cp -r * %{buildroot}/usr/clearos/apps/pptpd/


%post
logger -p local6.notice -t installer 'app-pptpd - installing'

%post core
logger -p local6.notice -t installer 'app-pptpd-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/pptpd/deploy/install ] && /usr/clearos/apps/pptpd/deploy/install
fi

[ -x /usr/clearos/apps/pptpd/deploy/upgrade ] && /usr/clearos/apps/pptpd/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-pptpd - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-pptpd-core - uninstalling'
    [ -x /usr/clearos/apps/pptpd/deploy/uninstall ] && /usr/clearos/apps/pptpd/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/pptpd/controllers
/usr/clearos/apps/pptpd/htdocs
/usr/clearos/apps/pptpd/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/pptpd/packaging
%exclude /usr/clearos/apps/pptpd/tests
%dir /usr/clearos/apps/pptpd
/usr/clearos/apps/pptpd/deploy
/usr/clearos/apps/pptpd/language
/usr/clearos/apps/pptpd/libraries
