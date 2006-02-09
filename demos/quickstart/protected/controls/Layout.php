<?php

class Layout extends TTemplateControl
{
	public function __construct()
	{
		if(isset($this->Request['notheme']))
			$this->Service->RequestedPage->EnableTheming=false;
		parent::__construct();
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		$url=$this->Request->RequestUri;
		if(strpos($url,'?')===false)
			$url.='?notheme=true';
		else
			$url.='&notheme=true';
		$this->PrinterLink->NavigateUrl=$url;

		if(isset($this->Request['notheme']))
		{
			$this->MainMenu->Visible=false;
			$this->TopicPanel->Visible=false;
		}
	}
}

?>