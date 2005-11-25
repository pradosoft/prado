<?php
/**
 * TLiteral class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TLiteral class
 *
 * TLiteral displays a static text on the Web page.
 * TLiteral is similar to the TLabel control, except that the TLiteral
 * control does not allow child controls and do not have style properties (e.g. BackColor, Font, etc.)
 * You can programmatically control the text displayed in the control by setting
 * the {@link setText Text} property. The text displayed may be HTML-encoded
 * if the {@link setEncode Encode} property is set true (defaults to false).
 *
 * Note, if {@link setEncode Encode} is false, make sure {@link setText Text}
 * does not contain unwanted characters that may bring security vulnerabilities.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TLiteral extends TControl
{
	/**
	 * Processes an object that is created during parsing template.
	 * This method overrides the parent implementation by forbidding any child controls.
	 * @param string|TComponent text string or component parsed and instantiated in template
	 */
	public function addParsedObject($object)
	{
		if($object instanceof TComponent)
			throw new TConfigurationException('literal_body_forbidden');
	}

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

	/**
	 * @return boolean whether the rendered text should be HTML-encoded. Defaults to false.
	 */
	public function getEncode()
	{
		return $this->getViewState('Encode',false);
	}

	/**
	 * @param boolean  whether the rendered text should be HTML-encoded.
	 */
	public function setEncode($value)
	{
		$this->setViewState('Encode',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * Renders the literal control.
	 * @param THtmlWriter the writer used for the rendering purpose
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