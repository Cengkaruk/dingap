
Name: app-directory-manager-core
Group: ClearOS/Libraries
Version: 5.9.9.0
Release: 1
Summary: Directory management and setup - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-directory-manager-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core

%description
The Directory Manager provides... blah blah blah

This package provides the core API and libraries.

%prep
%setup -q -n app-directory-manager-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/directory_manager
cp -r * %{buildroot}/usr/clearos/apps/directory_manager/

install -d -m 0755 %{buildroot}/var/clearos/directory_manager/drivers
install -d -m 0755 %{buildroot}/var/clearos/directory_manager/plugins

%post
logger -p local6.notice -t installer 'app-directory-manager-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/directory_manager/deploy/install ] && /usr/clearos/apps/directory_manager/deploy/install
fi

[ -x /usr/clearos/apps/directory_manager/deploy/upgrade ] && /usr/clearos/apps/directory_manager/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-directory-manager-core - uninstalling'
    [ -x /usr/clearos/apps/directory_manager/deploy/uninstall ] && /usr/clearos/apps/directory_manager/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/directory_manager/packaging
%exclude /usr/clearos/apps/directory_manager/tests
%dir /usr/clearos/apps/directory_manager
%dir /var/clearos/directory_manager/drivers
%dir /var/clearos/directory_manager/plugins
/usr/clearos/apps/directory_manager/deploy
/usr/clearos/apps/directory_manager/language
/usr/clearos/apps/directory_manager/libraries
