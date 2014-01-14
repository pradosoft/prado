<?php

class Home extends TPage
{
	public function drop1_ondrop($sender, $param)
	{
		$draggable=$param->getDroppedControl()->ID;
		$this->label1->Text="Dropped ".$draggable." at: <br/>Top=".$param->getOffsetTop()." Left=".$param->getOffsetLeft();
	}

	public function drop2_ondrop($sender, $param)
	{
		$draggable=$param->getDroppedControl()->ID;
		$this->label2->Text="Dropped ".$draggable." at: <br/>Top=".$param->getOffsetTop()." Left=".$param->getOffsetLeft();
	}
}
