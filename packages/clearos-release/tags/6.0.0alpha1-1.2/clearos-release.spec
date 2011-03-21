Name: clearos-release
Version: 6.0.0alpha1
Release: 1.2%{?dist}
Summary: ClearOS product release information
Group: System Environment/Base
License: GPLv2
Source: %{name}-%{version}.tar.gz
Requires: coreutils
Requires: sed
Requires: util-linux-ng
Requires: rpm
Provides: redhat-release system-release
Provides: redhat-release-server
Provides: centos-release
Obsoletes: redhat-release system-release
Obsoletes: redhat-release-server
Obsoletes: centos-release
Obsoletes: app-release
BuildArch: noarch 
BuildRoot: %_tmppath/%name-%version-buildroot

%description
ClearOS product release information

%prep
%setup

%build

%install
rm -rf $RPM_BUILD_ROOT

# create /etc
mkdir -p $RPM_BUILD_ROOT/etc

# create /etc/system-release and /etc/clearos-release
install -m 644 config/release $RPM_BUILD_ROOT/etc/clearos-release
ln -s clearos-release $RPM_BUILD_ROOT/etc/system-release

# create /etc/issue and /etc/issue.net
cp $RPM_BUILD_ROOT/etc/clearos-release $RPM_BUILD_ROOT/etc/issue
echo "Kernel \r on an \m" >> $RPM_BUILD_ROOT/etc/issue
cp $RPM_BUILD_ROOT/etc/issue $RPM_BUILD_ROOT/etc/issue.net
echo >> $RPM_BUILD_ROOT/etc/issue

# copy GPG keys
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 rpm-gpg/clearos-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 rpm-gpg/pointclark-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 rpm-gpg/RPM-GPG-KEY-CentOS-5 $RPM_BUILD_ROOT/etc/pki/rpm-gpg
install -m 644 config/product-gpg-key $RPM_BUILD_ROOT/etc/pki/rpm-gpg

# Software repository
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/yum.repos.d
install -m 0644 config/base.repo $RPM_BUILD_ROOT/etc/yum.repos.d/

# Product marks
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/clearos/release/
install -m 755 config/upgrade $RPM_BUILD_ROOT/usr/share/clearos/release/ 
install -m 644 config/product $RPM_BUILD_ROOT/usr/share/clearos/release/


%pre
if [ -e /etc/release ]; then
    mkdir -p /usr/share/clearos/release
	cp /etc/release /usr/share/clearos/release/clearos-release.previous
fi

%post
rpm --import /etc/pki/rpm-gpg/clearos-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/product-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/pointclark-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-5 2>/dev/null

if [ $1 == 1 ]; then
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

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
/etc/issue
/etc/issue.net
/etc/clearos-release
/etc/system-release
%dir /etc/pki/rpm-gpg
/etc/pki/rpm-gpg
/etc/yum.repos.d/base.repo
%dir /usr/share/clearos/release
/usr/share/clearos/release/product
/usr/share/clearos/release/upgrade

%changelog
* Mon Mar 21 2011 ClearFoundation <developer@clearfoundation.com> - 6.0.0alpha1-1.1
- Import based on spec file from upstream
