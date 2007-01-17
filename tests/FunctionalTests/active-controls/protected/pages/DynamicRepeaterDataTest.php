<?php

class DynamicRepeaterDataTest extends TPage
{
	function button_clicked($sender, $param)
	{
		$this->_repeater->dataSource = array(1,2,3);
		$this->_repeater->dataBind();
	}

	function button_callback($sender, $param)
	{
		$this->panel1->render($param->NewWriter);
	}

	function rpt_button_clicked($sender, $param)
	{
		$item = $sender->NamingContainer;
		$item->label1->Text = $sender->Text;
	}
}

?>