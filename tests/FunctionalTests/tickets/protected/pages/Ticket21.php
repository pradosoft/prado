<?php

class Ticket21 extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		if(!$this->IsPostBack)
			$this->setViewState("clicks", 0);
	}

	public function doClick($sender, $param)
	{
		$clicks = $this->getViewState("clicks");
		$clicks++;
		$this->label1->setText("Radio button clicks: $clicks");
		$this->setViewState("clicks", $clicks);
	}
}

?>