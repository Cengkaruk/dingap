
Name: app-syswatch-core
Group: ClearOS/Libraries
Version: 5.9.9.3
Release: 2.1%{dist}
Summary: System Watch - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-syswatch-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core

%description
System Watch description

This package provides the core API and libraries.

%prep
%setup -q -n app-syswatch-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/syswatch
cp -r * %{buildroot}/usr/clearos/apps/syswatch/


%post
logger -p local6.notice -t installer 'app-syswatch-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/syswatch/deploy/install ] && /usr/clearos/apps/syswatch/deploy/install
fi

[ -x /usr/clearos/apps/syswatch/deploy/upgrade ] && /usr/clearos/apps/syswatch/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-syswatch-core - uninstalling'
    [ -x /usr/clearos/apps/syswatch/deploy/uninstall ] && /usr/clearos/apps/syswatch/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/syswatch/packaging
%exclude /usr/clearos/apps/syswatch/tests
%dir /usr/clearos/apps/syswatch
/usr/clearos/apps/syswatch/deploy
/usr/clearos/apps/syswatch/language
/usr/clearos/apps/syswatch/libraries
