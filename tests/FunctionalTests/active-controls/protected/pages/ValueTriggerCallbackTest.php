<?php

class ValueTriggerCallbackTest extends TPage
{
	public function text1_changed($sender, $param)
	{
		$values = $param->getCallbackParameter();
		$this->label1->Text = "Old  = " . $values->OldValue . " : New Value = " . $values->NewValue;
	}
}
