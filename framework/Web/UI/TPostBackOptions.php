<?php

class TPostBackOptions extends TComponent
{
	public $ActionUrl;
	public $Argument;
	public $AutoPostBack;
	public $ClientSubmit;
	public $PerformValidation;
	public $TargetControl;
	public $TrackFocus;
	public $ValidationGroup;

	public function __construct($targetControl=null,
								$argument='',
								$actionUrl='',
								$autoPostBack=false,
								$trackFocus=false,
								$clientSubmit=true,
								$performValidation=false,
								$validationGroup='')
	{
		$this->ActionUrl=$actionUrl;
		$this->Argument=$argument;
		$this->AutoPostBack=$autoPostBack;
		$this->ClientSubmit=$clientSubmit;
		$this->PerformValidation=$performValidation;
		$this->TargetControl=$targetControl;
		$this->TrackFocus=$trackFocus;
		$this->ValidationGroup=$validationGroup;
	}
}

?>