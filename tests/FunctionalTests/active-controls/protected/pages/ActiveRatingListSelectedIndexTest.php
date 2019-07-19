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
class ActiveRatingListSelectedIndexTest extends TPage
{
	public function ratingChanged($sender, $param)
	{
		$this->Status->setText('SelectedIndex: ' . $sender->getSelectedIndex());
	}
	
	public function setSelectedIndex($sender, $param)
	{
		$this->RatingList->setSelectedIndex(5);
		$this->ratingChanged($this->RatingList, null);
	}
}
