<?php

/**
 * TEmailTrackedLink class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TEmailTrackedLink class.
 *
 * TEmailTrackedLink is a hyperlink whose clicks are counted by the
 * {@see \Prado\Util\Email\TDbEmailService}.  The NavigateUrl is registered with the
 * email as a numbered link and the rendered href points to the service, which counts
 * the click and redirects to the registered URL.  Only registered URLs redirect.
 * "{{KEY}}" placeholders in the NavigateUrl are replaced with the email's custom data
 * at redirect time.
 *
 * The link renders a plain href when {@see setTracked Tracked} is false or there is no
 * tracking service.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailTrackedLink extends \Prado\Web\UI\WebControls\THyperLink
{
	use TEmailControlTrait;

	/**
	 * @return string the href: the tracking URL, or the NavigateUrl when not tracked.
	 */
	public function getNavigateUrl()
	{
		$url = parent::getNavigateUrl();
		if ($url === '' || !$this->getTracked() || !$this->getHasTrackingService()) {
			return $url;
		}
		return $this->getEmailTrackingUrl(['click' => $this->getEmailPage()->addTrackedLink($url)]);
	}

	/**
	 * @return string the URL the link leads to, before tracking.
	 */
	public function getTargetUrl()
	{
		return parent::getNavigateUrl();
	}

	/**
	 * Renders the link body; a link without text shows its target URL.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function renderContents($writer)
	{
		if ($this->getText() === '' && $this->getImageUrl() === '' && !$this->getHasControls()) {
			$writer->write(THttpUtility::htmlEncode($this->getTargetUrl()));
			return;
		}
		parent::renderContents($writer);
	}

	/**
	 * @return bool whether clicks are tracked. Defaults to true.
	 */
	public function getTracked()
	{
		return $this->getViewState('Tracked', true);
	}

	/**
	 * @param bool $value whether clicks are tracked.
	 */
	public function setTracked($value)
	{
		$this->setViewState('Tracked', TPropertyValue::ensureBoolean($value), true);
	}
}
