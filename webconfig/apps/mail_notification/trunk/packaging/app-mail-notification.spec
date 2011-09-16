
Name: app-mail-notification
Group: ClearOS/Apps
Version: 5.9.9.5
Release: 4%{dist}
Summary: Mail Notification
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Mail Notification description....

%package core
Summary: Mail Notification - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: postfix
Requires: Swift
Requires: app-users
Requires: app-samba-core
Requires: app-groups
Requires: app-accounts

%description core
Mail Notification description....

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/mail_notification
cp -r * %{buildroot}/usr/clearos/apps/mail_notification/

install -D -m 0755 packaging/mailer.conf %{buildroot}/etc/mailer.conf

%post
logger -p local6.notice -t installer 'app-mail-notification - installing'

%post core
logger -p local6.notice -t installer 'app-mail-notification-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/mail_notification/deploy/install ] && /usr/clearos/apps/mail_notification/deploy/install
fi

[ -x /usr/clearos/apps/mail_notification/deploy/upgrade ] && /usr/clearos/apps/mail_notification/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mail-notification - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mail-notification-core - uninstalling'
    [ -x /usr/clearos/apps/mail_notification/deploy/uninstall ] && /usr/clearos/apps/mail_notification/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/mail_notification/controllers
/usr/clearos/apps/mail_notification/htdocs
/usr/clearos/apps/mail_notification/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/mail_notification/packaging
%exclude /usr/clearos/apps/mail_notification/tests
%dir /usr/clearos/apps/mail_notification
/usr/clearos/apps/mail_notification/deploy
/usr/clearos/apps/mail_notification/language
/usr/clearos/apps/mail_notification/libraries
%config(noreplace) /etc/mailer.conf
