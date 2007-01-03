<?php

class LargePageStateTest2 extends TPage
{
	function onLoad($param)
	{
		parent::onLoad($param);
		for($i=0;$i<100;$i++) //may try 10000, but may crash PHP.
		{
			$label = new TLabel();
			$label->Text=" this is a very long label with some text $i:";
			$this->Panel1->Controls[] = $label;
		}
	}

	function button_clicked($sender, $param)
	{
		$this->status->Text .= ' Callback Clicked... ';
	}
}

?>