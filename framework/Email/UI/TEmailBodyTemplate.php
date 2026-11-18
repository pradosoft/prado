<?php

/**
 * TEmailBodyTemplate class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI;

use Prado\Web\UI\TTemplate;

/**
 * TEmailBodyTemplate class.
 *
 * TEmailBodyTemplate is the parsed ".email" template of a {@see TEmailBody}.
 *
 * The template directive sets properties of the body and of its email.  A name
 * that starts with "Email." is a property of the {@see \Prado\Email\TEmail}:
 *
 * <code>
 * <%@ MasterClass="Application.Layouts.EmailLayout" Email.Subject="Your order shipped" %>
 * </code>
 *
 * {@see getEmailDirective} returns the email properties, which
 * {@see \Prado\Email\TEmailModule::createEmail} applies when it creates the email,
 * so the application code that follows can replace them.  {@see getDirective}
 * returns the others, which the body applies when it renders.
 *
 * The files the template publishes with `<%~ file %>` follow the
 * {@see TEmailBody::getPublishStyle PublishStyle} of the body.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailBodyTemplate extends TTemplate
{
	/** The prefix of a directive name that is a property of the email. */
	public const EMAIL_PREFIX = 'Email.';

	/** @var ?TEmailBody the body being instantiated. */
	private ?TEmailBody $_body = null;

	/**
	 * @return array the directive values of the body, by property name.
	 */
	public function getDirective()
	{
		return array_filter(parent::getDirective(), fn ($name) => !static::isEmailName($name), ARRAY_FILTER_USE_KEY);
	}

	/**
	 * @return array the directive values of the email, by property name without the "Email." prefix.
	 */
	public function getEmailDirective(): array
	{
		$directive = [];
		foreach (parent::getDirective() as $name => $value) {
			if (static::isEmailName($name)) {
				$directive[substr($name, strlen(self::EMAIL_PREFIX))] = $value;
			}
		}
		return $directive;
	}

	/**
	 * @param string $name the directive name.
	 * @return bool whether the name starts with "Email.", in any case.
	 */
	protected static function isEmailName(string $name): bool
	{
		return stripos($name, self::EMAIL_PREFIX) === 0;
	}

	/**
	 * Publishes a file by the publish style of the body being instantiated; outside
	 * a body the file is published as a web asset.
	 * @param string $fullPath the absolute path of the file or directory.
	 * @param bool $checkTimestamp whether a published asset is republished when the file is newer.
	 * @return string the URL of the file.
	 */
	public function publishFilePath($fullPath, $checkTimestamp = false)
	{
		if ($this->_body !== null) {
			return $this->_body->publishEmailFile($fullPath, $checkTimestamp);
		}
		return parent::publishFilePath($fullPath, $checkTimestamp);
	}

	/**
	 * Instantiates the template in a control.
	 * @param \Prado\Web\UI\TControl $tplControl the control that owns the template.
	 * @param ?\Prado\Web\UI\TControl $parentControl the parent of the instantiated controls; null for the owner.
	 */
	public function instantiateIn($tplControl, $parentControl = null)
	{
		$this->_body = $tplControl instanceof TEmailBody ? $tplControl : null;
		try {
			parent::instantiateIn($tplControl, $parentControl);
		} finally {
			$this->_body = null;
		}
	}
}
