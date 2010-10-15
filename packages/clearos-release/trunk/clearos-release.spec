#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: clearos-release
Version: 6.0
Release: 0.3%{dist}
Summary: ClearOS product release information
License: Copyright ClearFoundation
Group: Applications/Modules
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
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/system
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/pki/rpm-gpg
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/yum.repos.d
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/system/modules/release

# Software GPG keys and SSL config
install -m 644 rpm-gpg/clearos-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 rpm-gpg/pointclark-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 rpm-gpg/RPM-GPG-KEY-CentOS-5 $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 custom/product-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 600 custom/openssl.cnf $RPM_BUILD_ROOT/etc/system/

# Software repository
install -m 0644 custom/base.repo $RPM_BUILD_ROOT/etc/yum.repos.d/

# Product marks
install -m 644 custom/default-web.html $RPM_BUILD_ROOT/usr/share/system/modules/release/
install -m 644 custom/logo.png $RPM_BUILD_ROOT/usr/share/system/modules/release/
install -m 755 custom/upgrade $RPM_BUILD_ROOT/usr/share/system/modules/release/

# Product info
install -m 644 custom/product $RPM_BUILD_ROOT/etc/system/product

# Boot splash
install -m 644 custom/splash.xpm.gz $RPM_BUILD_ROOT/boot/grub/

# /etc/issue and /etc/issue.net
install -m 644 custom/issue $RPM_BUILD_ROOT/etc/issue
install -m 644 custom/issue $RPM_BUILD_ROOT/etc/issue.net

# Upstream release files
mkdir -p $RPM_BUILD_ROOT/etc
install -m 644 custom/release $RPM_BUILD_ROOT/etc/release
echo "ClearOS release %{version}" > $RPM_BUILD_ROOT/etc/clearos-release
echo "ClearOS release %{version}" > $RPM_BUILD_ROOT/etc/system-release

#------------------------------------------------------------------------------
# P R E P  S C R I P T
#------------------------------------------------------------------------------

%pre

if [ -e /etc/release ]; then
	logger -p local6.notice -t installer "app-release - found previous release file"
	cp /etc/release /usr/share/system/settings/release.previous
fi

# Set file to indicate that this was a pre-5.0 system.
# /etc/cron.d/servicewatch just happens to be a file that has been around for
# a long time, but no longer exists in 5.0.
if [ -e /etc/cron.d/servicewatch ]; then
	logger -p local6.notice -t installer "app-release - detected pre-5.0 release"
	touch /etc/system/pre5x
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

	if ( [ -e /usr/share/system/settings/release.previous ] && [ -e /etc/release ] ); then
		OLDPRODUCT=`/bin/sed -e 's/ release.*//' /usr/share/system/settings/release.previous`
		NEWPRODUCT=`/bin/sed -e 's/ release.*//' /etc/release`
		OLDVERSION=`/bin/sed -e 's/.*release //' /usr/share/system/settings/release.previous`
		NEWVERSION=`/bin/sed -e 's/.*release //' /etc/release`
		OLDBASEPRODUCT=`echo "$OLDPRODUCT" | /bin/sed 's/ .*//'`
		LOGSTAMP=`date "+%b-%d-%Y %T"`

		logger -p local6.notice -t installer "app-release - found install $OLDPRODUCT $OLDVERSION"

		echo "$LOGSTAMP: $OLDPRODUCT $OLDVERSION => $NEWPRODUCT $NEWVERSION" >> /usr/share/system/modules/release/upgrade.log

		if [ "$OLDBASEPRODUCT" == "ClarkConnect" ]; then
			logger -p local6.notice -t installer "app-release - detected product change, resetting registration"
			rm -f /usr/share/system/modules/services/registered 2>/dev/null
			rm -f /var/webconfig/tmp/sess_* 2>/dev/null
			rm -f /var/lib/rbs/backup-history.data /var/lib/rbs/session-history.data
		fi
	else
		logger -p local6.notice -t installer "app-release - detected new install"
	fi

	# Run product script
	#--------------------

	/usr/share/system/modules/release/upgrade >/dev/null 2>&1

	# Add web server start page and logo
	#-----------------------------------

	[ ! -d /var/www/html ] && mkdir -p /var/www/html

	if [ ! -f /var/www/html/logo.png ]; then
		logger -p local6.notice -t installer "app-release - adding default web server logo"
		cp /usr/share/system/modules/release/logo.png /var/www/html/logo.png
	fi

	if [ ! -f /var/www/html/index.html ]; then
		logger -p local6.notice -t installer "app-release - updating default web page"
		cp /usr/share/system/modules/release/default-web.html /var/www/html/index.html
	fi
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
/etc/clearos-release
/etc/system-release
/etc/release
%dir /etc/system
/etc/system/product
/etc/system/openssl.cnf
%dir /etc/pki/rpm-gpg
/etc/pki/rpm-gpg
/usr/share/system/modules/release/default-web.html
/usr/share/system/modules/release/logo.png
/usr/share/system/modules/release/upgrade
/etc/yum.repos.d/base.repo
