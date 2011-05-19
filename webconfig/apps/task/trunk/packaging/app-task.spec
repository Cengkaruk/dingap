
Name: app-task-core
Group: ClearOS/Libraries
Version: 5.9.9.0
Release: 1%{dist}
Summary: Task summary - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-task-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: cronie >= 1.4.4

%description
Task scheduler long description

This package provides the core API and libraries.

%prep
%setup -q -n app-task-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/task
cp -r * %{buildroot}/usr/clearos/apps/task/


%post
logger -p local6.notice -t installer 'app-task-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/task/deploy/install ] && /usr/clearos/apps/task/deploy/install
fi

[ -x /usr/clearos/apps/task/deploy/upgrade ] && /usr/clearos/apps/task/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-task-core - uninstalling'
    [ -x /usr/clearos/apps/task/deploy/uninstall ] && /usr/clearos/apps/task/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/task/packaging
%exclude /usr/clearos/apps/task/tests
%dir /usr/clearos/apps/task
/usr/clearos/apps/task/deploy
/usr/clearos/apps/task/language
/usr/clearos/apps/task/libraries
