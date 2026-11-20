<?php

/**
 * TEmailTrackerImage class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

/**
 * TEmailTrackerImage class.
 *
 * TEmailTrackerImage renders the 1x1 image whose loading counts an open of the
 * email.  It renders nothing when the email module has no tracking.
 *
 * <code>
 * <com:Prado\Email\UI\WebControls\TEmailTrackerImage />
 * </code>
 *
 * The image has an empty alternate text and the presentation role, so assistive
 * technology skips it.  A mail client that blocks images, or loads them through a
 * proxy, makes the count differ from the number of readers.
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
		if (!$this->getHasTracking()) {
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
