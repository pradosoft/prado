<?php

/*
 * Created on 25/04/2006
 */

class TActiveControl extends TControl implements ICallbackEventHandler, IActiveControl
{	
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	public function raiseCallbackEvent($param)
	{
		var_dump($param);
		$client = $this->getPage()->getCallbackClient();
		$client->hide($this);
	}
} 

?>
