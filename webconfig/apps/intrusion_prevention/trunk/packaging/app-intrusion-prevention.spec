
Name: app-intrusion-prevention
Group: ClearOS/Apps
Version: 5.9.9.5
Release: 1%{dist}
Summary: Intrusion Prevention
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-intrusion-detection
Requires: app-network

%description
Intrusion Prevention actively monitors network traffic and blocks unwanted traffic before it can harm your network.

%package core
Summary: Intrusion Prevention - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: app-intrusion-detection-core
Requires: snort >= 2.9.0.4

%description core
Intrusion Prevention actively monitors network traffic and blocks unwanted traffic before it can harm your network.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/intrusion_prevention
cp -r * %{buildroot}/usr/clearos/apps/intrusion_prevention/

install -D -m 0644 packaging/snortsam.php %{buildroot}/var/clearos/base/daemon/snortsam.php

%post
logger -p local6.notice -t installer 'app-intrusion-prevention - installing'

%post core
logger -p local6.notice -t installer 'app-intrusion-prevention-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/intrusion_prevention/deploy/install ] && /usr/clearos/apps/intrusion_prevention/deploy/install
fi

[ -x /usr/clearos/apps/intrusion_prevention/deploy/upgrade ] && /usr/clearos/apps/intrusion_prevention/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-intrusion-prevention - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-intrusion-prevention-core - uninstalling'
    [ -x /usr/clearos/apps/intrusion_prevention/deploy/uninstall ] && /usr/clearos/apps/intrusion_prevention/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/intrusion_prevention/controllers
/usr/clearos/apps/intrusion_prevention/htdocs
/usr/clearos/apps/intrusion_prevention/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/intrusion_prevention/packaging
%exclude /usr/clearos/apps/intrusion_prevention/tests
%dir /usr/clearos/apps/intrusion_prevention
/usr/clearos/apps/intrusion_prevention/deploy
/usr/clearos/apps/intrusion_prevention/language
/usr/clearos/apps/intrusion_prevention/libraries
/var/clearos/base/daemon/snortsam.php
