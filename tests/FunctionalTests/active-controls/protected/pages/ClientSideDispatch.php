<?php

class ClientSideDispatch extends TPage
{
	public function method1($sender, $param)
	{
		$this->status1->Text = "Method 1 callback with parameter: {$param->CallbackParameter}";
	}

	public function method2($sender, $param)
	{
		$this->status2->Text = "Method 2 callback";
	}
}
