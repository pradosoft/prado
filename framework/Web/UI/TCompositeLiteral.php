<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * TCompositeLiteral class
 *
 * TCompositeLiteral is used internally by {@link TTemplate} for representing
 * consecutive static strings, expressions and statements.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TCompositeLiteral extends \Prado\TComponent implements IRenderable, IBindable
{
	const TYPE_EXPRESSION = 0;
	const TYPE_STATEMENTS = 1;
	const TYPE_DATABINDING = 2;
	private $_container;
	private $_items = [];
	private $_expressions = [];
	private $_statements = [];
	private $_bindings = [];

	/**
	 * Constructor.
	 * @param array $items list of items to be represented by TCompositeLiteral
	 */
	public function __construct($items)
	{
		$this->_items = [];
		$this->_expressions = [];
		$this->_statements = [];
		foreach ($items as $id => $item) {
			if (is_array($item)) {
				if ($item[0] === self::TYPE_EXPRESSION) {
					$this->_expressions[$id] = $item[1];
				} elseif ($item[0] === self::TYPE_STATEMENTS) {
					$this->_statements[$id] = $item[1];
				} elseif ($item[0] === self::TYPE_DATABINDING) {
					$this->_bindings[$id] = $item[1];
				}
				$this->_items[$id] = '';
			} else {
				$this->_items[$id] = $item;
			}
		}
	}

	/**
	 * @return TComponent container of this component. It serves as the evaluation context of expressions and statements.
	 */
	public function getContainer()
	{
		return $this->_container;
	}

	/**
	 * @param \Prado\TComponent $value container of this component. It serves as the evaluation context of expressions and statements.
	 */
	public function setContainer(\Prado\TComponent $value)
	{
		$this->_container = $value;
	}

	/**
	 * Evaluates the expressions and/or statements in the component.
	 */
	public function evaluateDynamicContent()
	{
		$context = $this->_container === null ? $this : $this->_container;
		foreach ($this->_expressions as $id => $expression) {
			$this->_items[$id] = $context->evaluateExpression($expression);
		}
		foreach ($this->_statements as $id => $statement) {
			$this->_items[$id] = $context->evaluateStatements($statement);
		}
	}

	/**
	 * Performs databindings.
	 * This method is required by {@link IBindable}
	 */
	public function dataBind()
	{
		$context = $this->_container === null ? $this : $this->_container;
		foreach ($this->_bindings as $id => $binding) {
			$this->_items[$id] = $context->evaluateExpression($binding);
		}
	}

	/**
	 * Renders the content stored in this component.
	 * This method is required by {@link IRenderable}
	 * @param ITextWriter $writer
	 */
	public function render($writer)
	{
		$writer->write(implode('', $this->_items));
	}
}
