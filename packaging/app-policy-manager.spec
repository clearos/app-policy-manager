
Name: app-policy-manager
Epoch: 1
Version: 1.5.10
Release: 1%{dist}
Summary: Policy Manager
License: Proprietary
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
The Policy Manager app provides the engine for deploying specific app policies to specific groups.

%package core
Summary: Policy Manager - Core
License: Proprietary
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-groups-core >= 1:1.4.22
Requires: app-openldap-core >= 1:1.4.70
Requires: app-ldap-core

%description core
The Policy Manager app provides the engine for deploying specific app policies to specific groups.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/policy_manager
cp -r * %{buildroot}/usr/clearos/apps/policy_manager/


%post
logger -p local6.notice -t installer 'app-policy-manager - installing'

%post core
logger -p local6.notice -t installer 'app-policy-manager-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/policy_manager/deploy/install ] && /usr/clearos/apps/policy_manager/deploy/install
fi

[ -x /usr/clearos/apps/policy_manager/deploy/upgrade ] && /usr/clearos/apps/policy_manager/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-policy-manager - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-policy-manager-core - uninstalling'
    [ -x /usr/clearos/apps/policy_manager/deploy/uninstall ] && /usr/clearos/apps/policy_manager/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/policy_manager/controllers
/usr/clearos/apps/policy_manager/htdocs
/usr/clearos/apps/policy_manager/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/policy_manager/packaging
%exclude /usr/clearos/apps/policy_manager/tests
%dir /usr/clearos/apps/policy_manager
/usr/clearos/apps/policy_manager/deploy
/usr/clearos/apps/policy_manager/language
/usr/clearos/apps/policy_manager/libraries
