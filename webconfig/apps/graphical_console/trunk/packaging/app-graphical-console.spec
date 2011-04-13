
Name: app-graphical-console
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1
Summary: Graphical console tool
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}

%description
Graphical console tool for configuring the network.

%package core
Summary: Graphical console tool - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: dbus-x11
Requires: gconsole
Requires: ratpoison
Requires: urw-fonts
Requires: xorg-x11-drivers
Requires: xorg-x11-server-Xorg
Requires: xorg-x11-xinit

%description core
Graphical console tool for configuring the network.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/graphical_console
cp -r * %{buildroot}/usr/clearos/apps/graphical_console/

install -D -m 0644 packaging/xinitrc %{buildroot}/var/lib/clearconsole/.xinitrc
install -D -m 0644 packaging/Xdefaults %{buildroot}/var/lib/clearconsole/.Xdefaults

%post
logger -p local6.notice -t installer 'app-graphical-console - installing'

%post core
logger -p local6.notice -t installer 'app-graphical-console-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/graphical_console/deploy/install ] && /usr/clearos/apps/graphical_console/deploy/install
fi

[ -x /usr/clearos/apps/graphical_console/deploy/upgrade ] && /usr/clearos/apps/graphical_console/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-graphical-console - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-graphical-console-core - uninstalling'
    [ -x /usr/clearos/apps/graphical_console/deploy/uninstall ] && /usr/clearos/apps/graphical_console/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/graphical_console/controllers
/usr/clearos/apps/graphical_console/htdocs
/usr/clearos/apps/graphical_console/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/graphical_console/packaging
%exclude /usr/clearos/apps/graphical_console/tests
%dir /usr/clearos/apps/graphical_console
/usr/clearos/apps/graphical_console/deploy
/usr/clearos/apps/graphical_console/language
/usr/clearos/apps/graphical_console/libraries
/var/lib/clearconsole/.xinitrc
/var/lib/clearconsole/.Xdefaults
