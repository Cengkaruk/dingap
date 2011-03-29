
Name: app-directory
Group: ClearOS/Apps
Version: 6.0.0
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
Summary: Core libraries and install for app-directory
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base

%description core
Core API and install for app-directory

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/directory
cp -r * %{buildroot}/usr/clearos/apps/directory/


%post
logger -p local6.notice -t installer 'app-directory - installing'

%post core
logger -p local6.notice -t installer 'app-directory-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/directory/deploy/install ] && /usr/clearos/apps/directory/deploy/install
fi

[ -x /usr/clearos/apps/directory/deploy/upgrade ] && /usr/clearos/apps/directory/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-directory - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-directory-core - uninstalling'
    [ -x /usr/clearos/apps/directory/deploy/uninstall ] && /usr/clearos/apps/directory/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/directory/controllers
/usr/clearos/apps/directory/htdocs
/usr/clearos/apps/directory/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/directory/packaging
%exclude /usr/clearos/apps/directory/tests
%dir /usr/clearos/apps/directory
/usr/clearos/apps/directory/config
/usr/clearos/apps/directory/deploy
/usr/clearos/apps/directory/language
/usr/clearos/apps/directory/libraries
