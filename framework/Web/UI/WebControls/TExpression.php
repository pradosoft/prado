<?php
/**
 * TExpression class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TExpression class
 *
 * TExpression evaluates a PHP expression and renders the result.
 * The expression is evaluated during the rendering stage. The expression being
 * evaluated can be set via the property {@link setExpression Expression}.
 * The context of the expression evaluated is the TExpression object itself.
 *
 * Note, since TExpression allows evaluation of arbitrary PHP expression,
 * make sure {@link setExpression Expression} does not come directly from user input.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TExpression extends \Prado\Web\UI\TControl
{
	/**
	 * @var string PHP expression to be evaluated
	 */
	private $_exp = '';

	/**
	 * @return string the expression to be evaluated
	 */
	public function getExpression()
	{
		return $this->_exp;
	}

	/**
	 * @param string $value the expression to be evaluated
	 */
	public function setExpression($value)
	{
		$this->_exp = $value;
	}

	/**
	 * Renders the evaluation result of the expression.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->_exp !== '') {
			$writer->write($this->evaluateExpression($this->_exp));
		}
	}
}
