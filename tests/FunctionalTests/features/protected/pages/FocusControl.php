<?php

class FocusControl extends TPage
{
	function doFocus($sender, $param)
	{
		$selected = $this->list->SelectedIndex;
		if($selected >= 0)
		{
			$id = "button".($selected+1);
			$controlID = $this->{$id}->ClientID;
			$this->ClientScript->registerFocusControl($controlID);
		}
	}
}

?>