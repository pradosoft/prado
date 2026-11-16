<?php

/**
 * TImapMailbox class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Mailbox;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNetworkException;
use Prado\TPropertyValue;

/**
 * TImapMailbox class.
 *
 * TImapMailbox reads unseen messages from an IMAP4rev1 mailbox over a socket.  It
 * needs no PHP imap extension, which PHP 8.4 removed from core.  It implements the
 * commands for reading a mailbox: LOGIN or AUTHENTICATE XOAUTH2, SELECT, UID SEARCH
 * UNSEEN, UID FETCH BODY.PEEK[], UID STORE +FLAGS (\Seen), and LOGOUT.
 *
 * | Encryption | Connection |
 * |---|---|
 * | ssl | TLS from the start (port 993). Default. |
 * | starttls | Plain connection upgraded by STARTTLS (port 143). |
 * | none | Plain connection; credentials are sent unencrypted. |
 *
 * The settings may come from a DSN:
 * <code>
 *   imaps://user%40example.com:password@imap.example.com:993/INBOX
 *   imap://user:password@imap.example.com:143/Bounces?encryption=starttls
 *   imaps://user%40gmail.com@imap.gmail.com/INBOX?auth=xoauth2&token=ACCESS_TOKEN
 * </code>
 * Gmail and Microsoft 365 require XOAUTH2 with an OAuth 2.0 access token.  Encryption
 * other than "none" needs the openssl extension.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TImapMailbox extends \Prado\TComponent implements IEmailMailbox
{
	public const ENCRYPTION_SSL = 'ssl';
	public const ENCRYPTION_STARTTLS = 'starttls';
	public const ENCRYPTION_NONE = 'none';
	public const AUTH_LOGIN = 'LOGIN';
	public const AUTH_XOAUTH2 = 'XOAUTH2';

	/** @var string the server host. */
	private $_host = '';

	/** @var ?int the server port; null uses 993 for ssl and 143 otherwise. */
	private $_port;

	/** @var string the encryption, one of the ENCRYPTION_* constants. */
	private $_encryption = self::ENCRYPTION_SSL;

	/** @var string the login name. */
	private $_username = '';

	/** @var string the password. */
	private $_password = '';

	/** @var string the OAuth 2.0 access token for XOAUTH2. */
	private $_accessToken = '';

	/** @var string the authentication mechanism, one of the AUTH_* constants. */
	private $_authMechanism = self::AUTH_LOGIN;

	/** @var string the mailbox name. */
	private $_mailbox = 'INBOX';

	/** @var float the socket timeout in seconds. */
	private $_timeout = 30.0;

	/** @var null|resource the socket stream. */
	private $_stream;

	/** @var int the command tag counter. */
	private $_tag = 0;

	/** @var bool whether the mailbox is selected. */
	private $_opened = false;

	/**
	 * Connects, authenticates, and selects the mailbox.
	 * @throws TNetworkException when the connection fails.
	 * @throws TNetworkException when the server rejects a command.
	 */
	public function open(): void
	{
		if ($this->_opened) {
			return;
		}
		$encryption = $this->getEncryption();
		if ($encryption !== self::ENCRYPTION_NONE && !extension_loaded('openssl')) {
			throw new TConfigurationException('imapmailbox_openssl_required');
		}
		if ($this->getHost() === '') {
			throw new TConfigurationException('imapmailbox_host_required');
		}
		$port = $this->getPort() ?? ($encryption === self::ENCRYPTION_SSL ? 993 : 143);
		$this->connectTransport(($encryption === self::ENCRYPTION_SSL ? 'ssl://' : 'tcp://') . $this->getHost() . ':' . $port);
		$greeting = $this->readLine();
		if (!preg_match('/^\* (OK|PREAUTH)\b/i', $greeting)) {
			throw new TNetworkException('imapmailbox_bad_greeting', trim($greeting));
		}
		if ($encryption === self::ENCRYPTION_STARTTLS) {
			$this->command('STARTTLS');
			$this->enableTransportCrypto();
		}
		if (strtoupper($this->getAuthMechanism()) === self::AUTH_XOAUTH2) {
			$this->command('AUTHENTICATE XOAUTH2 ' . base64_encode('user=' . $this->getUsername() . "\x01auth=Bearer " . $this->getAccessToken() . "\x01\x01"));
		} else {
			$this->command('LOGIN ' . $this->quote($this->getUsername()) . ' ' . $this->quote($this->getPassword()));
		}
		$this->command('SELECT ' . $this->quote($this->getMailbox()));
		$this->_opened = true;
	}

	/**
	 * @return string[] the UIDs of the unseen messages.
	 */
	public function getUnseenMessageIds(): array
	{
		$this->open();
		$ids = [];
		foreach ($this->command('UID SEARCH UNSEEN') as [$line]) {
			if (preg_match('/^\* SEARCH\b(.*)$/i', $line, $m)) {
				array_push($ids, ...preg_split('/\s+/', trim($m[1]), -1, PREG_SPLIT_NO_EMPTY));
			}
		}
		return $ids;
	}

	/**
	 * Fetches a message by UID without setting \Seen.
	 * @param string $id the message UID.
	 * @throws TNetworkException when the message is not returned.
	 * @return string the raw message.
	 */
	public function fetchMessage(string $id): string
	{
		$this->open();
		foreach ($this->command('UID FETCH ' . $this->ensureUid($id) . ' BODY.PEEK[]') as [$line, $literals]) {
			if ($literals && preg_match('/^\* \d+ FETCH\b/i', $line)) {
				return $literals[0];
			}
		}
		throw new TNetworkException('imapmailbox_message_missing', $id);
	}

	/**
	 * Sets \Seen on a message by UID.
	 * @param string $id the message UID.
	 */
	public function markSeen(string $id): void
	{
		$this->open();
		$this->command('UID STORE ' . $this->ensureUid($id) . ' +FLAGS (\\Seen)');
	}

	/**
	 * Logs out and closes the connection.
	 */
	public function close(): void
	{
		if (!$this->_opened) {
			$this->closeTransport();
			return;
		}
		$this->_opened = false;
		try {
			$this->command('LOGOUT');
		} catch (TNetworkException $e) {
		} finally {
			$this->closeTransport();
		}
	}

	/**
	 * Sends a tagged command and reads its responses.
	 * @param string $command the command without tag or line ending.
	 * @throws TNetworkException when the server answers NO or BAD.
	 * @return array list of untagged responses as [line, literals].
	 */
	protected function command(string $command): array
	{
		$tag = 'A' . str_pad((string) ++$this->_tag, 4, '0', STR_PAD_LEFT);
		$this->writeData($tag . ' ' . $command . "\r\n");
		$responses = [];
		while (true) {
			$line = $this->readLine();
			$literals = [];
			while (preg_match('/\{(\d+)\}\r?\n$/', $line, $m)) {
				$literals[] = $this->readBytes((int) $m[1]);
				$line = substr($line, 0, -strlen($m[0])) . $this->readLine();
			}
			$line = rtrim($line, "\r\n");
			if (str_starts_with($line, $tag . ' ')) {
				$result = substr($line, strlen($tag) + 1);
				if (stripos($result, 'OK') !== 0) {
					$verb = strtok($command, ' ');
					throw new TNetworkException('imapmailbox_command_failed', $verb === 'LOGIN' || $verb === 'AUTHENTICATE' ? $verb : $command, $result);
				}
				return $responses;
			}
			if (str_starts_with($line, '+')) {
				$this->writeData("\r\n");
				continue;
			}
			$responses[] = [$line, $literals];
		}
	}

	/**
	 * @param string $value the string.
	 * @throws TConfigurationException when the string has a line break or NUL.
	 * @return string the IMAP quoted string.
	 */
	protected function quote(string $value): string
	{
		if (strpbrk($value, "\r\n\0") !== false) {
			throw new TConfigurationException('imapmailbox_invalid_string');
		}
		return '"' . addcslashes($value, '"\\') . '"';
	}

	/**
	 * @param string $id the message UID.
	 * @throws TConfigurationException when the UID is not a number.
	 * @return string the UID.
	 */
	protected function ensureUid(string $id): string
	{
		if (!ctype_digit($id)) {
			throw new TConfigurationException('imapmailbox_invalid_uid', $id);
		}
		return $id;
	}

	/**
	 * Opens the socket.
	 * @param string $remote the socket address, e.g. "ssl://imap.example.com:993".
	 * @throws TNetworkException when the connection fails.
	 */
	protected function connectTransport(string $remote): void
	{
		$context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $this->getHost()]]);
		$stream = @stream_socket_client($remote, $errno, $errstr, $this->getTimeout(), STREAM_CLIENT_CONNECT, $context);
		if ($stream === false) {
			throw new TNetworkException('imapmailbox_connect_failed', $remote, $errstr);
		}
		stream_set_timeout($stream, (int) ceil($this->getTimeout()));
		$this->_stream = $stream;
	}

	/**
	 * Upgrades the socket to TLS after STARTTLS.
	 * @throws TNetworkException when the TLS handshake fails.
	 */
	protected function enableTransportCrypto(): void
	{
		if (@stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
			throw new TNetworkException('imapmailbox_starttls_failed', $this->getHost());
		}
	}

	/**
	 * @param string $data the bytes to send.
	 * @throws TNetworkException when writing fails.
	 */
	protected function writeData(string $data): void
	{
		if (@fwrite($this->_stream, $data) !== strlen($data)) {
			throw new TNetworkException('imapmailbox_write_failed', $this->getHost());
		}
	}

	/**
	 * @throws TNetworkException when the connection closes or times out.
	 * @return string one line with its line ending.
	 */
	protected function readLine(): string
	{
		$line = @fgets($this->_stream);
		if ($line === false) {
			throw new TNetworkException('imapmailbox_read_failed', $this->getHost());
		}
		return $line;
	}

	/**
	 * @param int $length the number of bytes.
	 * @throws TNetworkException when the connection closes or times out.
	 * @return string exactly $length bytes.
	 */
	protected function readBytes(int $length): string
	{
		$data = '';
		while (strlen($data) < $length) {
			$chunk = @fread($this->_stream, $length - strlen($data));
			if ($chunk === false || $chunk === '') {
				throw new TNetworkException('imapmailbox_read_failed', $this->getHost());
			}
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * Closes the socket.
	 */
	protected function closeTransport(): void
	{
		if (is_resource($this->_stream)) {
			fclose($this->_stream);
		}
		$this->_stream = null;
	}

	/**
	 * Sets the connection from a DSN: imaps:// for ssl, imap:// for starttls.  The path is
	 * the mailbox.  Query parameters: encryption, auth, token.  The user and password are URL-decoded.
	 * @param string $dsn the DSN.
	 * @throws TConfigurationException when the DSN cannot be parsed.
	 */
	public function setDsn($dsn)
	{
		if (($parts = parse_url((string) $dsn)) === false || !isset($parts['host'])) {
			throw new TConfigurationException('imapmailbox_dsn_invalid');
		}
		parse_str($parts['query'] ?? '', $query);
		$scheme = strtolower($parts['scheme'] ?? 'imaps');
		$this->setHost($parts['host']);
		$this->setPort($parts['port'] ?? null);
		$this->setEncryption($query['encryption'] ?? ($scheme === 'imaps' ? self::ENCRYPTION_SSL : self::ENCRYPTION_STARTTLS));
		$this->setUsername(rawurldecode($parts['user'] ?? ''));
		$this->setPassword(rawurldecode($parts['pass'] ?? ''));
		if (isset($query['auth'])) {
			$this->setAuthMechanism($query['auth']);
		}
		if (isset($query['token'])) {
			$this->setAccessToken($query['token']);
		}
		if (($path = trim(rawurldecode($parts['path'] ?? ''), '/')) !== '') {
			$this->setMailbox($path);
		}
	}

	/**
	 * @return string the server host.
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * @param string $value the server host.
	 */
	public function setHost($value)
	{
		$this->_host = TPropertyValue::ensureString($value);
	}

	/**
	 * @return ?int the server port; null uses 993 for ssl and 143 otherwise.
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * @param ?int $value the server port; null uses the default of the encryption.
	 */
	public function setPort($value)
	{
		$this->_port = ($value === null || $value === '') ? null : TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string the encryption: "ssl", "starttls", or "none". Defaults to "ssl".
	 */
	public function getEncryption()
	{
		return $this->_encryption;
	}

	/**
	 * @param string $value the encryption: "ssl", "starttls", or "none".
	 */
	public function setEncryption($value)
	{
		$this->_encryption = TPropertyValue::ensureEnum(strtolower((string) $value), [self::ENCRYPTION_SSL, self::ENCRYPTION_STARTTLS, self::ENCRYPTION_NONE]);
	}

	/**
	 * @return string the login name.
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * @param string $value the login name.
	 */
	public function setUsername($value)
	{
		$this->_username = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the password.
	 */
	public function getPassword()
	{
		return $this->_password;
	}

	/**
	 * @param string $value the password.
	 */
	public function setPassword($value)
	{
		$this->_password = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the OAuth 2.0 access token for XOAUTH2.
	 */
	public function getAccessToken()
	{
		return $this->_accessToken;
	}

	/**
	 * @param string $value the OAuth 2.0 access token for XOAUTH2.
	 */
	public function setAccessToken($value)
	{
		$this->_accessToken = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the authentication mechanism: "LOGIN" or "XOAUTH2". Defaults to "LOGIN".
	 */
	public function getAuthMechanism()
	{
		return $this->_authMechanism;
	}

	/**
	 * @param string $value the authentication mechanism: "LOGIN" or "XOAUTH2".
	 */
	public function setAuthMechanism($value)
	{
		$this->_authMechanism = TPropertyValue::ensureEnum(strtoupper((string) $value), [self::AUTH_LOGIN, self::AUTH_XOAUTH2]);
	}

	/**
	 * @return string the mailbox name. Defaults to "INBOX".
	 */
	public function getMailbox()
	{
		return $this->_mailbox;
	}

	/**
	 * @param string $value the mailbox name.
	 */
	public function setMailbox($value)
	{
		$this->_mailbox = TPropertyValue::ensureString($value);
	}

	/**
	 * @return float the socket timeout in seconds. Defaults to 30.
	 */
	public function getTimeout()
	{
		return $this->_timeout;
	}

	/**
	 * @param float $value the socket timeout in seconds.
	 */
	public function setTimeout($value)
	{
		$this->_timeout = TPropertyValue::ensureFloat($value);
	}
}
