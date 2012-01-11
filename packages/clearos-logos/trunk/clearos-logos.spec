# Anaconda looks here for images
%define anaconda_image_prefix /usr/lib/anaconda-runtime

Name: clearos-logos
Summary: ClearOS-related icons and pictures
Version: 60.0.14
Release: 2%{?dist}
Group: System Environment/Base
# No upstream, do in dist-cvs
Source0: clearos-logos-%{version}.tar.gz

License: Copyright 2011 ClearFoundation.  All rights reserved.
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch: noarch
Conflicts: anaconda-images <= 10
Provides: system-logos = %{version}-%{release}
Obsoletes: desktop-backgrounds-basic <= 60.0.1-1.el6
Provides: redhat-logos desktop-backgrounds-basic = %{version}-%{release}
Obsoletes: redhat-logos
Conflicts: redhat-artwork <= 5.0.5
Requires(post): coreutils
# For _kde4_appsdir macro:
BuildRequires: kde-filesystem

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

mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps/redhat
for i in redhat-pixmaps/*; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps/redhat
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/backgrounds/
for i in backgrounds/*.png backgrounds/default.xml; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/backgrounds/
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/gnome-background-properties/
install -p -m 644 backgrounds/desktop-backgrounds-default.xml $RPM_BUILD_ROOT%{_datadir}/gnome-background-properties/

mkdir -p $RPM_BUILD_ROOT%{_datadir}/firstboot/themes/RHEL
for i in firstboot/* ; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/firstboot/themes/RHEL
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
for i in pixmaps/* ; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/plymouth/themes/charge
for i in plymouth/charge/* ; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/plymouth/themes/charge
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/plymouth/themes/rings
for i in plymouth/rings/* ; do
  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/plymouth/themes/rings
done

for size in 16x16 24x24 32x32 36x36 48x48 96x96 ; do
  mkdir -p $RPM_BUILD_ROOT%{_datadir}/icons/hicolor/$size/apps
  for i in icons/hicolor/$size/apps/* ; do
    install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/icons/hicolor/$size/apps
  done
done
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}
pushd $RPM_BUILD_ROOT%{_sysconfdir}
ln -s %{_datadir}/icons/hicolor/16x16/apps/system-logo-icon.png favicon.png
popd

mkdir -p $RPM_BUILD_ROOT%{_datadir}/icons/hicolor/scalable/apps
install -p -m 644 icons/hicolor/scalable/apps/xfce4_xicon1.svg $RPM_BUILD_ROOT%{_datadir}/icons/hicolor/scalable/apps

(cd anaconda; make DESTDIR=$RPM_BUILD_ROOT install)

for i in 16 24 32 36 48 96; do
  mkdir -p $RPM_BUILD_ROOT%{_datadir}/icons/System/${i}x${i}/places
  install -p -m 644 -D $RPM_BUILD_ROOT%{_datadir}/icons/hicolor/${i}x${i}/apps/system-logo-icon.png $RPM_BUILD_ROOT%{_datadir}/icons/System/${i}x${i}/places/start-here.png
  install -p -m 644 -D $RPM_BUILD_ROOT%{_datadir}/icons/hicolor/${i}x${i}/apps/system-logo-icon.png $RPM_BUILD_ROOT%{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/${i}x${i}/places/start-here.png 
done

# ksplash theme
mkdir -p $RPM_BUILD_ROOT%{_kde4_appsdir}/ksplash/Themes/
cp -rp kde-splash/RHEL6/ $RPM_BUILD_ROOT%{_kde4_appsdir}/ksplash/Themes/
pushd $RPM_BUILD_ROOT%{_kde4_appsdir}/ksplash/Themes/RHEL6/1920x1200/
ln -s %{_datadir}/anaconda/pixmaps/splash.png splash.png
ln -s %{_datadir}/backgrounds/default.png background.png
popd

# kdm theme
mkdir -p $RPM_BUILD_ROOT/%{_kde4_appsdir}/kdm/themes/
cp -rp kde-kdm/RHEL6/ $RPM_BUILD_ROOT/%{_kde4_appsdir}/kdm/themes/
pushd $RPM_BUILD_ROOT/%{_kde4_appsdir}/kdm/themes/RHEL6/
ln -s %{_datadir}/backgrounds/default.png background.png
ln -s %{_datadir}/pixmaps/system-logo-white.png system-logo-white.png
popd

# wallpaper theme
mkdir -p $RPM_BUILD_ROOT/%{_datadir}/wallpapers/
cp -rp kde-plasma/RHEL6/ $RPM_BUILD_ROOT/%{_datadir}/wallpapers
pushd $RPM_BUILD_ROOT/%{_datadir}/wallpapers/RHEL6/contents/images
ln -s %{_datadir}/backgrounds/1920x1200_day.png 1920x1200.png
popd

%clean
rm -rf $RPM_BUILD_ROOT

%post
touch --no-create %{_datadir}/icons/hicolor || :
touch --no-create %{_datadir}/icons/System || :
touch --no-create %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE ||:
if [ -x /usr/bin/gtk-update-icon-cache ]; then
  if [ -f %{_datadir}/icons/hicolor/index.theme ]; then
    gtk-update-icon-cache %{_datadir}/icons/hicolor &> /dev/null || :
  fi
  if [ -f %{_datadir}/icons/System/index.theme ]; then
    gtk-update-icon-cache %{_datadir}/icons/System &> /dev/null || :
  fi
fi

%files
%defattr(-, root, root, -)
%config(noreplace) %{_sysconfdir}/favicon.png
%{_datadir}/backgrounds/*
%{_datadir}/gnome-background-properties/*
%{_datadir}/firstboot/themes/RHEL/
%{_datadir}/plymouth/themes/charge/
%{_datadir}/plymouth/themes/rings/
%{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/*/places/*
%{_datadir}/pixmaps/*
%{_datadir}/anaconda/pixmaps/*
%{_datadir}/icons/hicolor/*/apps/*
%{_datadir}/icons/System/*/places/*

%{anaconda_image_prefix}/boot/*png
%{anaconda_image_prefix}/*.sh
%{anaconda_image_prefix}/*.jpg
%{_kde4_appsdir}/ksplash/Themes/RHEL6/
%{_kde4_appsdir}/kdm/themes/RHEL6/
%{_kde4_datadir}/wallpapers/RHEL6/

# we multi-own these directories, so as not to require the packages that
# provide them, thereby dragging in excess dependencies.
%dir %{_datadir}/icons/hicolor
%dir %{anaconda_image_prefix}
%dir %{anaconda_image_prefix}/boot
%dir %{_datadir}/anaconda
%dir %{_datadir}/anaconda/pixmaps/
%dir %{_datadir}/kde-settings
%dir %{_datadir}/kde-settings/kde-profile
%dir %{_datadir}/kde-settings/kde-profile/default
%dir %{_datadir}/kde-settings/kde-profile/default/share
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/16x16
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/16x16/places
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/24x24
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/24x24/places
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/32x32
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/32x32/places
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/36x36
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/36x36/places
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/48x48
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/48x48/places
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/96x96
%dir %{_datadir}/kde-settings/kde-profile/default/share/icons/Fedora-KDE/96x96/places
%dir %{_kde4_appsdir}
# should be ifarch i386
/boot/grub/splash.xpm.gz
# end i386 bits

%changelog
* Fri Jul 15 2011 Shad L. Lords <slords@clearfoundation.com> - 60.0.14-2
- Initial build for ClearOS Enterprise 6.1
