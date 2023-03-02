<?php

class DynamicRepeaterDataTest extends TPage
{
	public function button_clicked($sender, $param)
	{
		$this->_repeater->dataSource = [1, 2, 3];
		$this->_repeater->dataBind();
	}

	public function button_callback($sender, $param)
	{
		$this->panel1->render($param->NewWriter);
	}

	public function rpt_button_clicked($sender, $param)
	{
		$item = $sender->NamingContainer;
		$item->label1->Text = $sender->Text;
	}
}
