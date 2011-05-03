Name: clearos-console
Version: 5.9.9.0
Release: 1%{dist}
Summary: Administration console module
License: GPLv3 or later
Group: ClearOS/Core
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
Requires: clearos-base
Requires: iptraf
Requires: kbd
Requires: tconsole
Requires: upstart
BuildRoot: %_tmppath/%name-%version-buildroot

%description
Administration console module

%prep
%setup
%build

%install
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/init
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/bin
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/sbin
mkdir -p -m 755 $RPM_BUILD_ROOT/var/lib/clearconsole

install -m 644 bash_profile $RPM_BUILD_ROOT/var/lib/clearconsole/.bash_profile
install -m 644 clearos-console.conf $RPM_BUILD_ROOT/etc/init/

%pre
getent group clearconsole >/dev/null || groupadd -r clearconsole
getent passwd clearconsole >/dev/null || \
    useradd -r -g clearconsole -d /var/lib/clearconsole/ -s /bin/bash \
    -c "Console" clearconsole
exit 0

%post

# Add sudoers stuff
#------------------

CHECK=`grep "^Cmnd_Alias CLEARCONSOLE =" /etc/sudoers`
if [ -z "$CHECK" ]; then
	echo "Cmnd_Alias CLEARCONSOLE = /usr/bin/iptraf, /usr/sbin/console_start, /usr/sbin/tc-yum, /bin/rpm, /sbin/halt, /sbin/reboot" >> /etc/sudoers
fi

CHECK=`grep "^clearconsole[[:space:]]*" /etc/sudoers`
if [ -z "$CHECK" ]; then
	echo "clearconsole ALL=NOPASSWD: CLEARCONSOLE" >> /etc/sudoers
fi

/usr/sbin/addsudo /sbin/halt clearos-console
/usr/sbin/addsudo /sbin/reboot clearos-console

# Remove old consoles
#--------------------

CHECK=`grep "/usr/sbin/launcher" /etc/inittab 2>/dev/null`
if [ -n "$CHECK" ]; then
    grep -v "/usr/sbin/launcher" /etc/inittab > /etc/inittab.new
    mv /etc/inittab.new /etc/inittab 
	sleep 1
	initctl reload-configuration >/dev/null 2>&1
	killall -q launcher >/dev/null 2>&1
fi

CHECK=`grep "/usr/sbin/console" /etc/inittab 2>/dev/null`
if [ -n "$CHECK" ]; then
    grep -v "/usr/sbin/launcher" /etc/inittab > /etc/inittab.new
    mv /etc/inittab.new /etc/inittab 
	sleep 1
	initctl reload-configuration >/dev/null 2>&1
	killall -q lynx >/dev/null 2>&1
fi

# Install new console
#--------------------

CHECK=`grep "ACTIVE_CONSOLES=\/dev\/tty\[1-6\]" /etc/sysconfig/init 2>/dev/null`
if [ -n "$CHECK" ]; then
    sed -i -e 's/ACTIVE_CONSOLES=\/dev\/tty\[1-6\]/ACTIVE_CONSOLES=\/dev\/tty\[2-6\]/' /etc/sysconfig/init
fi

exit 0

%preun
if [ $1 -eq 0 ]; then
    CHECK=`grep "ACTIVE_CONSOLES=\/dev\/tty\[2-6\]" /etc/sysconfig/init 2>/dev/null`
	if [ -n "$CHECK" ]; then
        sed -i -e 's/ACTIVE_CONSOLES=\/dev\/tty\[2-6\]/ACTIVE_CONSOLES=\/dev\/tty\[1-6\]/' /etc/sysconfig/init
		sleep 1
        initctl reload-configuration >/dev/null 2>&1
		killall X >/dev/null 2>&1
	fi	
fi

exit 0

%files
%defattr(-,root,root)
/etc/init/clearos-console.conf
/var/lib/clearconsole/.bash_profile
%dir %attr(-,clearconsole,root) /var/lib/clearconsole
