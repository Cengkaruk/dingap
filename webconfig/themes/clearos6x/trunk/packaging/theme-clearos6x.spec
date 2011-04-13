
Name: theme-clearos6x
Group: Applications/Themes
Version: 5.9.9.0
Release: 1%{dist}
Summary: ClearOS Enterprise 6.x theme
License: Copyright 2011 ClearFoundation
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Provides: clearos-theme
Requires: util-linux-ng
Obsoletes: app-theme-clearos5x
Buildarch: noarch

%description
ClearOS Enterprise 6.x webconfig theme

%prep
%setup -q
%build


%install
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/clearos/themes/clearos6x
cp -r * $RPM_BUILD_ROOT/usr/clearos/themes/clearos6x


%files
%defattr(-,root,root)
%dir /usr/clearos/themes/clearos6x
/usr/clearos/themes/clearos6x
