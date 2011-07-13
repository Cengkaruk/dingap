
Name: app-contact-extension-core
Group: ClearOS/Libraries
Version: 5.9.9.3
Release: 1%{dist}
Summary: Contact Account Extension - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-contact-extension-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: app-openldap-directory-core
Requires: app-organization

%description
Contact account extension description ... blah blah blah.

This package provides the core API and libraries.

%prep
%setup -q -n app-contact-extension-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/contact_extension
cp -r * %{buildroot}/usr/clearos/apps/contact_extension/

install -D -m 0644 packaging/contact.php %{buildroot}/var/clearos/openldap_directory/extensions/contact.php

%post
logger -p local6.notice -t installer 'app-contact-extension-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/contact_extension/deploy/install ] && /usr/clearos/apps/contact_extension/deploy/install
fi

[ -x /usr/clearos/apps/contact_extension/deploy/upgrade ] && /usr/clearos/apps/contact_extension/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-contact-extension-core - uninstalling'
    [ -x /usr/clearos/apps/contact_extension/deploy/uninstall ] && /usr/clearos/apps/contact_extension/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/contact_extension/packaging
%exclude /usr/clearos/apps/contact_extension/tests
%dir /usr/clearos/apps/contact_extension
/usr/clearos/apps/contact_extension/deploy
/usr/clearos/apps/contact_extension/language
/usr/clearos/apps/contact_extension/libraries
/var/clearos/openldap_directory/extensions/contact.php
