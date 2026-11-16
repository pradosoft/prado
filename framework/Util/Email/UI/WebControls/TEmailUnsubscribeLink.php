<?php

/**
 * TEmailUnsubscribeLink class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TEmailUnsubscribeLink class.
 *
 * TEmailUnsubscribeLink renders an unsubscribe link and sets the List-Unsubscribe
 * headers of the email, so mail clients show their own unsubscribe button.
 *
 * | NavigateUrl | Link and header URL | One-click header |
 * |---|---|---|
 * | empty | the "unsubscribe" request of {@see \Prado\Util\Email\TDbEmailService} | yes |
 * | set | the NavigateUrl | when {@see setOneClick OneClick} is true |
 *
 * A one-click URL (RFC 8058) must be HTTPS and must unsubscribe on a POST of
 * "List-Unsubscribe=One-Click".  Large mailbox providers require one-click unsubscribe
 * from bulk senders.  The service handles one-click POSTs and raises
 * {@see \Prado\Util\Email\TDbEmailModule::onUnsubscribe}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailUnsubscribeLink extends \Prado\Web\UI\WebControls\THyperLink
{
	use TEmailControlTrait;

	/**
	 * Sets the List-Unsubscribe headers before the email renders.
	 * @param mixed $param the event parameter.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getAddListUnsubscribeHeader() && ($url = $this->getNavigateUrl()) !== '') {
			$this->getEmailPage()->setListUnsubscribe($url, $this->getMailto() ?: null, $this->getIsOneClick());
		}
	}

	/**
	 * @return string the unsubscribe URL: the NavigateUrl, or the tracking service URL when empty.
	 */
	public function getNavigateUrl()
	{
		$url = parent::getNavigateUrl();
		if ($url === '' && $this->getHasTrackingService()) {
			$url = $this->getEmailTrackingUrl(['unsubscribe' => 1]);
		}
		return $url;
	}

	/**
	 * @return bool whether the header advertises one-click unsubscribe.
	 */
	public function getIsOneClick(): bool
	{
		return parent::getNavigateUrl() === '' ? $this->getHasTrackingService() : $this->getOneClick();
	}

	/**
	 * @return string the link text. Defaults to "Unsubscribe".
	 */
	public function getText()
	{
		return $this->getViewState('Text', Prado::localize('Unsubscribe'));
	}

	/**
	 * @return bool whether a set NavigateUrl supports one-click unsubscribe. Defaults to false.
	 */
	public function getOneClick()
	{
		return $this->getViewState('OneClick', false);
	}

	/**
	 * @param bool $value whether a set NavigateUrl supports one-click unsubscribe.
	 */
	public function setOneClick($value)
	{
		$this->setViewState('OneClick', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string an unsubscribe email address added to the List-Unsubscribe header. Defaults to empty.
	 */
	public function getMailto()
	{
		return $this->getViewState('Mailto', '');
	}

	/**
	 * @param string $value an unsubscribe email address added to the List-Unsubscribe header.
	 */
	public function setMailto($value)
	{
		$this->setViewState('Mailto', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the List-Unsubscribe headers are set on the email. Defaults to true.
	 */
	public function getAddListUnsubscribeHeader()
	{
		return $this->getViewState('AddListUnsubscribeHeader', true);
	}

	/**
	 * @param bool $value whether the List-Unsubscribe headers are set on the email.
	 */
	public function setAddListUnsubscribeHeader($value)
	{
		$this->setViewState('AddListUnsubscribeHeader', TPropertyValue::ensureBoolean($value), true);
	}
}
