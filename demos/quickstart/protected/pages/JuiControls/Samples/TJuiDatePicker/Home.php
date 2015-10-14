<?php

class Home extends TPage
{

	public function change4($sender, $param)
	{
		$this->dp4->getOptions()->showAnim = $sender->getSelectedValue();
	}

	public function change5($sender, $param)
	{
		$value = $sender->getValue();
		switch ($value) {
		  case 'button':
		    $this->dp5->getOptions()->showButtonPanel = $sender->getChecked();
		    break;
		  case 'menu':
		    $this->dp5->getOptions()->changeYear = $this->dp5->getOptions()->changeMonth = $sender->getChecked();
		    break;
		  case 'week':
		    $this->dp5->getOptions()->showWeek = $sender->getChecked();
		    break;
		  case 'month':
		    $this->dp5->getOptions()->numberOfMonths =  $sender->getChecked() ? 3 : 1;
		    break;
		}
	}

}