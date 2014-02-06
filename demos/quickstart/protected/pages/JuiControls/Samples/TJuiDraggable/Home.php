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
}