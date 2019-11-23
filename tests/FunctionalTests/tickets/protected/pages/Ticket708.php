<?php

class Ticket708 extends TPage
{
	public function onLoad($param)
	{
		if (!$this->getIsCallback() && !$this->getIsPostBack()) {
			$this->grid->dataSource = $this->getData();
			$this->grid->dataBind();
		}
	}

	protected function getData()
	{
		return [
			['RadioValue' => 1, 'Text' => 'Radio 1'],
			['RadioValue' => 2, 'Text' => 'Radio 2'],
			['RadioValue' => 3, 'Text' => 'Radio 3'],
			['RadioValue' => 4, 'Text' => 'Radio 4'],
		];
	}

	public function ChangeRadio($sender, $param)
	{
		$this->Result->setText("You have selected Radio Button #" . $sender->getValue());
	}
}
