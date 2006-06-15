<?php
/*
 * Created on 12/05/2006
 */

class TCallbackOptions extends TControl
{ 
	private $_clientSide;
	
	/**
	 * Callback client-side options can be set by setting the properties of
	 * the ClientSide property. E.g. <com:TCallbackOptions ClientSide.OnSuccess="..." />
	 * See {@link TCallbackClientSideOptions} for details on the properties of
	 * ClientSide.
	 * @return TCallbackClientSideOptions client-side callback options.
	 */
	public function getClientSide()
	{
		if(is_null($this->_clientSide))
			$this->_clientSide = $this->createClientSideOptions();
		return $this->_clientSide;
	}
	
	/**
	 * @return TCallbackClientSideOptions callback client-side options.
	 */
	protected function createClientSideOptions()
	{
		return new TCallbackClientSideOptions;
	}
	
}

?>