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

	public function bt4Click($sender, $param)
	{
		$this->dlg4->open();
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

	public function dlg4title($sender, $param)
	{
		$this->dlg4->getOptions()->title = 'Title changed at ' . date('Y-m-d H:i:s');
	}

	public function dlg4width($sender, $param)
	{
		$this->dlg4->getOptions()->width += $this->dlg4->getOptions()->width > 400 ? -200 : 200;
	}

	public function dlg4pos($sender, $param)
	{
	  list($x, $y) = explode(' ', $this->dlg4->getOptions()->position);
	  if ($x == 'left') {
	    if ($y == 'top') $x = 'right';
	    else $y = 'top';
	  }
	  elseif ($x == 'right') {
	    if ($y == 'top') $y = 'bottom';
	    else $x = 'left';
	  }
	  else {
	    $x = 'left';
	    $y = 'top';
	  }
	  $this->dlg4->getOptions()->position = "$x $y";
	}

}