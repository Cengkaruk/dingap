
Name: app-simple-mode-core
Group: ClearOS/Libraries
Version: 5.9.9.3
Release: 2.1%{dist}
Summary: Translation missing (simple_mode_base_system_mode) - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-simple-mode-%{version}.tar.gz
Buildarch: noarch
Provides: system-mode-driver
Requires: app-base-core
Requires: app-mode-core

%description
Translation missing (simple_mode_app_long_description)

This package provides the core API and libraries.

%prep
%setup -q -n app-simple-mode-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/simple_mode
cp -r * %{buildroot}/usr/clearos/apps/simple_mode/


%post
logger -p local6.notice -t installer 'app-simple-mode-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/simple_mode/deploy/install ] && /usr/clearos/apps/simple_mode/deploy/install
fi

[ -x /usr/clearos/apps/simple_mode/deploy/upgrade ] && /usr/clearos/apps/simple_mode/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-simple-mode-core - uninstalling'
    [ -x /usr/clearos/apps/simple_mode/deploy/uninstall ] && /usr/clearos/apps/simple_mode/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/simple_mode/packaging
%exclude /usr/clearos/apps/simple_mode/tests
%dir /usr/clearos/apps/simple_mode
/usr/clearos/apps/simple_mode/deploy
/usr/clearos/apps/simple_mode/language
/usr/clearos/apps/simple_mode/libraries
