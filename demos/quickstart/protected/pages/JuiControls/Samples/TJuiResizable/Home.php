<?php

class Home extends TPage
{
	protected function resize1_start($sender, $param)
	{
		$size=$param->getCallbackParameter()->size;
		$this->label1->Text.="<br/>Start: ".intval($size->width)." x ".intval($size->height);
	}

	protected function resize1_stop($sender, $param)
	{
		$size=$param->getCallbackParameter()->size;
		$this->label1->Text.="<br/>Stop: ".intval($size->width)." x ".intval($size->height);
	}

	protected function check1($sender, $param)
	{
		$this->resize1->getOptions()->alsoResize = $sender->getChecked() ? '#box' : '';
	}

	protected function check2($sender, $param)
	{
		$this->resize1->getOptions()->animate = TPropertyValue::ensureString($sender->getChecked());
	}

	protected function check3($sender, $param)
	{
		$this->resize1->getOptions()->grid = $sender->getChecked() ? '30' : false;
	}
}