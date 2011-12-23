
Name: app-tasks
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Task Scheduler - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Source: app-tasks-%{version}.tar.gz
Buildarch: noarch

%description
Task scheduler description

%package core
Summary: Task Scheduler - APIs and install
Requires: app-base-core
Requires: cronie >= 1.4.4

%description core
Task scheduler description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/tasks
cp -r * %{buildroot}/usr/clearos/apps/tasks/

install -D -m 0644 packaging/crond.php %{buildroot}/var/clearos/base/daemon/crond.php

%post core
logger -p local6.notice -t installer 'app-tasks-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/tasks/deploy/install ] && /usr/clearos/apps/tasks/deploy/install
fi

[ -x /usr/clearos/apps/tasks/deploy/upgrade ] && /usr/clearos/apps/tasks/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-tasks-core - uninstalling'
    [ -x /usr/clearos/apps/tasks/deploy/uninstall ] && /usr/clearos/apps/tasks/deploy/uninstall
fi

exit 0

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/tasks/packaging
%exclude /usr/clearos/apps/tasks/tests
%dir /usr/clearos/apps/tasks
/usr/clearos/apps/tasks/deploy
/usr/clearos/apps/tasks/language
/usr/clearos/apps/tasks/libraries
/var/clearos/base/daemon/crond.php
