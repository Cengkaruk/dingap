
Name: app-disk-usage
Group: ClearOS/Apps
Version: 5.9.9.4
Release: 1.1%{dist}
Summary: Disk Usage
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Displays your system hard disk usage.

%package core
Summary: Disk Usage - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: philesight

%description core
Displays your system hard disk usage.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/disk_usage
cp -r * %{buildroot}/usr/clearos/apps/disk_usage/

install -d -m 755 %{buildroot}/var/clearos/disk_usage
install -D -m 0644 packaging/app-disk-usage.cron %{buildroot}/etc/cron.d/app-disk-usage

%post
logger -p local6.notice -t installer 'app-disk-usage - installing'

%post core
logger -p local6.notice -t installer 'app-disk-usage-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/disk_usage/deploy/install ] && /usr/clearos/apps/disk_usage/deploy/install
fi

[ -x /usr/clearos/apps/disk_usage/deploy/upgrade ] && /usr/clearos/apps/disk_usage/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-disk-usage - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-disk-usage-core - uninstalling'
    [ -x /usr/clearos/apps/disk_usage/deploy/uninstall ] && /usr/clearos/apps/disk_usage/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/disk_usage/controllers
/usr/clearos/apps/disk_usage/htdocs
/usr/clearos/apps/disk_usage/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/disk_usage/packaging
%exclude /usr/clearos/apps/disk_usage/tests
%dir /usr/clearos/apps/disk_usage
%dir %attr(755,webconfig,webconfig) /var/clearos/disk_usage
/usr/clearos/apps/disk_usage/deploy
/usr/clearos/apps/disk_usage/language
/usr/clearos/apps/disk_usage/libraries
/etc/cron.d/app-disk-usage
