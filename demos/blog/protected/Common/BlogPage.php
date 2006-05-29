<?php

class BlogPage extends TPage
{
	public function getDataAccess()
	{
		return $this->getApplication()->getModule('data');
	}

	public function gotoDefaultPage()
	{
		$this->Response->redirect($this->Service->constructUrl($this->Service->DefaultPage));
	}

	public function gotoPage($pagePath,$getParameters=null)
	{
		$this->Response->redirect($this->Service->constructUrl($pagePath,$getParameters));
	}

	public function reportError($errorCode)
	{
		$this->gotoPage('ErrorReport',array('id'=>$errorCode));
	}
}

?>