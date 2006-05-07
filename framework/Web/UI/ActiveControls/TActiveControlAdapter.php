<?php
/*
 * Created on 29/04/2006
 */

class TActiveControlAdapter extends TControlAdapter
{
	private static $_renderedPosts = false;
	
	/**
	 * Render the callback request post data loaders once only.
	 */
	public function render($writer)
	{
		if(!self::$_renderedPosts)
		{
			$cs = $this->getPage()->getClientScript();
			$cs->registerPradoScript('ajax');
			$options = TJavascript::encode($this->getPage()->getPostDataLoaders(),false);
			$script = "Prado.CallbackRequest.PostDataLoaders = {$options};";
			$cs->registerEndScript(get_class($this), $script);
			self::$_renderedPosts = true;
		}
		parent::render($writer);
		if($this->getPage()->getIsCallback())
			$this->getPage()->getCallbackClient()->replace($this->getControl(), $writer);
	}
} 
?>