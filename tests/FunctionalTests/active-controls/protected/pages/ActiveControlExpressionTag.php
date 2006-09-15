<?php

class ActiveControlExpressionTag extends TPage
{
	public function button1_clicked($sender, $param)
	{
		$this->subpanel1->Visible = true;
		$data = array('1', 'two');
		$this->repeater1->DataSource = $data;
		$this->repeater1->dataBind();
	}

	public function button1_callback($sender, $param)
	{
		$this->panel1->renderControl($param->NewWriter);
		$this->button2->Enabled=true;
	}

	public function button2_callback($sender, $param)
	{
		$this->panel2->Visible=true;
		$this->Page->CallbackClient->insertContentAfter('contents', $this->panel2);
	}
}

?>