
Name: app-smtp
Group: ClearOS/Apps
Version: 6.0
Release: 0.2%{dist}
Summary: SMTP server and gateway
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
SMTP description blah blah blah...

%package core
Summary: SMTP server and gateway - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: postfix >= 2.6.6

%description core
SMTP description blah blah blah...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/smtp
cp -r * %{buildroot}/usr/clearos/apps/smtp/


%post
logger -p local6.notice -t installer 'app-smtp - installing'

%post core
logger -p local6.notice -t installer 'app-smtp-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/smtp/deploy/install ] && /usr/clearos/apps/smtp/deploy/install
fi

[ -x /usr/clearos/apps/smtp/deploy/upgrade ] && /usr/clearos/apps/smtp/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-smtp - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-smtp-core - uninstalling'
    [ -x /usr/clearos/apps/smtp/deploy/uninstall ] && /usr/clearos/apps/smtp/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/smtp/controllers
/usr/clearos/apps/smtp/htdocs
/usr/clearos/apps/smtp/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/smtp/packaging
%exclude /usr/clearos/apps/smtp/tests
%dir /usr/clearos/apps/smtp
/usr/clearos/apps/smtp/deploy
/usr/clearos/apps/smtp/language
/usr/clearos/apps/smtp/libraries
