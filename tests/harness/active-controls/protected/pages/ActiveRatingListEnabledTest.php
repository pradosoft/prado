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
class ActiveRatingListEnabledTest extends TPage
{
	public function ratingChanged($sender, $param)
	{
		$this->Status->setText($sender->getRating() . ' : ' . $sender->getSelectedValue());
	}
	
	public function enable($sender, $param)
	{
		$this->RatingList->setEnabled(true);
		$this->Status->setText('Enabled=true');
	}
	
	public function disable($sender, $param)
	{
		$this->RatingList->setEnabled(false);
		$this->Status->setText('Enabled=false');
	}
}
