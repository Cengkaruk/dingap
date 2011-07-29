
Name: app-zarafa-extension-core
Group: ClearOS/Libraries
Version: 5.9.9.1
Release: 1%{dist}
Summary: Zarafa Accounts Extension - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-zarafa-extension-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: app-openldap-directory-core
Requires: app-contact-extension-core

%description
Zarafa Accounts Extension long description

This package provides the core API and libraries.

%prep
%setup -q -n app-zarafa-extension-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/zarafa_extension
cp -r * %{buildroot}/usr/clearos/apps/zarafa_extension/

install -D -m 0644 packaging/zarafa.php %{buildroot}/var/clearos/openldap_directory/extensions/10_zarafa.php

%post
logger -p local6.notice -t installer 'app-zarafa-extension-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/zarafa_extension/deploy/install ] && /usr/clearos/apps/zarafa_extension/deploy/install
fi

[ -x /usr/clearos/apps/zarafa_extension/deploy/upgrade ] && /usr/clearos/apps/zarafa_extension/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-zarafa-extension-core - uninstalling'
    [ -x /usr/clearos/apps/zarafa_extension/deploy/uninstall ] && /usr/clearos/apps/zarafa_extension/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/zarafa_extension/packaging
%exclude /usr/clearos/apps/zarafa_extension/tests
%dir /usr/clearos/apps/zarafa_extension
/usr/clearos/apps/zarafa_extension/deploy
/usr/clearos/apps/zarafa_extension/language
/usr/clearos/apps/zarafa_extension/libraries
/var/clearos/openldap_directory/extensions/10_zarafa.php
