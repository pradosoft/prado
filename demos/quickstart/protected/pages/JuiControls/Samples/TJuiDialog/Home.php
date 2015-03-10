<?php

class Home extends TPage
{

	public function bt1Click($sender, $param)
	{
		$this->dlg1->open();
	}

	public function bt2Click($sender, $param)
	{
		$this->dlg2->open();
	}

	public function bt3Click($sender, $param)
	{
		$this->dlg3->open();
	}

	public function dlg3Ok($sender, $param)
	{
		$this->lbl3->Text="Button Ok clicked";
		$this->dlg3->close();
	}

	public function dlg3Cancel($sender, $param)
	{
		$this->lbl3->Text="Button Cancel clicked";
		$this->dlg3->close();
	}

}