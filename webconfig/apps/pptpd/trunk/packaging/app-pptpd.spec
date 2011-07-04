
Name: app-pptpd
Group: ClearOS/Apps
Version: 5.9.9.2
Release: 4%{dist}
Summary: PPTP Server
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
Requires: app-network

%description
PPTP Server description... wordsmith please.

%package core
Summary: PPTP Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: app-pptpd-plugin-core
Requires: app-samba-extension-core
Requires: pptpd >= 1.3.4

%description core
PPTP Server description... wordsmith please.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/pptpd
cp -r * %{buildroot}/usr/clearos/apps/pptpd/

install -d -m 0755 %{buildroot}/var/clearos/pptpd

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
%dir /var/clearos/pptpd
/usr/clearos/apps/pptpd/deploy
/usr/clearos/apps/pptpd/language
/usr/clearos/apps/pptpd/libraries
