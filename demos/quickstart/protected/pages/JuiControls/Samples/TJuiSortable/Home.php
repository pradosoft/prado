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
			$this->label1->Text = implode(' ', $this->data);
		}
	}

	public function repeater1_onStop($sender, $param)
	{
		$order = $param->getCallbackParameter()->index;
		foreach ($order as $key => $index) $order[$key] = $this->data[$index];
		$this->label1->Text = implode(' ', $order);
	}

	public function sort1($sender, $param)
	{
	  $this->repeater1->getOptions()->placeholder = $sender->getChecked() ? 'ui-sortable-highlight' : false;
	}

	public function sort2($sender, $param)
	{
	  $this->repeater1->getOptions()->revert = $sender->getChecked();
	}

	protected function sort3($sender, $param)
	{
    $this->repeater1->getOptions()->cursor = $sender->getSelectedValue();
	}
}
