<?php

/**
 * TEmailUnsubscribeLink class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TEmailUnsubscribeLink class.
 *
 * TEmailUnsubscribeLink renders an unsubscribe link and sets the List-Unsubscribe
 * headers of the email, so mail clients show their own unsubscribe button.
 *
 * <code>
 * <com:Prado\Email\UI\WebControls\TEmailUnsubscribeLink />
 * </code>
 *
 * | NavigateUrl | Link and header URL | One-click header |
 * |---|---|---|
 * | empty | the "unsubscribe" request of {@see \Prado\Email\TEmailService} | yes |
 * | set | the NavigateUrl | when {@see setOneClick OneClick} is true |
 *
 * A one-click URL (RFC 8058) is HTTPS and unsubscribes on a POST of
 * "List-Unsubscribe=One-Click".  Large mailbox providers require one-click
 * unsubscribe from bulk senders.
 *
 * The service unsubscribes the recipient from the {@see \Prado\Email\TEmail::getList List}
 * of the email and raises {@see \Prado\Email\TEmailModule::onUnsubscribe}.  It
 * names one recipient, so an email with the link is sent to one To address, as
 * {@see \Prado\Email\TEmailModule::sendEach} does.
 *
 * The control renders nothing when it has no NavigateUrl and the email module has no tracking.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailUnsubscribeLink extends \Prado\Web\UI\WebControls\THyperLink
{
	use TEmailControlTrait;

	/**
	 * Sets the List-Unsubscribe headers of the email.
	 * @param mixed $param the event parameter.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getAddListUnsubscribeHeader() && ($url = $this->getUnsubscribeUrl()) !== '') {
			$this->getEmail()->setListUnsubscribe($url, $this->getMailto() ?: null, $this->getIsOneClick());
		}
	}

	/**
	 * Renders the link when it has a URL.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function render($writer)
	{
		if ($this->getUnsubscribeUrl() !== '') {
			parent::render($writer);
		}
	}

	/**
	 * Adds the attributes of the link; a link without a NavigateUrl gets the URL of the tracking service as its href.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if ($this->getNavigateUrl() === '' && $this->getEnabled(true)) {
			$writer->addAttribute('href', $this->getUnsubscribeUrl());
		}
	}

	/**
	 * @return string the NavigateUrl, else the unsubscribe URL of the tracking service, else ''.
	 */
	public function getUnsubscribeUrl(): string
	{
		$url = $this->getNavigateUrl();
		if ($url === '' && $this->getHasTracking()) {
			$url = $this->getEmailTrackingUrl(['unsubscribe' => 1]);
		}
		return $url;
	}

	/**
	 * @return bool whether the List-Unsubscribe-Post header is set: always for the tracking service, and by OneClick for a NavigateUrl.
	 */
	public function getIsOneClick(): bool
	{
		return $this->getNavigateUrl() === '' ? $this->getHasTracking() : $this->getOneClick();
	}

	/**
	 * @return string the link text. Defaults to the localized "Unsubscribe".
	 */
	public function getText()
	{
		return $this->getViewState('Text', Prado::localize('Unsubscribe'));
	}

	/**
	 * @return bool whether the NavigateUrl supports one-click unsubscribe. Defaults to false.
	 */
	public function getOneClick()
	{
		return $this->getViewState('OneClick', false);
	}

	/**
	 * @param bool|string $value whether the NavigateUrl unsubscribes on a POST of "List-Unsubscribe=One-Click".
	 */
	public function setOneClick($value)
	{
		$this->setViewState('OneClick', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the unsubscribe email address of the List-Unsubscribe header. Defaults to ''.
	 */
	public function getMailto()
	{
		return $this->getViewState('Mailto', '');
	}

	/**
	 * @param string $value an email address that unsubscribes the sender of a message to it.
	 */
	public function setMailto($value)
	{
		$this->setViewState('Mailto', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the List-Unsubscribe headers of the email are set. Defaults to true.
	 */
	public function getAddListUnsubscribeHeader()
	{
		return $this->getViewState('AddListUnsubscribeHeader', true);
	}

	/**
	 * @param bool|string $value whether the List-Unsubscribe headers of the email are set.
	 */
	public function setAddListUnsubscribeHeader($value)
	{
		$this->setViewState('AddListUnsubscribeHeader', TPropertyValue::ensureBoolean($value), true);
	}
}
