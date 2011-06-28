# Anaconda looks here for images
%define anaconda_image_prefix /usr/lib/anaconda-runtime

Name: clearos-logos
Summary: ClearOS-related icons and pictures
Version: 60.0.14
Release: 1%{?dist}
Group: System Environment/Base
# No upstream, do in dist-cvs
Source0: clearos-logos-%{version}.tar.gz

License: Copyright 2011 ClearFoundation.  All rights reserved.
URL: http://www.clearfoundation.com
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch: noarch
Conflicts: anaconda-images <= 10
Provides: system-logos = %{version}-%{release}
Obsoletes: redhat-logos
Obsoletes: desktop-backgrounds-basic <= 60.0.1-1.el6
Provides: desktop-backgrounds-basic = %{version}-%{release}
Requires(post): coreutils

%description
Licensed only for approved usage.

%prep
%setup -q

%build

%install
rm -rf $RPM_BUILD_ROOT

# should be ifarch i386
mkdir -p $RPM_BUILD_ROOT/boot/grub
install -p -m 644 -D bootloader/splash.xpm.gz $RPM_BUILD_ROOT/boot/grub/splash.xpm.gz
# end i386 bits

mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
for i in pixmaps/* ; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/plymouth/themes/rings
for i in plymouth/rings/* ; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/plymouth/themes/rings
done

%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-, root, root, -)
%{_datadir}/plymouth/themes/rings/
%{_datadir}/pixmaps/*
/boot/grub/splash.xpm.gz

%changelog
* Mon Mar 21 2011 ClearFoundaiton <info@clearfoundation.com> 60.0.14-1.1
- Import based on spec from upstream
