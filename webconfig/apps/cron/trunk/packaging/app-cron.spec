
Name: app-cron-core
Group: ClearOS/Libraries
Version: 5.9.9.0
Release: 1%{dist}
Summary: Cron.. - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-cron-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: app-base
Requires: cronie >= 1.4.4

%description
Cron...

This package provides the core API and libraries.

%prep
%setup -q -n app-cron-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/cron
cp -r * %{buildroot}/usr/clearos/apps/cron/


%post
logger -p local6.notice -t installer 'app-cron-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/cron/deploy/install ] && /usr/clearos/apps/cron/deploy/install
fi

[ -x /usr/clearos/apps/cron/deploy/upgrade ] && /usr/clearos/apps/cron/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-cron-core - uninstalling'
    [ -x /usr/clearos/apps/cron/deploy/uninstall ] && /usr/clearos/apps/cron/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/cron/packaging
%exclude /usr/clearos/apps/cron/tests
%dir /usr/clearos/apps/cron
/usr/clearos/apps/cron/deploy
/usr/clearos/apps/cron/language
/usr/clearos/apps/cron/libraries
