#
# Pandora FMS Server 
#
%define name        pandorafms_server
%define version     3.2rc2
%define release     1

Summary:            Pandora FMS Server
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             ArticaST <http://www.artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.pandorafms.com
Group:              System/Monitoring
Packager:           Manuel Arostegui <manuel@todo-linux.com>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-buildroot
BuildArchitectures: noarch 
Requires(pre):      /usr/sbin/useradd
AutoReq:            0
Provides:           %{name}-%{version}
Requires:           perl-DBI perl-DBD-mysql 
Requires:           perl-NetAddr-IP net-snmp net-tools
Requires:           nmap wmic sudo perl-HTML-Tree perl-XML-SAX

%description
Pandora FMS is a monitoring system for big IT environments. It uses remote tests, or local agents to grab information. Pandora supports all standard OS (Linux, AIX, HP-UX, Solaris and Windows XP,2000/2003), and support multiple setups in HA enviroments.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n pandora_server

%build

%install

rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/usr/sbin/
mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in/conf
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in/md5
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in/collections
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/
mkdir -p $RPM_BUILD_ROOT/usr/share/man/man1/

# All binaries go to /usr/bin
cp -aRf bin/pandora_server $RPM_BUILD_ROOT/usr/bin/
cp -aRf bin/pandora_exec $RPM_BUILD_ROOT/usr/bin/
cp -aRf bin/tentacle_server $RPM_BUILD_ROOT/usr/bin/

cp -aRf conf/* $RPM_BUILD_ROOT%{prefix}/pandora_server/conf/
cp -aRf util $RPM_BUILD_ROOT%{prefix}/pandora_server/
cp -aRf lib/* $RPM_BUILD_ROOT/usr/lib/perl5/
cp -aRf AUTHORS COPYING ChangeLog README $RPM_BUILD_ROOT%{prefix}/pandora_server/

cp -aRf util/pandora_server $RPM_BUILD_ROOT/etc/init.d/
cp -aRf util/tentacle_serverd $RPM_BUILD_ROOT/etc/init.d/

cp -aRf man/man1/pandora_server.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/
cp -aRf man/man1/tentacle_server.1.gz $RPM_BUILD_ROOT/usr/share/man/man1/

rm -f $RPM_BUILD_ROOT%{prefix}/pandora_server/util/PandoraFMS
rm -f $RPM_BUILD_ROOT%{prefix}/pandora_server/util/recon_scripts/PandoraFMS

%clean
rm -fr $RPM_BUILD_ROOT

%pre
/usr/sbin/useradd -d %{prefix}/pandora -s /bin/false -M -g 0 pandora
if [ -e "/etc/pandora/pandora_server.conf" ]
then
	cat /etc/pandora/pandora_server.conf > /etc/pandora/pandora_server.conf.old
fi
exit 0

%post
chkconfig pandora_server on 
chkconfig tentacle_serverd on 

echo "/usr/share/pandora_server/util/pandora_db.pl /etc/pandora/pandora_server.conf" > /etc/cron.daily/pandora_db
chmod 750 /etc/cron.daily/pandora_db
cp -aRf /usr/share/pandora_server/util/pandora_logrotate /etc/logrotate.d/pandora

if [ ! -d /etc/pandora ] ; then
   mkdir -p /etc/pandora
fi

if [ ! -e /etc/pandora/pandora_server.conf ] ; then
   ln -s /usr/share/pandora_server/conf/pandora_server.conf /etc/pandora/
   echo "Pandora FMS Server configuration is /etc/pandora/pandora_server.conf"
   echo "Pandora FMS Server main directory is %{prefix}/pandora_server/"
   echo "The manual can be reached at: man pandora or man pandora_server"
   echo "Pandora FMS Documentation is in: http://pandorafms.org"
   echo " "
fi

echo "Don't forget to start Tentacle Server daemon if you want to receive"
echo "data using tentacle"

%preun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

/etc/init.d/pandora_server stop &>/dev/null
/etc/init.d/tentacle_serverd stop &>/dev/null
chkconfig --del pandora_server
chkconfig --del tentacle_serverd

%postun

# Upgrading
if [ "$1" = "1" ]; then
        exit 0
fi

rm -Rf /etc/init.d/tentacle_serverd
rm -Rf /etc/init.d/pandora_server
rm -Rf %{prefix}pandora_server
rm -Rf /var/log/pandora
rm -Rf /usr/lib/perl5/PandoraFMS/
rm -Rf /etc/pandora/pandora_server.conf
rm -Rf /var/spool/pandora
rm -Rf /etc/init.d/pandora_server /etc/init.d/tentacle_serverd 
rm -Rf /usr/bin/pandora_exec /usr/bin/pandora_server /usr/bin/tentacle_server
rm -Rf /etc/cron.daily/pandora_db
rm -Rf /etc/logrotate.d/pandora
rm -Rf /usr/share/man/man1/pandora_server.1.gz
rm -Rf /usr/share/man/man1/tentacle_server.1.gz

%files

%defattr(750,pandora,root)
/etc/init.d/pandora_server
/etc/init.d/tentacle_serverd

%defattr(755,pandora,root)
/usr/bin/pandora_exec
/usr/bin/pandora_server
/usr/bin/tentacle_server

%defattr(755,pandora,root)
/usr/lib/perl5/PandoraFMS/
%{prefix}/pandora_server
/var/log/pandora

%defattr(770,pandora,www)
/var/spool/pandora
/var/spool/pandora/data_in
/var/spool/pandora/data_in/md5
/var/spool/pandora/data_in/collections

%defattr(750,pandora,root)
/etc/pandora

%defattr(644,pandora,root)
/usr/share/man/man1/pandora_server.1.gz
/usr/share/man/man1/tentacle_server.1.gz

