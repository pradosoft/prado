<?php

Prado::Using('System.Web.UI.ActiveControls.*');

class Ticket719 extends TPage
{
	protected function matchCountries($token)
	{
		$info = Prado::createComponent('System.I18N.core.CultureInfo', 'en');
		$list = array();
		$count = 0;
		$token = strtolower($token);
		foreach($info->getCountries() as $country)
		{
			if(strpos(strtolower($country), $token) === 0)
			{
				$list[] = $country;
				$count++;
				if($count > 10) break;
			}
		}
		return $list;
	}

	function suggestion_selected($sender, $param)
	{
		var_dump($param->selectedIndex);
	}
	public function suggestCountries($sender, $param)
	{
		$sender->setDataSource($this->matchCountries($param->Token));
		$sender->dataBind();
	}
	
	
	public function validForm ($sender, $param)
	{
		$this->Result->Text = "TextBox Content : ".$this->textbox->getText()."  -- Autocomplete Content :".$this->autocomplete->getText();
	}
}
?>