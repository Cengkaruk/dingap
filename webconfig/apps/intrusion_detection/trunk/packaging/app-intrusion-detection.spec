
Name: app-intrusion-detection
Group: ClearOS/Apps
Version: 5.9.9.1
Release: 1%{dist}
Summary: Intrusion detection summary..
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
Intrusion detection long description...

%package core
Summary: Intrusion detection summary.. - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: snort >= 2.9.0.4
Requires: snort-gpl-rules

%description core
Intrusion detection long description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/intrusion_detection
cp -r * %{buildroot}/usr/clearos/apps/intrusion_detection/

install -d -m 0755 %{buildroot}/var/clearos/intrusion_detection

%post
logger -p local6.notice -t installer 'app-intrusion-detection - installing'

%post core
logger -p local6.notice -t installer 'app-intrusion-detection-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/intrusion_detection/deploy/install ] && /usr/clearos/apps/intrusion_detection/deploy/install
fi

[ -x /usr/clearos/apps/intrusion_detection/deploy/upgrade ] && /usr/clearos/apps/intrusion_detection/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-intrusion-detection - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-intrusion-detection-core - uninstalling'
    [ -x /usr/clearos/apps/intrusion_detection/deploy/uninstall ] && /usr/clearos/apps/intrusion_detection/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/intrusion_detection/controllers
/usr/clearos/apps/intrusion_detection/htdocs
/usr/clearos/apps/intrusion_detection/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/intrusion_detection/packaging
%exclude /usr/clearos/apps/intrusion_detection/tests
%dir /usr/clearos/apps/intrusion_detection
%dir /var/clearos/intrusion_detection
/usr/clearos/apps/intrusion_detection/deploy
/usr/clearos/apps/intrusion_detection/language
/usr/clearos/apps/intrusion_detection/libraries
