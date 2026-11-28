<?php

/**
 * IEmailMailer interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

/**
 * IEmailMailer interface.
 *
 * IEmailMailer sends a {@see TEmail}.  {@see TEmail::send} hands the email to its
 * mailer; {@see TEmailModule} is the mailer of an application.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IEmailMailer
{
	/**
	 * Sends, defers, or queues an email.
	 * @param TEmail $email the email.
	 * @return string the {@see TEmailStatus} of the email after the call.
	 */
	public function sendEmail(TEmail $email): string;
}
