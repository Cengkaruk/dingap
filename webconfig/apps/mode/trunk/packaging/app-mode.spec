
Name: app-mode
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Translation missing (mode_base_system_mode) - APIs and instalcl
License: LGPLv3
Group: ClearOS/Libraries
Source: app-mode-%{version}.tar.gz
Buildarch: noarch

%description
Translation missing (mode_app_long_description)

%package core
Summary: Translation missing (mode_base_system_mode) - APIs and install
Requires: app-base-core
Requires: system-mode-driver

%description core
Translation missing (mode_app_long_description)

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/mode
cp -r * %{buildroot}/usr/clearos/apps/mode/

install -d -m 0755 %{buildroot}/var/clearos/mode
install -D -m 0644 packaging/mode.conf %{buildroot}/var/clearos/mode

%post core
logger -p local6.notice -t installer 'app-mode-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/mode/deploy/install ] && /usr/clearos/apps/mode/deploy/install
fi

[ -x /usr/clearos/apps/mode/deploy/upgrade ] && /usr/clearos/apps/mode/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mode-core - uninstalling'
    [ -x /usr/clearos/apps/mode/deploy/uninstall ] && /usr/clearos/apps/mode/deploy/uninstall
fi

exit 0

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/mode/packaging
%exclude /usr/clearos/apps/mode/tests
%dir /usr/clearos/apps/mode
%dir /var/clearos/mode
/usr/clearos/apps/mode/deploy
/usr/clearos/apps/mode/language
/usr/clearos/apps/mode/libraries
%config(noreplace) /var/clearos/mode
