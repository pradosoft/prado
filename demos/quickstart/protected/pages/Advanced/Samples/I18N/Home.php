<?php

class Home extends TPage
{
	/**
	 * Change the globalization culture using value from request "lang" parameter.
	 */
	protected function onPreInit($param)
	{
		parent::onPreInit($param);
		$lang = $this->Request['lang'];
		if(CultureInfo::validCulture($lang)) //only valid lang is permitted
			$this->Application->Globalization->Culture = $lang;
	}

	/**
	 * Initialize the page with some arbituary data.
	 * @param TEventParameter event parameter.
	 */
	protected function onLoad($param)
	{
		parent::onLoad($param);
		$time1 = $this->Time1;
		$time1->Value = time();

		$number2 = $this->Number2;
		$number2->Value = 46412.416;

		$this->dataBind();
	}

	/**
	 * Get the localized current culture name.
	 * @return string localized curreny culture name.
	 */
	public function getCurrentCulture()
	{
		$culture = $this->getApplication()->getGlobalization()->getCulture();
		$cultureInfo = new CultureInfo($culture);
		return $cultureInfo->getNativeName();
	}
}

?>