
Name: app-directory-manager
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1
Summary: Directory management and setup
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Requires: %{name}-core = %{version}-%{release}
Buildarch: noarch

%description
The Directory Manager provides... blah blah blah

%package core
Summary: Core libraries and install for app-directory-manager
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base
Requires: app-samba-core

%description core
Core API and install for app-directory-manager

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/directory_manager
cp -r * %{buildroot}/usr/clearos/apps/directory_manager/

install -d -m 0755 %{buildroot}/var/clearos/directory_manager/drivers
install -d -m 0755 %{buildroot}/var/clearos/directory_manager/plugins

%post
logger -p local6.notice -t installer 'app-directory-manager - installing'

%post core
logger -p local6.notice -t installer 'app-directory-manager-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/directory_manager/deploy/install ] && /usr/clearos/apps/directory_manager/deploy/install
fi

[ -x /usr/clearos/apps/directory_manager/deploy/upgrade ] && /usr/clearos/apps/directory_manager/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-directory-manager - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-directory-manager-core - uninstalling'
    [ -x /usr/clearos/apps/directory_manager/deploy/uninstall ] && /usr/clearos/apps/directory_manager/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/directory_manager/controllers
/usr/clearos/apps/directory_manager/htdocs
/usr/clearos/apps/directory_manager/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/directory_manager/packaging
%exclude /usr/clearos/apps/directory_manager/tests
%dir /usr/clearos/apps/directory_manager
%dir /var/clearos/directory_manager/drivers
%dir /var/clearos/directory_manager/plugins
/usr/clearos/apps/directory_manager/config
/usr/clearos/apps/directory_manager/deploy
/usr/clearos/apps/directory_manager/language
/usr/clearos/apps/directory_manager/libraries
