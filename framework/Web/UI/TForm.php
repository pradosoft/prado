<?php

class TForm extends TControl
{
	public function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->setForm($this);
	}

	protected function addAttributesToRender($writer)
	{
		$attributes=$this->getAttributes();
		$writer->addAttribute('method',$this->getMethod());
		$writer->addAttribute('action',$this->getRequest()->getRequestURI());
		if(($enctype=$this->getEnctype())!=='')
			$writer->addAttribute('enctype',$enctype);
		$attributes->remove('action');

		$page=$this->getPage();
		/*
		$onsubmit=$page->getClientOnSubmitEvent();
		if($onsubmit!=='')
		{
			if(($existing=$attributes->itemAt('onsubmit'))!=='')
			{
				$page->getClientScript()->registerOnSubmitStatement('TForm:OnSubmitScript',$existing);
				$attributes->remove('onsubmit');
			}
			if($page->getClientSupportsJavaScript())
				$writer->addAttribute('onsubmit',$onsubmit);
		}*/
		if($this->getDefaultButton()!=='')
		{//todo
		/*
			$control=$this->findControl($this->getDefaultButton());
			if(!$control)
				$control=$page->findControl($this->getDefaultButton());
			if($control instanceof IButtonControl)
				$page->getClientScript()->registerDefaultButtonScript($control,$writer,false);
			else
				throw new Exception('Only IButtonControl can be default button.');
				*/
		}
		$writer->addAttribute('id',$this->getClientID());
		foreach($attributes as $name=>$value)
			$writer->addAttribute($name,$value);
	}

	/**
	 * @internal
	 */
	protected function render($writer)
	{
		$this->addAttributesToRender($writer);
		$writer->renderBeginTag('form');
		$page=$this->getPage();
		$page->beginFormRender($writer);
		$this->renderChildren($writer);
		$page->endFormRender($writer);
		$writer->renderEndTag();
	}

	public function getDefaultButton()
	{
		return $this->getViewState('DefaultButton','');
	}

	public function setDefaultButton($value)
	{
		$this->setViewState('DefaultButton',$value,'');
	}

	public function getDefaultFocus()
	{
		return $this->getViewState('DefaultFocus','');
	}

	public function setDefaultFocus($value)
	{
		$this->setViewState('DefaultFocus',$value,'');
	}

	public function getMethod()
	{
		return $this->getViewState('Method','post');
	}

	public function setMethod($value)
	{
		$this->setViewState('Method',$value,'post');
	}

	public function getEnctype()
	{
		return $this->getViewState('Enctype','');
	}

	public function setEnctype($value)
	{
		$this->setViewState('Enctype',$value,'');
	}
/*
	public function getSubmitDisabledControls()
	{
		return $this->getViewState('SubmitDisabledControls',false);
	}

	public function setSubmitDisabledControls($value)
	{
		$this->setViewState('SubmitDisabledControls',TPropertyValue::ensureBoolean($value),false);
	}
*/
	public function getName()
	{
		return $this->getUniqueID();
	}

	public function getTarget()
	{
		return $this->getViewState('Target','');
	}

	public function setTarget($value)
	{
		$this->setViewState('Target',$value,'');
	}
}

?>