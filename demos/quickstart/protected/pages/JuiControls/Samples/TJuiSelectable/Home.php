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
		}
	}

	public function repeater1_onSelectedIndexChanged($sender, $param)
	{
		$this->label1->Text="Selected items:";
		$items = $param->getSelectedIndexes();

		foreach($items as $index)
			$this->label1->Text.=' '.$this->data[$index];
	}
}
