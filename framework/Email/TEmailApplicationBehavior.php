<?php

/**
 * TEmailApplicationBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

/**
 * TEmailApplicationBehavior class.
 *
 * TEmailApplicationBehavior gives the application the email methods of its
 * {@see TEmailModule}.  The first email module attaches it under the name "email".
 *
 * <code>
 * $email = $app->createEmail('Order.Shipped');
 * $email->setTo('ann@example.com');
 * $app->sendEmail($email);
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailApplicationBehavior extends \Prado\Util\TBehavior
{
	/** @var ?TEmailModule the module that does the work. */
	private ?TEmailModule $_module = null;

	/**
	 * Creates the email of an email path.
	 * @param string $emailPath the email path, e.g. "Order.Shipped".
	 * @param ?string $culture the culture of the template and text; null for the current culture.
	 * @return TEmail the email.
	 * @see TEmailModule::createEmail
	 */
	public function createEmail($emailPath, $culture = null): TEmail
	{
		return $this->getModule()->createEmail($emailPath, $culture);
	}

	/**
	 * Sends an email.
	 * @param TEmail $email the email.
	 * @return string the {@see TEmailStatus} of the email after the call.
	 * @see TEmailModule::sendEmail
	 */
	public function sendEmail(TEmail $email): string
	{
		return $this->getModule()->sendEmail($email);
	}

	/**
	 * @return ?TEmailModule the module that does the work.
	 */
	public function getModule(): ?TEmailModule
	{
		return $this->_module;
	}

	/**
	 * @param TEmailModule $module the module that does the work.
	 */
	public function setModule(TEmailModule $module)
	{
		$this->_module = $module;
	}
}
