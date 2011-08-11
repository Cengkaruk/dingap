
Name: app-pptpd-plugin-core
Group: ClearOS/Libraries
Version: 5.9.9.4
Release: 1.1%{dist}
Summary: PPTP Server Directory Plugin - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-pptpd-plugin-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: app-accounts-core
Requires: app-pptpd-core

%description
The PPTP server plugin ... blah blah blah.

This package provides the core API and libraries.

%prep
%setup -q -n app-pptpd-plugin-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/pptpd_plugin
cp -r * %{buildroot}/usr/clearos/apps/pptpd_plugin/

install -D -m 0644 packaging/pptpd.php %{buildroot}/var/clearos/accounts/plugins/pptpd.php

%post
logger -p local6.notice -t installer 'app-pptpd-plugin-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/pptpd_plugin/deploy/install ] && /usr/clearos/apps/pptpd_plugin/deploy/install
fi

[ -x /usr/clearos/apps/pptpd_plugin/deploy/upgrade ] && /usr/clearos/apps/pptpd_plugin/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-pptpd-plugin-core - uninstalling'
    [ -x /usr/clearos/apps/pptpd_plugin/deploy/uninstall ] && /usr/clearos/apps/pptpd_plugin/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/pptpd_plugin/packaging
%exclude /usr/clearos/apps/pptpd_plugin/tests
%dir /usr/clearos/apps/pptpd_plugin
/usr/clearos/apps/pptpd_plugin/deploy
/usr/clearos/apps/pptpd_plugin/language
/usr/clearos/apps/pptpd_plugin/libraries
/var/clearos/accounts/plugins/pptpd.php
