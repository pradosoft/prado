<?php

class ActivePanelTest extends TPage
{
	function callback1_requested($sender, $param)
	{
		$this->content1->visible = true;
		$this->panel1->flush($param->output);
	}
}

?>