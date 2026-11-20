<?php

/**
 * TEmailEncryption class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Net;

/**
 * TEmailEncryption class.
 *
 * TEmailEncryption is how a mail connection is encrypted.
 *
 * | Value | Connection |
 * |---|---|
 * | StartTls | Opens in plain text and upgrades with the STARTTLS command; the connection fails when the server refuses. |
 * | Ssl | TLS from the first byte. |
 * | None | Plain text. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailEncryption extends \Prado\TEnumerable
{
	public const StartTls = 'StartTls';
	public const Ssl = 'Ssl';
	public const None = 'None';
}
