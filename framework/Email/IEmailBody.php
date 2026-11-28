<?php

/**
 * IEmailBody interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

/**
 * IEmailBody interface.
 *
 * IEmailBody produces the content of a {@see TEmail}.  {@see TEmail::render} calls
 * {@see renderEmail} once; the body sets the Html, and may set the Text, the
 * subject, files, and tracked links.  {@see UI\TEmailBody} renders a ".email" template.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IEmailBody
{
	/**
	 * Renders the content into the email.
	 * @param TEmail $email the email that receives the content.
	 */
	public function renderEmail(TEmail $email): void;
}
