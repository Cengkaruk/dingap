
Name: app-firewall-custom
Group: ClearOS/Apps
Version: 5.9.9.2
Release: 1%{dist}
Summary: Allows customized firewall (iptables) rules to be added.  In advanced or highly customised networking environments, the addition of advanced firewall rules that cannot be added using the available firewall apps (Incoming, DMZ, MultiWAN, Port Forwarding etc.) to achieve the desired security and packet routing can be accomplished using the custom firewall app.  Use of this module should be reserved for advanced users only
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-firewall

%description
Allows customized firewall rules to be added.

%package core
Summary: Allows customized firewall (iptables) rules to be added.  In advanced or highly customised networking environments, the addition of advanced firewall rules that cannot be added using the available firewall apps (Incoming, DMZ, MultiWAN, Port Forwarding etc.) to achieve the desired security and packet routing can be accomplished using the custom firewall app.  Use of this module should be reserved for advanced users only - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
Allows customized firewall rules to be added.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/firewall_custom
cp -r * %{buildroot}/usr/clearos/apps/firewall_custom/


%post
logger -p local6.notice -t installer 'app-firewall-custom - installing'

%post core
logger -p local6.notice -t installer 'app-firewall-custom-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/firewall_custom/deploy/install ] && /usr/clearos/apps/firewall_custom/deploy/install
fi

[ -x /usr/clearos/apps/firewall_custom/deploy/upgrade ] && /usr/clearos/apps/firewall_custom/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-firewall-custom - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-firewall-custom-core - uninstalling'
    [ -x /usr/clearos/apps/firewall_custom/deploy/uninstall ] && /usr/clearos/apps/firewall_custom/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/firewall_custom/controllers
/usr/clearos/apps/firewall_custom/htdocs
/usr/clearos/apps/firewall_custom/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/firewall_custom/packaging
%exclude /usr/clearos/apps/firewall_custom/tests
%dir /usr/clearos/apps/firewall_custom
/usr/clearos/apps/firewall_custom/deploy
/usr/clearos/apps/firewall_custom/language
/usr/clearos/apps/firewall_custom/libraries
