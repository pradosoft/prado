<?php

/*
 * Created on 25/04/2006
 */

class TActiveControl extends TControl implements ICallbackEventHandler, IActiveControl
{	
	private $_clientSide;
	
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}
	
	public function getClientSide()
	{
		if(is_null($this->_clientSide))
			$this->_clientSide = $this->createClientSideOptions();
		return $this->_clientSide;
	}
	
	protected function createClientSideOptions()
	{
		$client = new TCallbackClientSideOptions;
		return $client;
	}

	public function raiseCallbackEvent($param)
	{
		var_dump($param->getParameter());
		$param->Output->write($param->Parameter);
		$client = $this->getPage()->getCallbackClient();
		$client->hide($this);
		$client->toggle($this);
		$client->update($this, 1);
		$param->setData(array("asdasdad",1));
	}
	
	public function getCallbackReference()
	{
	//	$formID = $this->getPage()->getForm()->getClientID();
	//	$this->getClientSide()->setValidationForm($formID);		
		return $this->getPage()->getClientScript()->getCallbackReference($this);
	}
} 

?>
