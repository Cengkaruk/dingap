
Name: app-syswatch
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: System Watch - APIs and instalcl
License: LGPLv3
Group: ClearOS/Libraries
Source: app-syswatch-%{version}.tar.gz
Buildarch: noarch

%description
System Watch description

%package core
Summary: System Watch - APIs and install
Requires: app-base-core

%description core
System Watch description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/syswatch
cp -r * %{buildroot}/usr/clearos/apps/syswatch/

install -D -m 0644 packaging/syswatch.php %{buildroot}/var/clearos/base/daemon/syswatch.php

%post core
logger -p local6.notice -t installer 'app-syswatch-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/syswatch/deploy/install ] && /usr/clearos/apps/syswatch/deploy/install
fi

[ -x /usr/clearos/apps/syswatch/deploy/upgrade ] && /usr/clearos/apps/syswatch/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-syswatch-core - uninstalling'
    [ -x /usr/clearos/apps/syswatch/deploy/uninstall ] && /usr/clearos/apps/syswatch/deploy/uninstall
fi

exit 0

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/syswatch/packaging
%exclude /usr/clearos/apps/syswatch/tests
%dir /usr/clearos/apps/syswatch
/usr/clearos/apps/syswatch/deploy
/usr/clearos/apps/syswatch/language
/usr/clearos/apps/syswatch/libraries
/var/clearos/base/daemon/syswatch.php
