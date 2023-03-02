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
class ActiveRatingListReadOnlyTest extends TPage
{
	public function ratingChanged($sender, $param)
	{
		$this->Status->setText($sender->getRating() . ' : ' . $sender->getSelectedValue());
	}

	public function readOnly($sender, $param)
	{
		$this->RatingList->setReadOnly(true);
		$this->Status->setText('ReadOnly=true');
	}
	
	public function writable($sender, $param)
	{
		$this->RatingList->setReadOnly(false);
		$this->Status->setText('ReadOnly=false');
	}
}
