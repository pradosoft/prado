<?php

class SampleLayout extends TTemplateControl
{

	public function __construct()
	{
		if($this->Request->Items->contains('functionaltest'))
			$this->Service->RequestedPage->EnableTheming=false;
		parent::__construct();
	}

	public function toggleTopicPanel($sender,$param)
	{
		$this->TopicPanel->Visible=!$this->TopicPanel->Visible;
		if($this->TopicPanel->Visible)
			$sender->Text="Hide TOC";
		else
			$sender->Text="Show TOC";
	}
}

?>