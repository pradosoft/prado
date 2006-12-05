<?php

class NullStateTest extends TPage
{
	public function btnTest_OnCallback($sender,$param)
	{
		$this->lblTest->Text = "Testing";
	}
}

?>