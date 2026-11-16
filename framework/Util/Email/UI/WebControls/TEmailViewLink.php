<?php

/**
 * TEmailViewLink class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI\WebControls;

use Prado\Prado;

/**
 * TEmailViewLink class.
 *
 * TEmailViewLink links to the email in the browser through the
 * {@see \Prado\Util\Email\TDbEmailService}.  The NavigateUrl is set from the
 * tracking ID of the email.  Text defaults to "View this email in your browser".
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailViewLink extends \Prado\Web\UI\WebControls\THyperLink
{
	use TEmailControlTrait;

	/**
	 * @return string the URL of the email in the browser.
	 */
	public function getNavigateUrl()
	{
		return $this->getEmailTrackingUrl();
	}

	/**
	 * @return string the link text. Defaults to "View this email in your browser".
	 */
	public function getText()
	{
		return $this->getViewState('Text', Prado::localize('View this email in your browser'));
	}
}
