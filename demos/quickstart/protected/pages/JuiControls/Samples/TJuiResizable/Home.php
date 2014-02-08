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
}