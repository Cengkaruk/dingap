<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2008 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Daemon list.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
//
// In an ideal world, we would be able to scan the list of scripts in 
// /etc/rc.d/init.d and generate the service list on the fly.  Unfortunately
// there are some inconsistencies that make this impossible.
//
// A list of services that we care about are below:
// - keyed on init.d script
// - array holds:
//   - the RPM where the daemon lives
//   - the daemon/process name (what you see with ps)
//   - whether or not the daemon supports a "/etc/rc.d/init.d/<xyz> reload"
//   - a short title (uses language templates)
//   - core daemon (no configuration, but still important)
//   - configuration URL
//
// A few daemons are not really "running" per se, but are part of the kernel,
// e.g. the firewall and bandwidth limiter.  Specify "kernel" for the 
// process name -- the daemon class will handle these differently.
//
///////////////////////////////////////////////////////////////////////////////

$DAEMONS = array(
	"amavisd"       => array("amavisd-new",      "amavisd",       "no",   DAEMON_LANG_AMAVIS,       "yes", null),
	"atalk"         => array("netatalk",         "atalkd",        "no",   DAEMON_LANG_APPLETALK,    "no",  null),
	"autofs"        => array("autofs",           "automount",     "yes",  DAEMON_LANG_AUTOFS,       "yes", null),
	"bacula-dir"    => array("bacula-mysql",     "bacula-dir",    "no",   DAEMON_LANG_BACULA_DIR,   "no",  "bacula.php"),
	"bacula-fd"     => array("bacula-mysql",     "bacula-fd",     "no",   DAEMON_LANG_BACULA_FD,    "no",  "bacula.php"),
	"bacula-mysqld" => array("bacula-mysql",     "bacula-mysqld", "no",   DAEMON_LANG_BACULA_MYSQL, "no",  "bacula.php"),
	"bacula-sd"     => array("bacula-mysql",     "bacula-sd",     "no",   DAEMON_LANG_BACULA_SD,    "no",  "bacula.php"),
	"bandwidth"     => array("app-bandwidth",    "kernel",        "no",   DAEMON_LANG_BANDWIDTH,    "no",  "bandwidth.php"),
	"clamd"         => array("clamav-server",    "clamd",         "no",   DAEMON_LANG_CLAMAV,       "no",  "antivirus.php"),
	"crond"         => array("vixie-cron",       "crond",         "yes",  DAEMON_LANG_CRON,         "yes", null),
	"cups"          => array("cups",             "cupsd",         "yes",  DAEMON_LANG_CUPS,         "no",  "printing-advanced.php"),
	"cyrus-imapd"   => array("cyrus-imapd",      "cyrus-master",  "no",   DAEMON_LANG_CYRUS,        "no",  "mail-pop-imap.php"),
	"dansguardian"	=> array("dansguardian",     "dansguardian",  "yes",  DAEMON_LANG_DANSGUARDIAN_BASIC, "no",  "proxy-filter.php"),
	"dansguardian-av" => array("dansguardian-av","dansguardian-av","yes", DAEMON_LANG_DANSGUARDIAN, "no", "proxy-filter.php"),
	"dhcpd"         => array("dhcp",             "dhcpd",         "no",   DAEMON_LANG_DHCP,         "no",  null),
	"dnsmasq"       => array("dnsmasq",          "dnsmasq",       "no",   DAEMON_LANG_DNSMASQ,      "no",  "dhcp.php"),
	"dovecot"       => array("dovecot",          "dovecot",       "no",   "Dovecot POP/IMAP",       "no",  null),
	"exim"          => array("exim",             "exim",          "no",   "Exim",                   "no",  null),
	"fetchmail"     => array("fetchmail",        "fetchmail",     "no",   DAEMON_LANG_FETCHMAIL,    "no",  "mail-retrieval.php"),
	"firewall"      => array("app-firewall",     "kernel",        "no",   DAEMON_LANG_FIREWALL,     "yes", "firewall.php"),
	"freshclam"     => array("clamav",           "freshclam ",    "no",   DAEMON_LANG_FRESHCLAM,    "no",  "antivirus.php"),
	"httpd"         => array("httpd",            "httpd",         "yes",  DAEMON_LANG_HTTPD,        "no",  "web-server.php"),
	"ipsec"         => array("openswan",         "pluto",         "yes",  DAEMON_LANG_IPSEC,        "no",  "ipsec.php"),
	"l7-filter"     => array("l7-filter-userspace",     "l7-filter",     "no",   DAEMON_LANG_L7FILTER,    "no",  "protocol-filter.php"),
	"ldap"          => array("openldap-servers", "slapd",         "no",   DAEMON_LANG_LDAP,         "yes", null),
	"ldapsync"      => array("kolabd",           "ldapsync",      "no",   DAEMON_LANG_KOLAB,        "yes", null),
	"mysqld"        => array("mysql-server",     "mysqld",        "no",   DAEMON_LANG_MYSQL,        "no",  "mysql.php"),
	"network"       => array("initscripts",      "kernel",        "no",   DAEMON_LANG_NETWORK,      "yes", "network.php"),
	"nfs"         	=> array("nfs-utils",        "nfsd",          "no",   DAEMON_LANG_NFS,          "no",  null),
	"nmb"           => array("samba",            "nmbd",          "no",   DAEMON_LANG_NMBD,         "no",  "samba.php"),
	"ntpd"          => array("ntp",              "ntpd",          "no",   DAEMON_LANG_NTPD,         "no",  "date.php"),
	"openvpn"       => array("openvpn",          "openvpn",       "no",   DAEMON_LANG_OPENVPN,      "no",  "openvpn.php"),
	"pcmcia"        => array("pcmcia-cs",        "cardmgr",       "no",   DAEMON_LANG_PCMCIA,       "yes", null),
	"portmap"       => array("portmap",          "portmap",       "no",   DAEMON_LANG_PORTMAP,      "no",  null),
	"postfix"       => array("postfix",          "master",        "yes",  DAEMON_LANG_POSTFIX,      "no",  "mail-smtp.php"),
	"postgrey"      => array("postgrey",         "postgrey",      "no",   DAEMON_LANG_GREYLIST,     "no",  "mail-filters.php"),
	"pptpd"         => array("pptpd",            "pptpd",         "no",   DAEMON_LANG_PPTP,         "no",  "pptpd.php"),
	"privoxy"       => array("privoxy",          "privoxy",       "yes",  DAEMON_LANG_PRIVOXY,      "no",  null),
	"proftpd"       => array("proftpd",          "proftpd",       "yes",  DAEMON_LANG_PROFTP,       "no",  "ftp.php"),
	"saslauthd"     => array("cyrus-sasl",       "saslauthd",     "no",   DAEMON_LANG_SASL,         "yes", null),
	"smartd"        => array("smartmontools",    "smartd",        "no",   DAEMON_LANG_SMARTD,       "yes", null),
	"smb"           => array("samba",            "smbd",          "no",   DAEMON_LANG_SAMBA,        "no",  "samba.php"),
	"snort"         => array("snort",            "snort",         "no",   DAEMON_LANG_SNORT,        "no",  "intrusion-detection.php"),
	"snortsam"      => array("snort",            "snortsam",      "no",   DAEMON_LANG_SNORTSAM,     "no",  "intrusion-prevention.php"),
	"spamassassin"  => array("spamassassin",     "spamd",         "no",   DAEMON_LANG_SPAMASSASSIN, "no",  "mail-antispam.php"),
	"squid"         => array("squid",            "squid",         "yes",  DAEMON_LANG_SQUID,        "no",  "proxy.php"),
	"sshd"          => array("openssh",          "sshd",          "yes",  DAEMON_LANG_SHELL,        "yes", null),
	"suvad"         => array("suva-client",      "suvad",         "no",   DAEMON_LANG_SUVA,         "yes", null),
	"syslog"        => array("sysklogd",         "syslogd",       "yes",  DAEMON_LANG_SYSLOG,       "yes", null),
	"system-mysqld" => array("system-mysql",     "system-mysqld", "no",   DAEMON_LANG_SYSTEM_DATABASE, "yes",  null),
	"syswatch"      => array("app-syswatch",     "syswatch",      "no",   DAEMON_LANG_SYSWATCH,     "yes", null),
	"upnpd"         => array("linuxigd",         "upnpd",         "no",   "UPnP",                   "no",  null),
	"vpnwatchd"     => array("app-ipsec",        "vpnwatchd",     "yes",  DAEMON_LANG_VPNWATCH,     "no",  "ipsec.php"),
	"webconfig"     => array("app-webconfig",    "webconfig",     "no",   "Webconfig",              "yes", null),
	"webmin"        => array("webmin",           "miniserv.pl",   "no",   "Webmin",                 "no",  null),
	"winbind"       => array("samba-winbind",    "winbindd",      "no",   DAEMON_LANG_WINBIND,      "yes",  null),
	"xinetd"        => array("xinetd",           "xinetd",        "yes",  DAEMON_LANG_XINET,        "no",  null),
);

ksort($DAEMONS);
