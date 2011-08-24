
Name: app-shell-extension-core
Group: ClearOS/Libraries
Version: 5.9.9.5
Release: 1%{dist}
Summary: Login Shell Extension - APIs and install
License: LGPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: app-shell-extension-%{version}.tar.gz
Buildarch: noarch
Requires: app-base-core
Requires: app-openldap-directory-core

%description
Login Shell Extension description...

This package provides the core API and libraries.

%prep
%setup -q -n app-shell-extension-%{version}
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/shell_extension
cp -r * %{buildroot}/usr/clearos/apps/shell_extension/

install -D -m 0644 packaging/shell.php %{buildroot}/var/clearos/openldap_directory/extensions/10_shell.php

%post
logger -p local6.notice -t installer 'app-shell-extension-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/shell_extension/deploy/install ] && /usr/clearos/apps/shell_extension/deploy/install
fi

[ -x /usr/clearos/apps/shell_extension/deploy/upgrade ] && /usr/clearos/apps/shell_extension/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-shell-extension-core - uninstalling'
    [ -x /usr/clearos/apps/shell_extension/deploy/uninstall ] && /usr/clearos/apps/shell_extension/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
%exclude /usr/clearos/apps/shell_extension/packaging
%exclude /usr/clearos/apps/shell_extension/tests
%dir /usr/clearos/apps/shell_extension
/usr/clearos/apps/shell_extension/deploy
/usr/clearos/apps/shell_extension/language
/usr/clearos/apps/shell_extension/libraries
/var/clearos/openldap_directory/extensions/10_shell.php
