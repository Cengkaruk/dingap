
Name: app-certificate-manager
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Certificate Manager
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base

%description
Certificate Manager description

%package core
Summary: Certificate Manager - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-network-core
Requires: openssl >= 1.0.0

%description core
Certificate Manager description

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/certificate_manager
cp -r * %{buildroot}/usr/clearos/apps/certificate_manager/

install -D -m 0644 packaging/index.txt %{buildroot}/etc/pki/CA/index.txt
install -D -m 0644 packaging/openssl.cnf %{buildroot}/etc/pki/CA/openssl.cnf
install -D -m 0644 packaging/serial %{buildroot}/etc/pki/CA/serial

%post
logger -p local6.notice -t installer 'app-certificate-manager - installing'

%post core
logger -p local6.notice -t installer 'app-certificate-manager-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/certificate_manager/deploy/install ] && /usr/clearos/apps/certificate_manager/deploy/install
fi

[ -x /usr/clearos/apps/certificate_manager/deploy/upgrade ] && /usr/clearos/apps/certificate_manager/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-certificate-manager - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-certificate-manager-core - uninstalling'
    [ -x /usr/clearos/apps/certificate_manager/deploy/uninstall ] && /usr/clearos/apps/certificate_manager/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/certificate_manager/controllers
/usr/clearos/apps/certificate_manager/htdocs
/usr/clearos/apps/certificate_manager/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/certificate_manager/packaging
%exclude /usr/clearos/apps/certificate_manager/tests
%dir /usr/clearos/apps/certificate_manager
/usr/clearos/apps/certificate_manager/deploy
/usr/clearos/apps/certificate_manager/language
/usr/clearos/apps/certificate_manager/libraries
%config(noreplace) /etc/pki/CA/index.txt
%config(noreplace) /etc/pki/CA/openssl.cnf
%config(noreplace) /etc/pki/CA/serial
