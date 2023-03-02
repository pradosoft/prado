<?php
/**
 * TRatingListTest.php
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @version Creation Date: Oct 13, 2008
 */

/**
 * TRatingListTest.php class
 *
 *
 *
 * Properties
 * -
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @version Modified Date: Oct 13, 2008
 *
 * Modifications:
 */
class ActiveRatingListHoverCaptionTest extends TPage
{
	public function ratingChanged($sender, $param)
	{
		$sender->setCaption($sender->getRating() . ' : ' . $sender->getSelectedValue());
	}
}
