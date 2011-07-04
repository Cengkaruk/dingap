Name: clearos-release
Version: 6.0.0.2
Release: 2%{?dist}
Summary: ClearOS product release information
Group: System Environment/Base
License: GPL
Source: %{name}-%{version}.tar.gz
Requires: coreutils
Requires: sed
Requires: util-linux-ng
Requires: rpm
Provides: redhat-release system-release
Provides: redhat-release-server
Provides: redhat-release-client
Provides: redhat-release-workstation
Provides: redhat-release-computenode
Provides: centos-release
Obsoletes: redhat-release system-release
Obsoletes: redhat-release-server
Obsoletes: redhat-release-client
Obsoletes: redhat-release-workstation
Obsoletes: redhat-release-computenode
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

# create /etc/product, /etc/clearos-release and /etc/system-release
NAME=`grep ^name[[:space:]] config/product | sed 's/.* = //'`
VERSION=`grep ^version[[:space:]] config/product | sed 's/.* = //'`

install -m 644 config/product $RPM_BUILD_ROOT/etc/

echo "$NAME release $VERSION" > $RPM_BUILD_ROOT/etc/clearos-release
ln -s clearos-release $RPM_BUILD_ROOT/etc/redhat-release
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

# RPM macros
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/rpm
install -m 644 config/macros.dist $RPM_BUILD_ROOT/etc/rpm/macros.dist

# Software repository
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/yum.repos.d
install -m 0644 config/base.repo $RPM_BUILD_ROOT/etc/yum.repos.d/

# Product marks
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/clearos/release/
install -m 755 config/upgrade $RPM_BUILD_ROOT/usr/share/clearos/release/ 

%post
rpm --import /etc/pki/rpm-gpg/clearos-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/product-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/pointclark-gpg-key 2>/dev/null
rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-5 2>/dev/null

/usr/share/clearos/release/upgrade >/dev/null 2>&1

exit 0

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
/etc/issue
/etc/issue.net
/etc/clearos-release
/etc/product
/etc/redhat-release
/etc/system-release
%dir /etc/pki/rpm-gpg
/etc/pki/rpm-gpg
/etc/rpm/macros.dist
/etc/yum.repos.d/base.repo
%dir /usr/share/clearos/release
/usr/share/clearos/release/upgrade

%changelog
* Mon Jun 27 2011 ClearFoundation <developer@clearfoundation.com> - 6.0.0.2-1
- Prepped for alpha 1 build
