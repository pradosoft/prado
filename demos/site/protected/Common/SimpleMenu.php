<?php

class SimpleMenu extends TControl
{
	public function addParsedObject($object)
	{
		if ($object instanceof SimpleMenuItem)
			parent::addParsedObject($object);
	}

	public function render($writer)
	{
		$writer->renderBeginTag("ul");
		parent::renderChildren($writer);
		$writer->renderEndTag();
	}

}

class SimpleMenuItem extends TControl
{

	public function getPath()
	{
		return $this->getControlState("Path", null);
	}

	public function setPath($value)
	{
		$this->setControlState("Path", TPropertyValue::ensureString($value));
	}

	public function getUrl()
	{
		return $this->getControlState("Url", null);
	}

	public function setUrl($value)
	{
		$this->setControlState("Url", TPropertyValue::ensureString($value));
	}
	
	public function getTarget()
	{
		return $this->getControlState("Target", null);
	}

	public function setTarget($value)
	{
		$this->setControlState("Target", TPropertyValue::ensureString($value));
	}
	
	public function getText()
	{
		return $this->getControlState("Text", $this->getID());
	}

	public function setText($value) {
		$this->setControlState("Text", TPropertyValue::ensureString($value));
	}

	public function render($writer)
	{
		$writer->renderBeginTag("li");

		if(null !== $path = $this->getPath())
		{
			$writer->addAttribute('href', $this->Service->constructUrl($path));

			if($path == $this->Page->getPagePath())
				$writer->addAttribute('class', 'active');
		} elseif(null !== $url = $this->getUrl()) {
			$writer->addAttribute('href', $url);
		}

		if($this->getTarget() !== null)
			$writer->addAttribute('target', $this->getTarget());

		$writer->renderBeginTag("a");

		$writer->write(THttpUtility::htmlEncode($this->getText()));

		$writer->renderEndTag();
		$writer->renderEndTag();
	}
}
