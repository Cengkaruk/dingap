
Name: app-language
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: Language summary
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Language long description

%package core
Summary: Language summary - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core

%description core
Language long description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/language
cp -r * %{buildroot}/usr/clearos/apps/language/


%post
logger -p local6.notice -t installer 'app-language - installing'

%post core
logger -p local6.notice -t installer 'app-language-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/language/deploy/install ] && /usr/clearos/apps/language/deploy/install
fi

[ -x /usr/clearos/apps/language/deploy/upgrade ] && /usr/clearos/apps/language/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-language - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-language-core - uninstalling'
    [ -x /usr/clearos/apps/language/deploy/uninstall ] && /usr/clearos/apps/language/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/language/controllers
/usr/clearos/apps/language/htdocs
/usr/clearos/apps/language/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/language/packaging
%exclude /usr/clearos/apps/language/tests
%dir /usr/clearos/apps/language
/usr/clearos/apps/language/deploy
/usr/clearos/apps/language/language
/usr/clearos/apps/language/libraries
