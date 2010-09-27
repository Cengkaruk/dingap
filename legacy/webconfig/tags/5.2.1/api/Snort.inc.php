<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Snort rule types.
 *
 * There are three types of rule sets:
 * - security: security-related rules
 * - policy: policy-based rules (e.g. detecting access to porn)
 * - ignore: not-so-useful or poorly maintained rules
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

$this->rule_details = array(
	"attack-responses.rules" => array( "type" => "security", "description" => SNORT_LANG_RULELIST_ATTACK_RESPONSES ),
	"backdoor.rules"         => array( "type" => "security", "description" => SNORT_LANG_RULELIST_BACKDOOR ),
	"bad-traffic.rules"      => array( "type" => "security", "description" => SNORT_LANG_RULELIST_BAD_TRAFFIC ),
	"chat.rules"             => array( "type" => "policy",   "description" => SNORT_LANG_RULELIST_CHAT ),
	"ddos.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_DDOS ),
	"deleted.rules"          => array( "type" => "ignore",   "description" => "Deleted" ),
	"dns.rules"              => array( "type" => "security", "description" => SNORT_LANG_RULELIST_DNS ),
	"dos.rules"              => array( "type" => "security", "description" => SNORT_LANG_RULELIST_DOS ),
	"experimental.rules"     => array( "type" => "ignore",   "description" => "Experimental" ),
	"exploit.rules"          => array( "type" => "security", "description" => SNORT_LANG_RULELIST_EXPLOIT ),
	"finger.rules"           => array( "type" => "security", "description" => SNORT_LANG_RULELIST_FINGER ),
	"ftp.rules"              => array( "type" => "security", "description" => SNORT_LANG_RULELIST_FTP ),
	"icmp-info.rules"        => array( "type" => "ignore",   "description" => "ICMP detection" ),
	"icmp.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_ICMP ),
	"imap.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_IMAP ),
	"info.rules"             => array( "type" => "policy",   "description" => SNORT_LANG_RULELIST_INFO ),
	"local.rules"            => array( "type" => "ignore",   "description" => "Local" ),
	"misc.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_MISC ),
	"multimedia.rules"       => array( "type" => "policy",   "description" => SNORT_LANG_RULELIST_MULTIMEDIA ),
	"mysql.rules"            => array( "type" => "security", "description" => SNORT_LANG_RULELIST_MYSQL ),
	"netbios.rules"          => array( "type" => "security", "description" => SNORT_LANG_RULELIST_NETBIOS ),
	"nntp.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_NNTP ),
	"oracle.rules"           => array( "type" => "security", "description" => SNORT_LANG_RULELIST_ORACLE ),
	"other-ids.rules"        => array( "type" => "ignore",   "description" => "Other intrusion detection" ),
	"p2p.rules"              => array( "type" => "policy",   "description" => SNORT_LANG_RULELIST_P2P ),
	"policy.rules"           => array( "type" => "policy",   "description" => SNORT_LANG_RULELIST_POLICY ),
	"pop2.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_POP2 ),
	"pop3.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_POP3 ),
	"porn.rules"             => array( "type" => "policy",   "description" => SNORT_LANG_RULELIST_PORN ),
	"rpc.rules"              => array( "type" => "security", "description" => SNORT_LANG_RULELIST_RPC ),
	"rservices.rules"        => array( "type" => "security", "description" => SNORT_LANG_RULELIST_RSERVICES ),
	"scan.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_SCAN ),
	"shellcode.rules"        => array( "type" => "security", "description" => SNORT_LANG_RULELIST_SHELLCODE ),
	"smtp.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_SMTP ),
	"snmp.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_SNMP ),
	"sql.rules"              => array( "type" => "security", "description" => SNORT_LANG_RULELIST_SQL ),
	"telnet.rules"           => array( "type" => "security", "description" => SNORT_LANG_RULELIST_TELNET ),
	"tftp.rules"             => array( "type" => "security", "description" => SNORT_LANG_RULELIST_TFTP ),
	"virus.rules"            => array( "type" => "ignore",   "description" => "Virus" ),
	"web-attacks.rules"      => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_ATTACKS ),
	"web-cgi.rules"          => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_CGI ),
	"web-client.rules"       => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_CLIENT ),
	"web-coldfusion.rules"   => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_COLDFUSION ),
	"web-frontpage.rules"    => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_FRONTPAGE ),
	"web-iis.rules"          => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_IIS ),
	"web-misc.rules"         => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_MISC ),
	"web-php.rules"          => array( "type" => "security", "description" => SNORT_LANG_RULELIST_WEB_PHP ),
	"x11.rules"              => array( "type" => "security", "description" => SNORT_LANG_RULELIST_X11 ),
);

?>
