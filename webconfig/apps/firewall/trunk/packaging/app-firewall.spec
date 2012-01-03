
Name: app-firewall
Version: 6.1.0.beta2.2
Release: 1%{dist}
Summary: Firewall - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Source: app-firewall-%{version}.tar.gz
Buildarch: noarch

%description
The core firewall engine for the system.

%package core
Summary: Firewall - APIs and install
Requires: app-base-core
Requires: app-network-core
Requires: csplugin-filewatch
Requires: firewall >= 1.4.7-3
Requires: iptables

%description core
The core firewall engine for the system.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/firewall
cp -r * %{buildroot}/usr/clearos/apps/firewall/

install -d -m 0755 %{buildroot}/etc/clearos/firewall.d
install -d -m 0755 %{buildroot}/var/clearos/firewall
install -D -m 0644 packaging/filewatch-firewall.conf %{buildroot}/etc/clearsync.d/filewatch-firewall.conf
install -D -m 0755 packaging/firewall-start %{buildroot}/usr/sbin/firewall-start
install -D -m 0644 packaging/firewall.conf %{buildroot}/etc/clearos/firewall.conf
install -D -m 0755 packaging/firewall.init %{buildroot}/etc/rc.d/init.d/firewall
install -D -m 0755 packaging/local %{buildroot}/etc/clearos/firewall.d/local
install -D -m 0755 packaging/types %{buildroot}/etc/clearos/firewall.d/types

%post core
logger -p local6.notice -t installer 'app-firewall-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/firewall/deploy/install ] && /usr/clearos/apps/firewall/deploy/install
fi

[ -x /usr/clearos/apps/firewall/deploy/upgrade ] && /usr/clearos/apps/firewall/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-firewall-core - uninstalling'
    [ -x /usr/clearos/apps/firewall/deploy/uninstall ] && /usr/clearos/apps/firewall/deploy/uninstall
fi

exit 0

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/firewall/packaging
%exclude /usr/clearos/apps/firewall/tests
%dir /usr/clearos/apps/firewall
%dir /etc/clearos/firewall.d
%dir /var/clearos/firewall
/usr/clearos/apps/firewall/deploy
/usr/clearos/apps/firewall/language
/usr/clearos/apps/firewall/libraries
/etc/clearsync.d/filewatch-firewall.conf
/usr/sbin/firewall-start
%config(noreplace) /etc/clearos/firewall.conf
/etc/rc.d/init.d/firewall
%config(noreplace) /etc/clearos/firewall.d/local
/etc/clearos/firewall.d/types
