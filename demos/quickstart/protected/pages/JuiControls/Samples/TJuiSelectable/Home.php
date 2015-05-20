<?php

class Home extends TPage
{
	protected $data = array(
		'PRADO',
		'quickstart',
		'tutorial',
		'sample',
		'for the',
		'TJuiSortable',
		'control',
		);

	public function onLoad($param)
	{
		if(!$this->IsPostback)
		{
			$this->repeater1->DataSource=$this->data;
			$this->repeater1->dataBind();
			$this->label1->setText('<i>none</i>');
		}
	}

	public function repeater1_onStop($sender, $param)
	{
		$items = $param->getCallbackParameter()->index;
		foreach ($items as $key => $index) $items[$key] = $this->data[$index];
		$this->label1->Text = implode(' ', $items);
	}

	public function select1($sender, $param) {
	  $this->repeater1->getOptions()->disabled  = $sender->getChecked();
	}

	public function select2($sender, $param) {
	  $this->repeater1->getOptions()->distance = $sender->getChecked() ? 100 : 0;
	}
}
