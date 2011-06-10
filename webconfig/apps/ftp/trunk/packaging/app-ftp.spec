
Name: app-ftp
Group: ClearOS/Apps
Version: 5.9.9.2
Release: 1%{dist}
Summary: FTP summary
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network
Requires: proftpd >= 1.3.3e

%description
FTP long description

%package core
Summary: FTP summary - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-ftp-plugin
Requires: ntpdate >= 4.2.4p8

%description core
FTP long description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/ftp
cp -r * %{buildroot}/usr/clearos/apps/ftp/

install -d -m 0755 %{buildroot}/var/clearos/ftp

%post
logger -p local6.notice -t installer 'app-ftp - installing'

%post core
logger -p local6.notice -t installer 'app-ftp-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/ftp/deploy/install ] && /usr/clearos/apps/ftp/deploy/install
fi

[ -x /usr/clearos/apps/ftp/deploy/upgrade ] && /usr/clearos/apps/ftp/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ftp - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ftp-core - uninstalling'
    [ -x /usr/clearos/apps/ftp/deploy/uninstall ] && /usr/clearos/apps/ftp/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/ftp/controllers
/usr/clearos/apps/ftp/htdocs
/usr/clearos/apps/ftp/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/ftp/packaging
%exclude /usr/clearos/apps/ftp/tests
%dir /usr/clearos/apps/ftp
%dir /var/clearos/ftp
/usr/clearos/apps/ftp/deploy
/usr/clearos/apps/ftp/language
/usr/clearos/apps/ftp/libraries
