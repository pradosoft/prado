<?php

/**
 * TUriDefaultPort class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\TEnumerable;

/**
 * TUriDefaultPort class.
 *
 * Enumerate the default TCP/UDP port for each URI scheme that Prado addresses.
 * {@see \Prado\IO\TResourceUri} uses it to suppress a scheme's default port from
 * the authority (per PSR-7).  Each constant maps an upper-cased scheme name to its
 * port; look one up for a scheme string with {@see forScheme()}.
 *
 * The set covers web/transfer, mail, directory/realtime, the databases Prado
 * connects to (MySQL, PostgreSQL, Firebird, MS SQL via sqlsrv/mssql/dblib, IBM DB2
 * via ibm, Oracle via oci), messaging/infra brokers, and local AI inference
 * (Ollama on 11434).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUriDefaultPort extends TEnumerable
{
	// web / transfer
	public const HTTP = 80;
	public const HTTPS = 443;
	public const FTP = 21;
	public const FTPS = 990;
	public const SFTP = 22;
	public const SSH = 22;
	public const WS = 80;
	public const WSS = 443;
	public const RSYNC = 873;
	public const GIT = 9418;
	public const SVN = 3690;

	// mail
	public const SMTP = 25;
	public const SMTPS = 465;
	public const SUBMISSION = 587;
	public const IMAP = 143;
	public const IMAPS = 993;
	public const POP3 = 110;
	public const POP3S = 995;

	// directory / legacy / realtime
	public const LDAP = 389;
	public const LDAPS = 636;
	public const TELNET = 23;
	public const GOPHER = 70;
	public const NNTP = 119;
	public const NNTPS = 563;
	public const IRC = 6667;
	public const IRCS = 6697;
	public const XMPP = 5222;
	public const SIP = 5060;
	public const SIPS = 5061;
	public const RTSP = 554;
	public const DNS = 53;
	public const TFTP = 69;
	public const NTP = 123;

	// databases (the drivers Prado connects to via PDO)
	public const MYSQL = 3306;
	public const PGSQL = 5432;
	public const POSTGRES = 5432;
	public const POSTGRESQL = 5432;
	public const FIREBIRD = 3050;
	public const MSSQL = 1433;
	public const SQLSRV = 1433;
	public const DBLIB = 1433;
	public const OCI = 1521;
	public const ORACLE = 1521;
	public const IBM = 50000;
	public const DB2 = 50000;
	public const MONGODB = 27017;
	public const REDIS = 6379;
	public const REDISS = 6379;
	public const MEMCACHED = 11211;

	// messaging / infra
	public const AMQP = 5672;
	public const AMQPS = 5671;
	public const MQTT = 1883;
	public const MQTTS = 8883;
	public const KAFKA = 9092;
	public const NATS = 4222;
	public const ETCD = 2379;

	// AI (local inference)
	public const OLLAMA = 11434;

	/**
	 * Return the default port for a URI scheme, or null when the scheme has none.
	 * @param string $scheme The scheme (case-insensitive, e.g. 'https', 'sqlsrv').
	 * @return ?int The default port, or null.
	 */
	public static function forScheme(string $scheme): ?int
	{
		if ($scheme === '') {
			return null;
		}
		$constant = static::class . '::' . strtoupper($scheme);
		return defined($constant) ? constant($constant) : null;
	}
}
