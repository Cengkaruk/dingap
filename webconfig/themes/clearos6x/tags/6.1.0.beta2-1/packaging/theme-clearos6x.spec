Name: theme-clearos6x
Group: Applications/Themes
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: ClearOS Enterprise 6 theme
License: Copyright 2011 ClearFoundation
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Provides: system-theme
Requires: util-linux-ng
Obsoletes: app-theme-clearos5x
Buildarch: noarch

%description
ClearOS Enterprise 6 webconfig theme

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
