<?php

class Home extends TPage 
{
	/**
	 * Change the globalization culture using value from request "lang" parameter.
	 */
	public function __construct()
	{
		$lang = $this->Request['lang'];
		if(CultureInfo::validCulture($lang)) //only valid lang is permitted
			$this->Application->Globalization->Culture = $lang;
		parent::__construct();
	}
	
	/**
	 * Initialize the page with some arbituary data.
	 * @param TEventParameter event parameter.
	 */	
	function onLoad($param) 
	{
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
	function getCurrentCulture() 
	{
		$culture = $this->Application->getGlobalization()->Culture;
		$cultureInfo = new CultureInfo($culture);
		return $cultureInfo->NativeName;
	}
}

?>