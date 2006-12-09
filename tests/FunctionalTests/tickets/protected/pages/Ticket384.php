<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket384 extends TPage
{
	public function initRecursive($namingContainer=null)
	{
		parent::initRecursive($namingContainer);
		$this->AutoCompleteRepeater->setDataSource(array(1, 2));
		$this->AutoCompleteRepeater->dataBind();
	}

	public function submitCallback($sender, $param)
	{
		$this->AutoCompleteRepeater->setDataSource(array(1,2,3,4));
		$this->AutoCompleteRepeater->dataBind();
		$this->AutoCompletePanel->render($this->getResponse()->createHtmlWriter());
	}

	public function suggestCountries($sender, $param)
	{
		$sender->setDataSource($this->matchCountries($param->getCallbackParameter()));
		$sender->dataBind();
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