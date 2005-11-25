<?php
/**
 * TExpression class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TExpression class
 *
 * TExpression evaluates a PHP expression and renders the result.
 * The expression is evaluated during rendering stage. You can set
 * it via the property <b>Expression</b>. You should also specify
 * the context object by <b>Context</b> property which is used as
 * the object in which the expression is evaluated. If the <b>Context</b>
 * property is not set, the TExpression component itself will be
 * assumed as the context.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TExpression extends TControl
{
	private $_e='';

	/**
	 * @return string the expression to be evaluated
	 */
	public function getExpression()
	{
		return $this->_e;
	}

	/**
	 * Sets the expression of the TExpression
	 * @param string the expression to be set
	 */
	public function setExpression($value)
	{
		$this->_e=$value;
	}

	/**
	 * Renders the evaluation result of the expression.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function render($writer)
	{
		if($this->_e!=='')
			$writer->write($this->evaluateExpression($this->_e));
	}
}

?>
