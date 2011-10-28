
Name: app-greylisting
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Greylisting
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Greylisting description...

%package core
Summary: Greylisting - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: postgrey >= 1.33-2

%description core
Greylisting description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/greylisting
cp -r * %{buildroot}/usr/clearos/apps/greylisting/

install -D -m 0644 packaging/postgrey.php %{buildroot}/var/clearos/base/daemon/postgrey.php

%post
logger -p local6.notice -t installer 'app-greylisting - installing'

%post core
logger -p local6.notice -t installer 'app-greylisting-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/greylisting/deploy/install ] && /usr/clearos/apps/greylisting/deploy/install
fi

[ -x /usr/clearos/apps/greylisting/deploy/upgrade ] && /usr/clearos/apps/greylisting/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-greylisting - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-greylisting-core - uninstalling'
    [ -x /usr/clearos/apps/greylisting/deploy/uninstall ] && /usr/clearos/apps/greylisting/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/greylisting/controllers
/usr/clearos/apps/greylisting/htdocs
/usr/clearos/apps/greylisting/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/greylisting/packaging
%exclude /usr/clearos/apps/greylisting/tests
%dir /usr/clearos/apps/greylisting
/usr/clearos/apps/greylisting/deploy
/usr/clearos/apps/greylisting/language
/usr/clearos/apps/greylisting/libraries
/var/clearos/base/daemon/postgrey.php
