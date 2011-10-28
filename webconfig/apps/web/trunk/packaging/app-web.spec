
Name: app-web
Group: ClearOS/Apps
Version: 5.9.9.5
Release: 1%{dist}
Summary: Web Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-groups
Requires: app-users
Requires: app-network

%description
Web Server description

%package core
Summary: Web Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: httpd >= 2.2.15

%description core
Web Server description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/web
cp -r * %{buildroot}/usr/clearos/apps/web/

install -d -m 0755 %{buildroot}/var/clearos/httpd
install -D -m 0644 packaging/httpd.php %{buildroot}/var/clearos/base/daemon/httpd.php

%post
logger -p local6.notice -t installer 'app-web - installing'

%post core
logger -p local6.notice -t installer 'app-web-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/web/deploy/install ] && /usr/clearos/apps/web/deploy/install
fi

[ -x /usr/clearos/apps/web/deploy/upgrade ] && /usr/clearos/apps/web/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-web - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-web-core - uninstalling'
    [ -x /usr/clearos/apps/web/deploy/uninstall ] && /usr/clearos/apps/web/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/web/controllers
/usr/clearos/apps/web/htdocs
/usr/clearos/apps/web/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/web/packaging
%exclude /usr/clearos/apps/web/tests
%dir /usr/clearos/apps/web
%dir /var/clearos/httpd
/usr/clearos/apps/web/deploy
/usr/clearos/apps/web/language
/usr/clearos/apps/web/libraries
/var/clearos/base/daemon/httpd.php
