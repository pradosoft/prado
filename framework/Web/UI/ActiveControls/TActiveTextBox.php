<?php
/*
 * Created on 6/05/2006
 */

class TActiveTextBox extends TTextBox
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	public function getActiveControl()
	{
		return $this->getAdapter()->getActiveControl();
	}

	/**
	 * Client-side Text property can only be updated after the OnLoad stage.
	 * @param string text content for the textbox
	 */
	public function setText($value)
	{
		parent::setText($value);
		if($this->getActiveControl()->canUpdateClientSide() && $this->getHasLoadedPostData())
			$this->getPage()->getCallbackClient()->setValue($this, $value);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveTextBox';
	}
}

?>