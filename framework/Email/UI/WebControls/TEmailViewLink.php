<?php

/**
 * TEmailViewLink class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

use Prado\Prado;

/**
 * TEmailViewLink class.
 *
 * TEmailViewLink renders the link that shows the email in a web browser, through
 * the tracking service.  It renders nothing when the email module has no tracking.
 *
 * <code>
 * <com:Prado\Email\UI\WebControls\TEmailViewLink Text="View in your browser" />
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailViewLink extends \Prado\Web\UI\WebControls\THyperLink
{
	use TEmailControlTrait;

	/**
	 * Renders the link when the email module has tracking.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function render($writer)
	{
		if ($this->getHasTracking()) {
			parent::render($writer);
		}
	}

	/**
	 * @return string the URL that shows the email, empty when the email module has no tracking.
	 */
	public function getNavigateUrl()
	{
		return $this->getHasTracking() ? $this->getEmailTrackingUrl() : '';
	}

	/**
	 * @return string the link text. Defaults to the localized "View this email in your browser".
	 */
	public function getText()
	{
		return $this->getViewState('Text', Prado::localize('View this email in your browser'));
	}
}
