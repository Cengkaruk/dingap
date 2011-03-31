%define enginedir /usr/clearos/webconfig/

Name: webconfig-utils
Version: 5.9.9.0
Release: 1%dist
Group: Applications/Modules
Summary: Web-based administration tool core
Source: %{name}-%{version}.tar.gz
Requires: coreutils
Requires: grep
Requires: initscripts
Requires: sudo
Requires: webconfig-php
BuildRequires: webconfig-php-devel
Obsoletes: cc-webconfig-engine
License: GPL
BuildRoot: /tmp/%{name}-%{version}-build

%description
Web-based administration tool core

%prep
%setup -q
%build
rm -rf $RPM_BUILD_ROOT

# Helper tools
cd utils
gcc -O2 app-rename.c -o app-rename
gcc -lcrypt -O2 app-passwd.c -o app-passwd
gcc -O2 app-realpath.c -o app-realpath

cd ../php-ifconfig
%configure \
	--prefix=%{enginedir} \
	--with-php-config=%{enginedir}%{_bindir}/php-config \
	--enable-ifconfig
make

cd ../php-statvfs
%configure \
	--prefix=%{enginedir} \
	--with-php-config=%{enginedir}%{_bindir}/php-config \
	--enable-statvfs
make

%install
mkdir -p -m 755 $RPM_BUILD_ROOT%{_sbindir}
mkdir -p -m 755 $RPM_BUILD_ROOT%{enginedir}%{_libdir}/php/modules
mkdir -p -m 755 $RPM_BUILD_ROOT%{enginedir}%{_sysconfdir}/php.d

# ifconfig PHP module
cp php-ifconfig/modules/ifconfig.so $RPM_BUILD_ROOT%{enginedir}%{_libdir}/php/modules
echo "extension=ifconfig.so" > $RPM_BUILD_ROOT%{enginedir}%{_sysconfdir}/php.d/ifconfig.ini

# statvfs PHP module
cp php-statvfs/modules/statvfs.so $RPM_BUILD_ROOT%{enginedir}%{_libdir}/php/modules
echo "extension=statvfs.so" > $RPM_BUILD_ROOT%{enginedir}%{_sysconfdir}/php.d/statvfs.ini

# Helper tools
install -m 755 utils/app-passwd $RPM_BUILD_ROOT%{_sbindir}
install -m 755 utils/app-rename $RPM_BUILD_ROOT%{_sbindir}
install -m 755 utils/app-realpath $RPM_BUILD_ROOT%{_sbindir}

%post
/sbin/service webconfig condrestart >/dev/null 2>&1
exit 0

%preun
if [ $1 = 0 ]; then
	/sbin/service webconfig restart >/dev/null 2>&1
fi

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
%{_sbindir}/app-passwd
%{_sbindir}/app-rename
%{_sbindir}/app-realpath
%{enginedir}%{_libdir}/php/modules/ifconfig.so
%{enginedir}%{_libdir}/php/modules/statvfs.so
%{enginedir}%{_sysconfdir}/php.d/ifconfig.ini
%{enginedir}%{_sysconfdir}/php.d/statvfs.ini
