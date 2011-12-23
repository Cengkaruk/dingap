
Name: app-account-import
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Account Import
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-users
Requires: app-groups

%description
Perform bulk import of users to the system using the Account Import/Export app.

%package core
Summary: Account Import - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core

%description core
Perform bulk import of users to the system using the Account Import/Export app.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/account_import
cp -r * %{buildroot}/usr/clearos/apps/account_import/

install -d -m 755 %{buildroot}/var/clearos/account_import
install -D -m 0755 packaging/account-import %{buildroot}/usr/sbin/account-import

%post
logger -p local6.notice -t installer 'app-account-import - installing'

%post core
logger -p local6.notice -t installer 'app-account-import-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/account_import/deploy/install ] && /usr/clearos/apps/account_import/deploy/install
fi

[ -x /usr/clearos/apps/account_import/deploy/upgrade ] && /usr/clearos/apps/account_import/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-account-import - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-account-import-core - uninstalling'
    [ -x /usr/clearos/apps/account_import/deploy/uninstall ] && /usr/clearos/apps/account_import/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/account_import/controllers
/usr/clearos/apps/account_import/htdocs
/usr/clearos/apps/account_import/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/account_import/packaging
%exclude /usr/clearos/apps/account_import/tests
%dir /usr/clearos/apps/account_import
%dir %attr(755,webconfig,webconfig) /var/clearos/account_import
/usr/clearos/apps/account_import/deploy
/usr/clearos/apps/account_import/language
/usr/clearos/apps/account_import/libraries
/usr/sbin/account-import
