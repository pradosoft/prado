<?php

/**
 * TEmailTrackerImage class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI\WebControls;

/**
 * TEmailTrackerImage class.
 *
 * TEmailTrackerImage renders a 1x1 transparent image whose URL is the "open" request
 * of the {@see \Prado\Util\Email\TDbEmailService}.  Loading the image counts an open of
 * the email.  Mail clients that block remote images do not count opens.
 *
 * The image is hidden from assistive technology: it has an empty alt and the
 * presentation role.  Without a tracking service the control renders nothing.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailTrackerImage extends \Prado\Web\UI\TControl
{
	use TEmailControlTrait;

	/**
	 * Renders the tracker image.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function render($writer)
	{
		if (!$this->getHasTrackingService()) {
			return;
		}
		$writer->addAttribute('src', $this->getEmailTrackingUrl(['open' => 1]));
		$writer->addAttribute('width', '1');
		$writer->addAttribute('height', '1');
		$writer->addAttribute('alt', '');
		$writer->addAttribute('role', 'presentation');
		$writer->addAttribute('style', 'display:block;width:1px;height:1px;border:0;');
		$writer->renderBeginTag('img');
		$writer->renderEndTag();
	}
}
