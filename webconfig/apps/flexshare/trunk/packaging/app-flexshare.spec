
Name: app-flexshare
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Flexshare app summary
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Flexshare app long description...

%package core
Summary: Flexshare app summary - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-cron-core
Requires: ntpdate >= 4.2.4p8

%description core
Flexshare app long description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/flexshare
cp -r * %{buildroot}/usr/clearos/apps/flexshare/

install -d -m 0755 %{buildroot}/var/flexshare
install -d -m 0755 %{buildroot}/var/flexshare/shares
install -D -m 0600 packaging/flexshare.conf %{buildroot}/etc/flexshare.conf
install -D -m 0755 packaging/updateflexperms %{buildroot}/usr/sbin/updateflexperms

%post
logger -p local6.notice -t installer 'app-flexshare - installing'

%post core
logger -p local6.notice -t installer 'app-flexshare-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/flexshare/deploy/install ] && /usr/clearos/apps/flexshare/deploy/install
fi

[ -x /usr/clearos/apps/flexshare/deploy/upgrade ] && /usr/clearos/apps/flexshare/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-flexshare - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-flexshare-core - uninstalling'
    [ -x /usr/clearos/apps/flexshare/deploy/uninstall ] && /usr/clearos/apps/flexshare/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/flexshare/controllers
/usr/clearos/apps/flexshare/htdocs
/usr/clearos/apps/flexshare/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/flexshare/packaging
%exclude /usr/clearos/apps/flexshare/tests
%dir /usr/clearos/apps/flexshare
%dir /var/flexshare
%dir /var/flexshare/shares
/usr/clearos/apps/flexshare/deploy
/usr/clearos/apps/flexshare/language
/usr/clearos/apps/flexshare/libraries
%config(noreplace) /etc/flexshare.conf
/usr/sbin/updateflexperms
