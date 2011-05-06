
Name: app-file-scan
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Antivirus file scannner
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Antivirus file scanner...blah blah.

%package core
Summary: Antivirus file scannner - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
Antivirus file scanner...blah blah.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/file_scan
cp -r * %{buildroot}/usr/clearos/apps/file_scan/


%post
logger -p local6.notice -t installer 'app-file-scan - installing'

%post core
logger -p local6.notice -t installer 'app-file-scan-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/file_scan/deploy/install ] && /usr/clearos/apps/file_scan/deploy/install
fi

[ -x /usr/clearos/apps/file_scan/deploy/upgrade ] && /usr/clearos/apps/file_scan/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-file-scan - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-file-scan-core - uninstalling'
    [ -x /usr/clearos/apps/file_scan/deploy/uninstall ] && /usr/clearos/apps/file_scan/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/file_scan/controllers
/usr/clearos/apps/file_scan/htdocs
/usr/clearos/apps/file_scan/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/file_scan/packaging
%exclude /usr/clearos/apps/file_scan/tests
%dir /usr/clearos/apps/file_scan
/usr/clearos/apps/file_scan/deploy
/usr/clearos/apps/file_scan/language
/usr/clearos/apps/file_scan/libraries
