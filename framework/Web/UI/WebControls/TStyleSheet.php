<?php
/**
 * TStyleSheet class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TStyleSheet class.
 *
 * TStyleSheet represents the link to a stylesheet file and/or a piece of
 * stylesheet code. To specify the link to a CSS file, set {@link setStyleSheetUrl StyleSheetUrl}.
 * The child rendering result of TStyleSheet is treated as CSS code and
 * is rendered within an appropriate style HTML element.
 * Therefore, if the child content is not empty, you should place the TStyleSheet
 * control in the head section of your page to conform to the HTML standard.
 * If only CSS file URL is specified, you may place the control anywhere on your page
 * and the style element will be rendered in the right position.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Tue Jul  4 04:38:16 EST 2006 $
 * @package System.Web.UI.WebControl
 * @since 3.0.2
 */
class TStyleSheet extends TControl
{
	/**
	 * @param string URL to the stylesheet file
	 */
	public function setStyleSheetUrl($value)
	{
		$this->setViewState('StyleSheetUrl', $value);
	}

	/**
	 * @return string URL to the stylesheet file
	 */
	public function getStyleSheetUrl()
	{
		return $this->getViewState('StyleSheetUrl', '');
	}

	/**
	 * Registers the stylesheet file and content to be rendered.
	 * This method overrides the parent implementation and is invoked right before rendering.
	 * @param mixed event parameter
	 */
	public function onPreRender($param)
	{
		if($this->getEnabled(true))
		{
			if(($url=$this->getStyleSheetUrl())!=='')
				$this->getPage()->getClientScript()->registerStyleSheetFile($url,$url);
		}
	}

	/**
	 * Renders the control.
	 * This method overrides the parent implementation and renders nothing.
	 * @param ITextWriter writer
	 */
	public function render($writer)
	{
		$textWriter=new TTextWriter;
		parent::renderChildren(new THtmlWriter($textWriter));
		if(($css=trim($textWriter->flush()))!=='')
			$writer->write("<style type=\"text/css\">\n/*<![CDATA[*/\n{$css}\n/*]]>*/\n</style>\n");
	}
}

?>