
Name: app-cron
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1
Summary: Cron..
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Requires: %{name}-core = %{version}-%{release}
Buildarch: noarch

%description
Cron...

%package core
Summary: Core libraries and install for app-cron
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: cronie >= 1.4.4

%description core
Core API and install for app-cron

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/cron
cp -r * %{buildroot}/usr/clearos/apps/cron/


%post
logger -p local6.notice -t installer 'app-cron - installing'

%post core
logger -p local6.notice -t installer 'app-cron-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/cron/deploy/install ] && /usr/clearos/apps/cron/deploy/install
fi

[ -x /usr/clearos/apps/cron/deploy/upgrade ] && /usr/clearos/apps/cron/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-cron - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-cron-core - uninstalling'
    [ -x /usr/clearos/apps/cron/deploy/uninstall ] && /usr/clearos/apps/cron/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/cron/controllers
/usr/clearos/apps/cron/htdocs
/usr/clearos/apps/cron/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/cron/packaging
%exclude /usr/clearos/apps/cron/tests
%dir /usr/clearos/apps/cron
/usr/clearos/apps/cron/config
/usr/clearos/apps/cron/deploy
/usr/clearos/apps/cron/language
/usr/clearos/apps/cron/libraries
