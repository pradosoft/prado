<?php
/**
 * TSlider class file.
 *
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 * @since 3.1.1
 */

/**
 * TSliderClientScript class.
 *
 * Client-side slider events {@link setOnChange OnChange} and {@line setOnMove OnMove}
 * can be modified through the {@link TSlider:: getClientSide ClientSide}
 * property of a slider.
 *
 * The current value of the slider can be get in the 'value' js variable
 *
 * The <tt>OnMove</tt> event is raised when the slider moves
 * The <tt>OnChange</tt> event is raised when the slider value is changed (or at the end of a move)
 *
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.1.1
 */
class TSliderClientScript extends TClientSideOptions
{
	/**
	 * Javascript code to execute when the slider value is changed.
	 * @param string javascript code
	 */
	public function setOnChange($javascript)
	{
		$code=TJavascript::quoteJsLiteral("function (value) { {$javascript} }");
		$this->setFunction('onChange', $code);
	}

	/**
	 * @return string javascript code to execute when the slider value is changed.
	 */
	public function getOnChange()
	{
		return $this->getOption('onChange');
	}

	/* Javascript code to execute when the slider moves.
	 * @param string javascript code
	 */
	public function setOnSlide($javascript)
	{
		$code=TJavascript::quoteJsLiteral("function (value) { {$javascript} }");
		$this->setFunction('onSlide', $code);
	}

	/**
	 * @return string javascript code to execute when the slider moves.
	 */
	public function getOnSlide()
	{
		return $this->getOption('onSlide');
	}
}
