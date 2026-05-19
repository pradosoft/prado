<?php

class ActivePanelTest extends TPage
{
	public function callback1_requested($sender, $param)
	{
		$this->content1->visible = true;
		$this->panel1->render($param->NewWriter);
	}
}
