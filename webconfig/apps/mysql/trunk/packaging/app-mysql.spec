
Name: app-mysql
Group: ClearOS/Apps
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: MySQL Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
MySQL is an open-source, Relational Database Management System (RDMS).  It can be configured to run database driven applications, websites, CRM and practically any other resource requiring a relational storage service.

%package core
Summary: MySQL Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: mysql-server >= 5.1.52
Requires: phpMyAdmin >= 3.4.7

%description core
MySQL is an open-source, Relational Database Management System (RDMS).  It can be configured to run database driven applications, websites, CRM and practically any other resource requiring a relational storage service.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/mysql
cp -r * %{buildroot}/usr/clearos/apps/mysql/

install -D -m 0644 packaging/mysql-default.conf %{buildroot}/etc/storage.d/mysql-default.conf
install -D -m 0644 packaging/mysql.php %{buildroot}/var/clearos/storage/plugins/mysql.php
install -D -m 0644 packaging/mysqld.php %{buildroot}/var/clearos/base/daemon/mysqld.php

%post
logger -p local6.notice -t installer 'app-mysql - installing'

%post core
logger -p local6.notice -t installer 'app-mysql-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/mysql/deploy/install ] && /usr/clearos/apps/mysql/deploy/install
fi

[ -x /usr/clearos/apps/mysql/deploy/upgrade ] && /usr/clearos/apps/mysql/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mysql - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mysql-core - uninstalling'
    [ -x /usr/clearos/apps/mysql/deploy/uninstall ] && /usr/clearos/apps/mysql/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/mysql/controllers
/usr/clearos/apps/mysql/htdocs
/usr/clearos/apps/mysql/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/mysql/packaging
%exclude /usr/clearos/apps/mysql/tests
%dir /usr/clearos/apps/mysql
/usr/clearos/apps/mysql/deploy
/usr/clearos/apps/mysql/language
/usr/clearos/apps/mysql/libraries
/etc/storage.d/mysql-default.conf
/var/clearos/storage/plugins/mysql.php
/var/clearos/base/daemon/mysqld.php
