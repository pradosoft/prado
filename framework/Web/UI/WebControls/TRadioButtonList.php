<?php

class TRadioButtonList extends TCheckBoxList
{
	protected function createRepeatedControl()
	{
		return new TRadioButton;
	}

	public function loadPostData($key,$values)
	{
	}

	public function raisePostDataChangedEvent()
	{
	}
}

?>