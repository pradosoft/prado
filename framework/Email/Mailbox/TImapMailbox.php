<?php

/**
 * TImapMailbox class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Mailbox;

use Prado\Email\Net\TEmailEncryption;
use Prado\Email\Net\TEmailSocketConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNetworkException;
use Prado\IO\TUriDefaultPort;
use Prado\IO\TUriScheme;
use Prado\TPropertyValue;

/**
 * TImapMailbox class.
 *
 * TImapMailbox reads the unseen messages of an IMAP4rev1 mailbox over a
 * {@see TEmailSocketConnection}.  It needs no PHP imap extension, which PHP 8.4
 * removed from core.  It implements the commands for reading a mailbox: LOGIN or
 * AUTHENTICATE XOAUTH2, SELECT, UID SEARCH UNSEEN, UID FETCH BODY.PEEK[],
 * UID STORE +FLAGS (\Seen), and LOGOUT.
 *
 * | DSN | Encryption | Default port |
 * |---|---|---|
 * | `imaps://user:password@host/INBOX` | TLS from the first byte | 993 |
 * | `imap://user:password@host/INBOX` | STARTTLS, required | 143 |
 * | `imap://user:password@host/INBOX?encryption=none` | none | 143 |
 *
 * The DSN path is the mailbox.  The query also takes `token` (an OAuth 2 access
 * token), `verify` (0 turns certificate verification off), and `timeout`
 * (seconds).  The user name and password are URL encoded.
 *
 * With an {@see setAccessToken AccessToken} the mailbox authenticates with XOAUTH2,
 * which Gmail and Microsoft 365 require; otherwise it sends LOGIN.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TImapMailbox extends \Prado\TComponent implements IEmailMailbox
{
	/** @var ?TEmailSocketConnection the connection to the server. */
	private ?TEmailSocketConnection $_connection = null;

	/** @var bool whether the mailbox is selected. */
	private bool $_opened = false;

	/** @var int the number of the last command tag. */
	private int $_tag = 0;

	/** @var string the server host. */
	private string $_host = '';

	/** @var ?int the server port, null for the default of the encryption. */
	private ?int $_port = null;

	/** @var string the TEmailEncryption of the connection. */
	private string $_encryption = TEmailEncryption::Ssl;

	/** @var string the user name. */
	private string $_username = '';

	/** @var string the password. */
	private string $_password = '';

	/** @var string the OAuth 2 access token. */
	private string $_accessToken = '';

	/** @var string the mailbox name. */
	private string $_mailbox = 'INBOX';

	/** @var float the connect and read timeout in seconds. */
	private float $_timeout = 30.0;

	/** @var bool whether the server certificate is verified. */
	private bool $_verifyPeer = true;

	/** @var int the largest message, in bytes, that is fetched. */
	private int $_maxMessageSize = 26214400;

	/**
	 * Connects, authenticates, and selects the mailbox.  An open mailbox stays as it is.
	 * @throws TConfigurationException when the Host is not set.
	 * @throws TNetworkException when the connection fails or the server refuses a command.
	 */
	public function open(): void
	{
		if ($this->_opened) {
			return;
		}
		$connection = $this->getConnection();
		$encryption = $this->getEncryption();
		if (!$connection->getIsOpen()) {
			if (($host = $this->getHost()) === '') {
				throw new TConfigurationException('imapmailbox_host_required');
			}
			$connection->setTimeout($this->getTimeout());
			$connection->setVerifyPeer($this->getVerifyPeer());
			$connection->open($host, $this->getPort(), $encryption === TEmailEncryption::Ssl);
		}
		try {
			$greeting = $connection->readLine();
			if (!preg_match('/^\* (OK|PREAUTH)\b/i', $greeting)) {
				throw new TNetworkException('imapmailbox_bad_greeting', substr($greeting, 0, 200));
			}
			if ($encryption === TEmailEncryption::StartTls) {
				$this->command('STARTTLS');
				$connection->startTls();
			}
			if (($token = $this->getAccessToken()) !== '') {
				$this->command('AUTHENTICATE XOAUTH2 ' . base64_encode('user=' . $this->getUsername() . "\x01auth=Bearer " . $token . "\x01\x01"));
			} else {
				$this->command('LOGIN ' . $this->quote($this->getUsername()) . ' ' . $this->quote($this->getPassword()));
			}
			$this->command('SELECT ' . $this->quote($this->getMailbox()));
		} catch (\Throwable $e) {
			$connection->close();
			throw $e;
		}
		$this->_opened = true;
	}

	/**
	 * @throws TNetworkException when the connection fails or the server refuses the search.
	 * @return string[] the UIDs of the unseen messages.
	 */
	public function getUnseenMessageIds(): array
	{
		$this->open();
		$ids = [];
		foreach ($this->command('UID SEARCH UNSEEN') as [$line]) {
			if (preg_match('/^\* SEARCH\b(.*)$/i', $line, $match)) {
				array_push($ids, ...preg_split('/\s+/', trim($match[1]), -1, PREG_SPLIT_NO_EMPTY));
			}
		}
		return $ids;
	}

	/**
	 * Fetches a message by UID without setting \Seen.
	 * @param string $id the message UID.
	 * @throws TInvalidDataValueException when the ID is not a UID.
	 * @throws TNetworkException when the message is not returned or is larger than {@see getMaxMessageSize MaxMessageSize}.
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
	 * @throws TInvalidDataValueException when the ID is not a UID.
	 * @throws TNetworkException when the connection fails or the server refuses the command.
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
		$connection = $this->getConnection();
		if ($this->_opened && $connection->getIsOpen()) {
			try {
				$this->command('LOGOUT');
			} catch (TNetworkException $e) {
			}
		}
		$this->_opened = false;
		$connection->close();
	}

	/**
	 * Sends a tagged command and reads its responses up to the tagged result.
	 * @param string $command the command without its tag and line ending.
	 * @throws TNetworkException when the server answers NO or BAD, a literal is too large, or the connection fails.
	 * @return array<array{0: string, 1: string[]}> the untagged responses, each a line and its literals.
	 */
	protected function command(string $command): array
	{
		$connection = $this->getConnection();
		$tag = 'A' . str_pad((string) ++$this->_tag, 4, '0', STR_PAD_LEFT);
		$connection->writeLine($tag . ' ' . $command);
		$responses = [];
		while (true) {
			$line = $connection->readLine();
			$literals = [];
			while (preg_match('/\{(\d+)\}$/', $line, $match)) {
				if ((int) $match[1] > $this->getMaxMessageSize()) {
					$this->_opened = false;
					$connection->close();
					throw new TNetworkException('imapmailbox_message_too_large', $match[1]);
				}
				$literals[] = $connection->readBytes((int) $match[1]);
				$line = substr($line, 0, -strlen($match[0])) . $connection->readLine();
			}
			if (str_starts_with($line, $tag . ' ')) {
				$result = substr($line, strlen($tag) + 1);
				if (stripos($result, 'OK') !== 0) {
					$verb = (string) strtok($command, ' ');
					throw new TNetworkException('imapmailbox_command_failed', in_array($verb, ['LOGIN', 'AUTHENTICATE'], true) ? $verb : $command, $result);
				}
				return $responses;
			}
			if (str_starts_with($line, '+')) {
				$connection->writeLine('');
				continue;
			}
			$responses[] = [$line, $literals];
		}
	}

	/**
	 * @param string $value the string.
	 * @throws TInvalidDataValueException when the string has a line break or a NUL.
	 * @return string the IMAP quoted string.
	 */
	protected function quote(string $value): string
	{
		if (strpbrk($value, "\r\n\0") !== false) {
			throw new TInvalidDataValueException('imapmailbox_invalid_string');
		}
		return '"' . addcslashes($value, '"\\') . '"';
	}

	/**
	 * @param string $id the message UID.
	 * @throws TInvalidDataValueException when the UID is not a number.
	 * @return string the UID.
	 */
	protected function ensureUid(string $id): string
	{
		if (!ctype_digit($id)) {
			throw new TInvalidDataValueException('imapmailbox_invalid_uid', $id);
		}
		return $id;
	}

	/**
	 * @return TEmailSocketConnection the connection to the server.
	 */
	public function getConnection(): TEmailSocketConnection
	{
		return $this->_connection ??= new TEmailSocketConnection();
	}

	/**
	 * @param TEmailSocketConnection $connection the connection to the server; an open one is used as it is.
	 */
	public function setConnection(TEmailSocketConnection $connection)
	{
		$this->_connection = $connection;
		$this->_opened = false;
	}

	/**
	 * Sets the properties from a DSN.
	 * @param string $dsn the DSN, e.g. "imaps://user:password@imap.example.com/INBOX".
	 * @throws TInvalidDataValueException when the DSN is not an imap or imaps URL with a host.
	 */
	public function setDsn($dsn)
	{
		$parts = parse_url(TPropertyValue::ensureString($dsn));
		$scheme = strtolower((string) ($parts['scheme'] ?? ''));
		if (!is_array($parts) || !isset($parts['host']) || !in_array($scheme, [TUriScheme::IMAP, TUriScheme::IMAPS], true)) {
			throw new TInvalidDataValueException('imapmailbox_dsn_invalid');
		}
		parse_str($parts['query'] ?? '', $query);
		$this->setHost($parts['host']);
		$this->setEncryption($scheme === TUriScheme::IMAPS ? TEmailEncryption::Ssl : ($query['encryption'] ?? TEmailEncryption::StartTls));
		$this->setPort($parts['port'] ?? null);
		$this->setUsername(rawurldecode((string) ($parts['user'] ?? '')));
		$this->setPassword(rawurldecode((string) ($parts['pass'] ?? '')));
		$this->setAccessToken((string) ($query['token'] ?? ''));
		if (($path = trim(rawurldecode((string) ($parts['path'] ?? '')), '/')) !== '') {
			$this->setMailbox($path);
		}
		if (isset($query['verify'])) {
			$this->setVerifyPeer($query['verify']);
		}
		if (isset($query['timeout'])) {
			$this->setTimeout($query['timeout']);
		}
	}

	/**
	 * @return string the server host. Defaults to ''.
	 */
	public function getHost(): string
	{
		return $this->_host;
	}

	/**
	 * @param string $value the server host.
	 */
	public function setHost($value)
	{
		$this->_host = trim(TPropertyValue::ensureString($value), '[]');
	}

	/**
	 * @return int the server port. Defaults to 993 for Ssl and 143 otherwise.
	 */
	public function getPort(): int
	{
		return $this->_port ?? ($this->_encryption === TEmailEncryption::Ssl ? TUriDefaultPort::IMAPS : TUriDefaultPort::IMAP);
	}

	/**
	 * @param null|int|string $value the server port; null or empty for the default of the Encryption.
	 */
	public function setPort($value)
	{
		$this->_port = ($value === null || $value === '') ? null : TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string the {@see TEmailEncryption} of the connection. Defaults to TEmailEncryption::Ssl.
	 */
	public function getEncryption(): string
	{
		return $this->_encryption;
	}

	/**
	 * @param string $value the {@see TEmailEncryption} of the connection, in any case.
	 */
	public function setEncryption($value)
	{
		$this->_encryption = TPropertyValue::ensureEnum($value, TEmailEncryption::class);
	}

	/**
	 * @return string the user name. Defaults to ''.
	 */
	public function getUsername(): string
	{
		return $this->_username;
	}

	/**
	 * @param string $value the user name.
	 */
	public function setUsername($value)
	{
		$this->_username = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the password. Defaults to ''.
	 */
	public function getPassword(): string
	{
		return $this->_password;
	}

	/**
	 * @param string $value the password for LOGIN.
	 */
	public function setPassword($value)
	{
		$this->_password = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the OAuth 2 access token. Defaults to ''.
	 */
	public function getAccessToken(): string
	{
		return $this->_accessToken;
	}

	/**
	 * @param string $value the OAuth 2 access token; when set, the mailbox authenticates with XOAUTH2.
	 */
	public function setAccessToken($value)
	{
		$this->_accessToken = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the mailbox name. Defaults to 'INBOX'.
	 */
	public function getMailbox(): string
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
	 * @return float the connect and read timeout in seconds. Defaults to 30.
	 */
	public function getTimeout(): float
	{
		return $this->_timeout;
	}

	/**
	 * @param float|string $value the connect and read timeout in seconds, at least 1.
	 */
	public function setTimeout($value)
	{
		$this->_timeout = max(1.0, TPropertyValue::ensureFloat($value));
	}

	/**
	 * @return bool whether the server certificate is verified. Defaults to true.
	 */
	public function getVerifyPeer(): bool
	{
		return $this->_verifyPeer;
	}

	/**
	 * @param bool|string $value whether the server certificate is verified.
	 */
	public function setVerifyPeer($value)
	{
		$this->_verifyPeer = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int the largest message, in bytes, that is fetched. Defaults to 25 MB.
	 */
	public function getMaxMessageSize(): int
	{
		return $this->_maxMessageSize;
	}

	/**
	 * @param int|string $value the largest message, in bytes, that is fetched.
	 */
	public function setMaxMessageSize($value)
	{
		$this->_maxMessageSize = max(1, TPropertyValue::ensureInteger($value));
	}
}
