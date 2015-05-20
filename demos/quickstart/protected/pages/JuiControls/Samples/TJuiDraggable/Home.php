<?php

class Home extends TPage
{
	protected function drag1_start($sender, $param)
	{
		$offset=$param->getCallbackParameter()->offset;
		$this->label1->Text.="<br/>Start drag at (".$offset->left.",".$offset->top.")";
	}

	protected function drag1_stop($sender, $param)
	{
		$offset=$param->getCallbackParameter()->offset;
		$this->label1->Text.="<br/>Stop drop at (".$offset->left.",".$offset->top.")";
	}

	protected function drag1h($sender, $param)
	{
    $this->drag1->getOptions()->axis = 'x';
    $this->label1->Text.="<br/>Drag horizontally only";
	}

	protected function drag1v($sender, $param)
	{
    $this->drag1->getOptions()->axis = 'y';
    $this->label1->Text.="<br/>Drag vertically only";
	}

	protected function drag1b($sender, $param)
	{
    $this->drag1->getOptions()->axis = false;
    $this->label1->Text.="<br/>Drag all directions";
	}

	protected function drag2c($sender, $param)
	{
    $this->drag1->getOptions()->cursor = $sender->getSelectedValue();
    $this->label1->Text.="<br/>Change dragging cursor to ".$sender->getSelectedValue();
	}

	protected function drag3r($sender, $param)
	{
    $this->drag1->getOptions()->revert = $sender->getChecked();
    $this->label1->Text.="<br/>".($sender->getChecked()?'Turn on':'Turn off')." reverting to original position.";
	}
}