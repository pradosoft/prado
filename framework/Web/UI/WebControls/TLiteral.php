<?php
/**
 * TLiteral class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TLiteral class
 *
 * TLiteral reserves a location on the Web page to display static text or body content.
 * The TLiteral control is similar to the TLabel control, except the TLiteral
 * control does not allow you to apply a style to the displayed text.
 * You can programmatically control the text displayed in the control by setting
 * the <b>Text</b> property. If the <b>Text</b> property is empty, the content
 * enclosed within the TLiteral control will be displayed. This is very useful
 * for reserving a location on a page because you can add text and controls
 * as children of TLiteral control and they will be rendered at the place.
 *
 * Note, <b>Text</b> is not HTML encoded before it is displayed in the TLiteral component.
 * If the values for the component come from user input, be sure to validate the values
 * to help prevent security vulnerabilities.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TLiteral extends TControl
{
	/**
	 * @return string the static text of the TLiteral
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the static text of the TLiteral
	 * @param string the text to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	public function getEncode()
	{
		return $this->getViewState('Encode',false);
	}

	public function setEncode($value)
	{
		$this->setViewState('Encode',$value,false);
	}

	/**
	 * Renders the evaluation result of the statements.
	 * @param THtmlTextWriter the writer used for the rendering purpose
	 */
	protected function render($writer)
	{
		if(($text=$this->getText())!=='')
		{
			if($this->getEncode())
				$writer->write(THttpUtility::htmlEncode($text));
			else
				$writer->write($text);
		}
	}
}

?>