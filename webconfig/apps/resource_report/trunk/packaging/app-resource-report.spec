
Name: app-resource-report
Group: ClearOS/Apps
Version: 5.9.9.3
Release: 2.1%{dist}
Summary: Resource Report
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Resource Report description...

%package core
Summary: Resource Report - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core

%description core
Resource Report description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/resource_report
cp -r * %{buildroot}/usr/clearos/apps/resource_report/


%post
logger -p local6.notice -t installer 'app-resource-report - installing'

%post core
logger -p local6.notice -t installer 'app-resource-report-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/resource_report/deploy/install ] && /usr/clearos/apps/resource_report/deploy/install
fi

[ -x /usr/clearos/apps/resource_report/deploy/upgrade ] && /usr/clearos/apps/resource_report/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-resource-report - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-resource-report-core - uninstalling'
    [ -x /usr/clearos/apps/resource_report/deploy/uninstall ] && /usr/clearos/apps/resource_report/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/resource_report/controllers
/usr/clearos/apps/resource_report/htdocs
/usr/clearos/apps/resource_report/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/resource_report/packaging
%exclude /usr/clearos/apps/resource_report/tests
%dir /usr/clearos/apps/resource_report
/usr/clearos/apps/resource_report/deploy
/usr/clearos/apps/resource_report/language
/usr/clearos/apps/resource_report/libraries
