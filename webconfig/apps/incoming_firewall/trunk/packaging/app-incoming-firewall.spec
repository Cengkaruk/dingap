
Name: app-incoming-firewall
Group: ClearOS/Apps
Version: 5.9.9.2
Release: 1%{dist}
Summary: Incoming Firewall summary
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
Incoming Firewall long description

%package core
Summary: Incoming Firewall summary - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-firewall-core
Requires: app-network-core

%description core
Incoming Firewall long description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/incoming_firewall
cp -r * %{buildroot}/usr/clearos/apps/incoming_firewall/


%post
logger -p local6.notice -t installer 'app-incoming-firewall - installing'

%post core
logger -p local6.notice -t installer 'app-incoming-firewall-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/incoming_firewall/deploy/install ] && /usr/clearos/apps/incoming_firewall/deploy/install
fi

[ -x /usr/clearos/apps/incoming_firewall/deploy/upgrade ] && /usr/clearos/apps/incoming_firewall/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-incoming-firewall - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-incoming-firewall-core - uninstalling'
    [ -x /usr/clearos/apps/incoming_firewall/deploy/uninstall ] && /usr/clearos/apps/incoming_firewall/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/incoming_firewall/controllers
/usr/clearos/apps/incoming_firewall/htdocs
/usr/clearos/apps/incoming_firewall/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/incoming_firewall/packaging
%exclude /usr/clearos/apps/incoming_firewall/tests
%dir /usr/clearos/apps/incoming_firewall
/usr/clearos/apps/incoming_firewall/deploy
/usr/clearos/apps/incoming_firewall/language
/usr/clearos/apps/incoming_firewall/libraries
