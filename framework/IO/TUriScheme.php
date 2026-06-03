<?php

/**
 * TUriScheme class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\TEnumerable;

/**
 * TUriScheme class
 *
 * Enumerate the common URI scheme names (RFC 3986), each constant mapping to its
 * lower-case scheme string.  Use the constants for typed, typo-proof scheme tests
 * instead of magic strings — for example `$uri->getScheme() === TUriScheme::HTTPS`.
 *
 * URI schemes are an open set, so this is a convenience list of well-known schemes,
 * not an exhaustive or enforced one; {@see \Prado\IO\TResourceUri} accepts any
 * syntactically valid scheme.  Stream-wrapper schemes containing a dot (such as
 * `compress.zlib`) are omitted because they are not atomic.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUriScheme extends TEnumerable
{
	// web & transfer
	public const FILE = 'file';
	public const HTTP = 'http';
	public const HTTPS = 'https';
	public const DATA = 'data';
	public const WS = 'ws';
	public const WSS = 'wss';
	public const FTP = 'ftp';
	public const FTPS = 'ftps';
	public const SFTP = 'sftp';
	public const SSH = 'ssh';

	// version control
	public const GIT = 'git';
	public const SVN = 'svn';
	public const RSYNC = 'rsync';

	// mail & telephony
	public const MAILTO = 'mailto';
	public const TEL = 'tel';
	public const SMTP = 'smtp';
	public const SMTPS = 'smtps';
	public const IMAP = 'imap';
	public const IMAPS = 'imaps';
	public const POP3 = 'pop3';
	public const POP3S = 'pop3s';

	// realtime
	public const SIP = 'sip';
	public const SIPS = 'sips';
	public const XMPP = 'xmpp';
	public const IRC = 'irc';
	public const IRCS = 'ircs';
	public const RTSP = 'rtsp';

	// directory & network
	public const LDAP = 'ldap';
	public const LDAPS = 'ldaps';
	public const DNS = 'dns';
	public const TELNET = 'telnet';
	public const GOPHER = 'gopher';
	public const NNTP = 'nntp';
	public const NTP = 'ntp';
	public const TFTP = 'tftp';

	// identifiers
	public const URN = 'urn';
	public const BLOB = 'blob';

	// PHP stream wrappers
	public const PHP = 'php';
	public const PHAR = 'phar';
	public const ZIP = 'zip';
	public const GLOB = 'glob';
}
