
Name: app-imap
Group: ClearOS/Apps
Version: 5.9.9.0
Release: 1%{dist}
Summary: POP and IMAP Server
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
The POP and IMAP servers provide standard messaging... blah blah blah.

%package core
Summary: POP and IMAP Server - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: cyrus-imapd >= 2.3.16

%description core
The POP and IMAP servers provide standard messaging... blah blah blah.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/imap
cp -r * %{buildroot}/usr/clearos/apps/imap/


%post
logger -p local6.notice -t installer 'app-imap - installing'

%post core
logger -p local6.notice -t installer 'app-imap-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/imap/deploy/install ] && /usr/clearos/apps/imap/deploy/install
fi

[ -x /usr/clearos/apps/imap/deploy/upgrade ] && /usr/clearos/apps/imap/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-imap - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-imap-core - uninstalling'
    [ -x /usr/clearos/apps/imap/deploy/uninstall ] && /usr/clearos/apps/imap/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/imap/controllers
/usr/clearos/apps/imap/htdocs
/usr/clearos/apps/imap/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/imap/packaging
%exclude /usr/clearos/apps/imap/tests
%dir /usr/clearos/apps/imap
/usr/clearos/apps/imap/deploy
/usr/clearos/apps/imap/language
/usr/clearos/apps/imap/libraries
