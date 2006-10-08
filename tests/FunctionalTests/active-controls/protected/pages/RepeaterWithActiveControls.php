<?php

class RepeaterWithActiveControls extends TPage
{
	private $_data=array('Hello', 'World', 'Prado');

	private $_status='';

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsCallback)
		{
			$this->repeater1->DataSource = $this->_data;
			$this->repeater1->dataBind();
		}
	}

	public function label_changed($sender, $param)
	{
		$index = $sender->getParent()->ItemIndex + 1;
		$this->_status .= " Update textbox {$index}: ".$sender->Text;
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if(trim($this->_status))
			$this->label1->Text = $this->_status;
	}

	public function enable_edit($sender, $param)
	{
		if($this->update_button->Enabled==false)
		{
			for($i = 0; $i<count($this->repeater1->Items); $i++)
			{
				$textbox = $this->repeater1->Items[$i]->edit_box;
				$textbox->DisplayTextBox = true;
			}
			$this->update_button->Enabled = true;
			$sender->Enabled=false;
		}
	}

	public function disable_edit($sender, $param)
	{
		if($this->update_button->Enabled==true)
		{
			for($i = 0; $i<count($this->repeater1->Items); $i++)
			{
				$textbox = $this->repeater1->Items[$i]->edit_box;
				$textbox->DisplayTextBox = false;
			}
			$this->edit_button->Enabled = true;
			$sender->Enabled=false;
		}
	}
}

?>