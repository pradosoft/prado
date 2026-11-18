<?php

/**
 * IEmailTransport interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

use Prado\Email\TEmail;

/**
 * IEmailTransport interface.
 *
 * IEmailTransport delivers a final {@see TEmail} to a mail system.  {@see send}
 * returns when the mail system accepted the email and throws when it did not.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IEmailTransport
{
	/**
	 * Delivers an email.
	 * @param TEmail $email the final email.
	 * @throws TEmailTransportException when the mail system does not accept the email.
	 */
	public function send(TEmail $email): void;

	/**
	 * Releases the connection that {@see send} keeps open between emails.
	 */
	public function close(): void;
}
