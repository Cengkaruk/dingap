
Name: app-antivirus
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Gateway Antivirus app summary..
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Gateway Antivirus long description...

%package core
Summary: Gateway Antivirus app summary.. - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: clamd

%description core
Gateway Antivirus long description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/antivirus
cp -r * %{buildroot}/usr/clearos/apps/antivirus/


%post
logger -p local6.notice -t installer 'app-antivirus - installing'

%post core
logger -p local6.notice -t installer 'app-antivirus-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/antivirus/deploy/install ] && /usr/clearos/apps/antivirus/deploy/install
fi

[ -x /usr/clearos/apps/antivirus/deploy/upgrade ] && /usr/clearos/apps/antivirus/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-antivirus - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-antivirus-core - uninstalling'
    [ -x /usr/clearos/apps/antivirus/deploy/uninstall ] && /usr/clearos/apps/antivirus/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/antivirus/controllers
/usr/clearos/apps/antivirus/htdocs
/usr/clearos/apps/antivirus/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/antivirus/packaging
%exclude /usr/clearos/apps/antivirus/tests
%dir /usr/clearos/apps/antivirus
/usr/clearos/apps/antivirus/deploy
/usr/clearos/apps/antivirus/language
/usr/clearos/apps/antivirus/libraries
