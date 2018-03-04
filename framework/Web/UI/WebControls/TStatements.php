<?php
/**
 * TStatements class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TStatements class
 *
 * TStatements executes one or several PHP statements and renders the display
 * generated during the execution. The execution happens during the rendering stage.
 * The PHP statements being executed can be set via the property
 * {@link setStatements Statements}. The context of the statemenets executed
 * is the TStatements object itself.
 *
 * Note, since TStatements allows execution of arbitrary PHP statements,
 * make sure {@link setStatements Statements} does not come directly from user input.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TStatements extends \Prado\Web\UI\TControl
{
	/**
	 * @var string PHP statements
	 */
	private $_s = '';

	/**
	 * @return string the statements to be executed
	 */
	public function getStatements()
	{
		return $this->_s;
	}

	/**
	 * @param string $value the PHP statements to be executed
	 */
	public function setStatements($value)
	{
		$this->_s = $value;
	}

	/**
	 * Renders the evaluation result of the statements.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->_s !== '') {
			$writer->write($this->evaluateStatements($this->_s));
		}
	}
}
