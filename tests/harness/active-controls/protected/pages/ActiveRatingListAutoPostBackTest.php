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
class ActiveRatingListAutoPostBackTest extends TPage
{
	public function ratingChanged($sender, $param)
	{
		$this->Status->setText($sender->getRating() . ' : ' . $sender->getSelectedValue());
	}
}
