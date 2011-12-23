
Name: app-ssh-server
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: SSH Server
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
SSH is a network protocol for allowing remote access to the systems Command Line Interface, or CLI.

%package core
Summary: SSH Server - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-network-core
Requires: openssh-server >= 5.3p1

%description core
SSH is a network protocol for allowing remote access to the systems Command Line Interface, or CLI.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/ssh_server
cp -r * %{buildroot}/usr/clearos/apps/ssh_server/

install -d -m 0755 %{buildroot}/var/clearos/ssh_server
install -D -m 0644 packaging/sshd.php %{buildroot}/var/clearos/base/daemon/sshd.php

%post
logger -p local6.notice -t installer 'app-ssh-server - installing'

%post core
logger -p local6.notice -t installer 'app-ssh-server-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/ssh_server/deploy/install ] && /usr/clearos/apps/ssh_server/deploy/install
fi

[ -x /usr/clearos/apps/ssh_server/deploy/upgrade ] && /usr/clearos/apps/ssh_server/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ssh-server - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-ssh-server-core - uninstalling'
    [ -x /usr/clearos/apps/ssh_server/deploy/uninstall ] && /usr/clearos/apps/ssh_server/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/ssh_server/controllers
/usr/clearos/apps/ssh_server/htdocs
/usr/clearos/apps/ssh_server/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/ssh_server/packaging
%exclude /usr/clearos/apps/ssh_server/tests
%dir /usr/clearos/apps/ssh_server
%dir /var/clearos/ssh_server
/usr/clearos/apps/ssh_server/deploy
/usr/clearos/apps/ssh_server/language
/usr/clearos/apps/ssh_server/libraries
/var/clearos/base/daemon/sshd.php
