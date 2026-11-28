<?php

/**
 * TEmailTrackingEventParameter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\Email\Bounce\TEmailBounce;

/**
 * TEmailTrackingEventParameter class.
 *
 * TEmailTrackingEventParameter is the parameter of the {@see TEmailModule} events
 * about a delivered email.
 *
 * | Event | Set |
 * |---|---|
 * | onOpenEmail | TrackingId, Info |
 * | onClickEmail | TrackingId, Info, Url |
 * | onUnsubscribe | TrackingId, Info, Suppressed |
 * | onBounce | Bounce, and when the store matched the bounce to an email: TrackingId, Info, Suppressed |
 *
 * The Info is the stored fields of the email as {@see Store\IEmailStore} describes them.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailTrackingEventParameter extends \Prado\TEventParameter
{
	/**
	 * @param ?string $_trackingId the tracking ID of the email.
	 * @param ?array $_info the stored fields of the email.
	 * @param ?string $_url the target URL of a clicked link.
	 * @param ?TEmailBounce $_bounce the bounce of one recipient.
	 */
	public function __construct(
		private ?string $_trackingId = null,
		private ?array $_info = null,
		private ?string $_url = null,
		private ?TEmailBounce $_bounce = null
	) {
		parent::__construct($_info);
	}

	/**
	 * @return ?string the tracking ID of the email, null for a bounce that matched no email.
	 */
	public function getTrackingId(): ?string
	{
		return $this->_trackingId;
	}

	/**
	 * @return ?array the stored fields of the email, null for a bounce that matched no email.
	 */
	public function getInfo(): ?array
	{
		return $this->_info;
	}

	/**
	 * @return ?string the target URL of a clicked link, null for the other events.
	 */
	public function getUrl(): ?string
	{
		return $this->_url;
	}

	/**
	 * @return ?TEmailBounce the bounce of one recipient, null for the other events.
	 */
	public function getBounce(): ?TEmailBounce
	{
		return $this->_bounce;
	}

	/**
	 * @return ?string the address that the unsubscribe or the bounce suppressed, null when it suppressed none.
	 */
	public function getSuppressed(): ?string
	{
		return $this->_info['suppressed'] ?? null;
	}
}
