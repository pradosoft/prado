<?php
/**
 * TActiveClientScript class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TActiveClientScript.php 3144 2012-05-19 10:07:03Z ctrlaltca $
 * @package System.Web.UI.ActiveControls
 */

/**
 * TActiveClientScript class
 *
 * This is the active counterpart of the {@link TClientScript} class.
 *
 * TActiveClientScript has the ability to render itself on ajax 
 * callbacks. This means that every variable or function declared in javascript
 * code will be available to the page.
 *
 * Beware that when rendered on normal (postback) or ajax callbacks, some
 * javascript code won't behave in the same way. 
 * When rendered as part of a normal/postback response, scripts will execute instantly 
 * where they are in the page and in a synchronous fashion.
 * Instead, when they are rendered as part of a callback response,
 * they will be executed when all DOM modifications are complete and any dynamic
 * script file includes are loaded, out-of-band and practically all blocks at once,
 * regardless of where they actually occour in the original template/markup code.
 * This can potentially hurt compatibility and graceful fallback.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id: TActiveClientScript.php 3144 2012-05-19 10:07:03Z ctrlaltca $
 * @package System.Web.UI.ActiveControls
 * @since 3.2
 */

class TActiveClientScript extends TClientScript
{
	/**
	 * Renders the custom script file.
	 * @param THtmLWriter the renderer
	 */
	protected function renderCustomScriptFile($writer)
	{
		if(($scriptUrl = $this->getScriptUrl())!=='')
		{
			if($this->getPage()->getIsCallback())
			{
				$cs = $this->getPage()->getClientScript();
				$uniqueid=$this->ClientID.'_custom';
				if(!$cs->isScriptFileRegistered($uniqueid))
					$cs->registerScriptFile($uniqueid, $scriptUrl);
			} else {
				$writer->write("<script type=\"text/javascript\" src=\"$scriptUrl\"></script>\n");
			}
		}
	}

	/**
	 * Registers the body content as javascript.
	 * @param THtmlWriter the renderer
	 */
	protected function renderCustomScript($writer)
	{
		if($this->getHasControls())
		{
			if($this->getPage()->getIsCallback())
			{
				$extWriter= $this->getPage()->getResponse()->createHtmlWriter();
				$extWriter->write("/*<![CDATA[*/\n");
				$this->renderChildren($extWriter);
				$extWriter->write("\n/*]]>*/");
				$this->getPage()->getCallbackClient()->appendScriptBlock($extWriter);
			} else {
				$writer->write("<script type=\"text/javascript\">\n/*<![CDATA[*/\n");
				$this->renderChildren($writer);
				$writer->write("\n/*]]>*/\n</script>\n");
			}
		}
	}
}
