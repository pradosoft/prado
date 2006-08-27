<?php
/*
 * Created on 7/05/2006
 */

class AutoCompleteTest extends TPage
{
	public function suggestCountries($sender, $param)
	{
		$sender->setDataSource($this->matchCountries($param->getParameter()));
		$sender->dataBind();
		$sender->render($param->getNewWriter());
		$this->label1->Text = "suggestion for ".$param->getParameter();
	}

	public function callback_requested($sender, $param)
	{
		$this->label1->Text = "Label 1: ".$this->textbox3->Text;
	}

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
}

?>