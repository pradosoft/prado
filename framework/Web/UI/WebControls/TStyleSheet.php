<?php

/**
 * TStyleSheet class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Tue Jul  4 04:38:16 EST 2006 $
 * @package System.Web.UI.WebControl
 * @since 3.0.2
 */
class TStyleSheet extends TControl
{
	/**
	 * @param string stylesheet url or asset resource.
	 */
	public function setStyleUrl($value)
	{
		$this->setViewState('StyleUrl', $value);	
	}
	
	/**
	 * @return string stylesheet url.
	 */
	public function getStyleUrl()
	{
		return $this->getViewState('StyleUrl', '');
	}
	
	/**
	 * Registers the stylesheet urls. Calls {@link renderChildren} to capture
	 * the body content to render the stylesheet in the head.
	 * @param mixed event parameter
	 */
	public function onPreRender($param)
	{
		if($this->getEnabled(true))
		{
			$this->registerCustomStyleSheetFile();
			$this->registerCustomStyleSheet();
		}
	}
	
	/**
	 * Overrides parent implementation, renders nothing.
	 */
	public function renderChildren($writer)
	{
		
	}
	
	/**
	 * Register custom stylesheet file.
	 */
	protected function registerCustomStyleSheetFile()
	{
		$cs = $this->getPage()->getClientScript();
		$url = $this->getStyleUrl();
		if(strlen($url) > 0)
			$cs->registerStyleSheetFile($url, $url);		
	}
	
	/**
	 * Registers  the body content as stylesheet.
	 */
	protected function registerCustomStyleSheet()
	{
		$cs = $this->getPage()->getClientScript();
		$textWriter=new TTextWriter;
		parent::renderChildren(new THtmlWriter($textWriter));
		$text = $textWriter->flush();
		if(strlen($text)>0)
			$cs->registerStyleSheet(sprintf('%08X', crc32($text)), $text);
	}
}
?>