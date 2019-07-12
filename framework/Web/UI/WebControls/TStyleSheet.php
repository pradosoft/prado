<?php
/**
 * TStyleSheet class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TStyleSheet class.
 *
 * TStyleSheet represents the link to a stylesheet file and/or a piece of
 * stylesheet code. To specify the link to a CSS file, set {@link setStyleSheetUrl
 * StyleSheetUrl}.
 * Since Prado 3.3.1, it' possible to import css libraries bundled with
 * Prado from template via the {@link setPradoStyles PradoStyles} property.
 * Multiple Prado libraries can be specified using comma delimited string of the
 * css library to include on the page. For example,
 *
 * <code>
 * <com:TStyleSheet PradoStyles="bootstrap, jquery.ui.progressbar" />
 * </code>
 *
 * The child rendering result of TStyleSheet is treated as CSS code and
 * is rendered within an appropriate style HTML element.
 * Therefore, if the child content is not empty, you should place the TStyleSheet
 * control in the head section of your page to conform to the HTML standard.
 * If only CSS file URL is specified, you may place the control anywhere on your page
 * and the style element will be rendered in the right position.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.2
 */
class TStyleSheet extends \Prado\Web\UI\TControl
{
	/**
	 * @return string comma delimited list of css libraries to include
	 * on the page.
	 * @since 3.3.1
	 */
	public function getPradoStyles()
	{
		return $this->getViewState('PradoStyles', '');
	}

	/**
	 * Include css library to the current page. The current supported
	 * libraries are: "jquery-ui", "bootstrap" and all the split
	 * jquery.ui.componentname libraries.
	 *
	 * @param string $value comma delimited list of css libraries to include.
	 * @since 3.3.1
	 */
	public function setPradoStyles($value)
	{
		$this->setViewState('PradoStyles', $value, '');
	}

	/**
	 * @param string $value URL to the stylesheet file
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
	 * @return string media type of the CSS (such as 'print', 'screen', etc.). Defaults to empty, meaning the CSS applies to all media types.
	 */
	public function getMediaType()
	{
		return $this->getViewState('MediaType', '');
	}

	/**
	 * @param string $value media type of the CSS (such as 'print', 'screen', etc.). If empty, it means the CSS applies to all media types.
	 */
	public function setMediaType($value)
	{
		$this->setViewState('MediaType', $value, '');
	}

	/**
	 * Registers the stylesheet file and content to be rendered.
	 * This method overrides the parent implementation and is invoked right before rendering.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		$cs = $this->getPage()->getClientScript();

		$styles = preg_split('/,|\s+/', $this->getPradoStyles());
		foreach ($styles as $style) {
			if (($style = trim($style)) !== '') {
				$cs->registerPradoStyle($style);
			}
		}

		if (($url = $this->getStyleSheetUrl()) !== '') {
			$cs->registerStyleSheetFile($url, $url, $this->getMediaType());
		}
	}

	/**
	 * Renders the control.
	 * This method overrides the parent implementation and renders nothing.
	 * @param ITextWriter $writer writer
	 */
	public function render($writer)
	{
		if ($this->getHasControls()) {
			$writer->write("<style type=\"text/css\">\n/*<![CDATA[*/\n");
			$this->renderChildren($writer);
			$writer->write("\n/*]]>*/\n</style>\n");
		}
	}
}
