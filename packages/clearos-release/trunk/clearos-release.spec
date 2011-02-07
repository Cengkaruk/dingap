#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: clearos-release
Version: 6.0
Release: 0.4%{dist}
Summary: ClearOS product release information
License: Copyright ClearFoundation
Group: ClearOS/Core
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
Requires: coreutils
Requires: sed
Requires: util-linux-ng
Requires: rpm
Provides: system-logos
Provides: redhat-logos
Provides: redhat-release
Provides: redhat-release-server
Provides: centos-release
Obsoletes: redhat-logos
Obsoletes: redhat-release
Obsoletes: redhat-release-server
Obsoletes: centos-release
Obsoletes: system-release
Obsoletes: clearos-enterprise-release
Obsoletes: app-release
BuildArch: noarch 
BuildRoot: %_tmppath/%name-%version-buildroot

%description
ClearOS product release information

#------------------------------------------------------------------------------
# B U I L D
#------------------------------------------------------------------------------

%prep
%setup
%build

#------------------------------------------------------------------------------
# I N S T A L L  F I L E S
#------------------------------------------------------------------------------

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

mkdir -p -m 755 $RPM_BUILD_ROOT/boot/grub
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/pki/rpm-gpg
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/yum.repos.d
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/clearos/release

# Software GPG keys and SSL config
install -m 644 rpm-gpg/clearos-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 rpm-gpg/pointclark-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 rpm-gpg/RPM-GPG-KEY-CentOS-5 $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 custom/product-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg

# Software repository
install -m 0644 custom/base.repo $RPM_BUILD_ROOT/etc/yum.repos.d/

# Product marks
install -m 644 custom/logo.png $RPM_BUILD_ROOT/usr/share/clearos/release/
install -m 755 custom/upgrade $RPM_BUILD_ROOT/usr/share/clearos/release/
install -m 644 custom/product $RPM_BUILD_ROOT/usr/share/clearos/release/

# Boot splash
install -m 644 custom/splash.xpm.gz $RPM_BUILD_ROOT/boot/grub/

# /etc/issue and /etc/issue.net
install -m 644 custom/issue $RPM_BUILD_ROOT/etc/issue
install -m 644 custom/issue $RPM_BUILD_ROOT/etc/issue.net

# Upstream release files
mkdir -p $RPM_BUILD_ROOT/etc
install -m 644 custom/release $RPM_BUILD_ROOT/etc/release
echo "ClearOS release %{version}" > $RPM_BUILD_ROOT/etc/system-release
echo "ClearOS release %{version}" > $RPM_BUILD_ROOT/usr/share/clearos/release/base-release

#------------------------------------------------------------------------------
# P R E P  S C R I P T
#------------------------------------------------------------------------------

%pre

if [ -e /etc/release ]; then
	logger -p local6.notice -t installer "app-release - found previous release file"
	cp /etc/release /usr/share/clearos/release/clearos-release.previous
fi

#------------------------------------------------------------------------------
# I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%post
logger -p local6.notice -t installer "app-release - installing"

# Always run GPG import
#----------------------

rpm --import /etc/pki/rpm-gpg/clearos-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/product-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/pointclark-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-5 2>/dev/null

if [ $1 == 1 ]; then
	logger -p local6.notice -t installer "app-release - running setup"

	# Reset registration when migrating from old products
	#----------------------------------------------------

	if ( [ -e /usr/share/clearos/release/clearos-release.previous ] && [ -e /etc/release ] ); then
		OLDPRODUCT=`/bin/sed -e 's/ release.*//' /usr/share/clearos/release/clearos-release.previous`
		NEWPRODUCT=`/bin/sed -e 's/ release.*//' /etc/release`
		OLDVERSION=`/bin/sed -e 's/.*release //' /usr/share/clearos/release/clearos-release.previous`
		NEWVERSION=`/bin/sed -e 's/.*release //' /etc/release`
		OLDBASEPRODUCT=`echo "$OLDPRODUCT" | /bin/sed 's/ .*//'`

		logger -p local6.notice -t installer "app-release - found install $OLDPRODUCT $OLDVERSION"

		if [ "$OLDBASEPRODUCT" == "ClarkConnect" ]; then
			logger -p local6.notice -t installer "app-release - detected product change, resetting registration"
			rm -f /var/lib/rbs/backup-history.data /var/lib/rbs/session-history.data
		fi
	else
		logger -p local6.notice -t installer "app-release - detected new install"
	fi

	# Run product script
	#--------------------

	/usr/share/clearos/release/upgrade >/dev/null 2>&1
fi

exit 0

#------------------------------------------------------------------------------
# U N I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%preun
if [ "$1" = 0 ]; then
	logger -p local6.notice -t installer "app-release - uninstalling"
fi

#------------------------------------------------------------------------------
# C L E A N  U P
#------------------------------------------------------------------------------

%clean
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

#------------------------------------------------------------------------------
# F I L E S
#------------------------------------------------------------------------------

%files
%defattr(-,root,root)
/boot/grub/splash.xpm.gz
/etc/issue
/etc/issue.net
/etc/release
/etc/system-release
%dir /etc/pki/rpm-gpg
/etc/pki/rpm-gpg
/etc/yum.repos.d/base.repo
%dir /usr/share/clearos/release
/usr/share/clearos/release/base-release
/usr/share/clearos/release/logo.png
/usr/share/clearos/release/product
/usr/share/clearos/release/upgrade
