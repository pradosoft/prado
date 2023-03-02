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
class ActiveRatingListRatingTest extends TPage
{
	public function ratingChanged($sender, $param)
	{
		$this->Status->setText('Rating: ' . $sender->getRating());
	}
	
	public function setRating($sender, $param)
	{
		$this->RatingList->setRating(3);
		$this->ratingChanged($this->RatingList, null);
	}
}
