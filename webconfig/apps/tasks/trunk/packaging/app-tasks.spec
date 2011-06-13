
Name: app-tasks-core
Group: ClearOS/Libraries
Version: 5.9.9.2
Release: 2.2%{dist}
Summary: Task summary - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-tasks-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: cronie >= 1.4.4

%description
Task scheduler long description

This package provides the core API and libraries.

%prep
%setup -q -n app-tasks-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/tasks
cp -r * %{buildroot}/usr/clearos/apps/tasks/


%post
logger -p local6.notice -t installer 'app-tasks-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/tasks/deploy/install ] && /usr/clearos/apps/tasks/deploy/install
fi

[ -x /usr/clearos/apps/tasks/deploy/upgrade ] && /usr/clearos/apps/tasks/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-tasks-core - uninstalling'
    [ -x /usr/clearos/apps/tasks/deploy/uninstall ] && /usr/clearos/apps/tasks/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/tasks/packaging
%exclude /usr/clearos/apps/tasks/tests
%dir /usr/clearos/apps/tasks
/usr/clearos/apps/tasks/deploy
/usr/clearos/apps/tasks/language
/usr/clearos/apps/tasks/libraries
