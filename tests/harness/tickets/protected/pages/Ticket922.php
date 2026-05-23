<?php

class Ticket922 extends TPage
{
	public function processString($sender, $param)
	{
		$text = $this->Text->Text;
		$url = $this->getService()->constructUrl('Ticket922', ['text' => $text]);
		$this->getResponse()->redirect($url);
	}

	public function onLoad($param)
	{
		if ($this->Request->contains('text')) {
			$this->Result->setText($this->Request->itemAt('text'));
		}
	}
}
