<?php

class FocusControl extends TPage
{
	public function doFocus($sender, $param)
	{
		$selected = $this->list->SelectedIndex;
		if ($selected >= 0) {
			$id = "button" . ($selected + 1);
			$controlID = $this->{$id}->getClientID();
			$this->getClientScript()->registerFocusControl($controlID);
		}
	}
}
