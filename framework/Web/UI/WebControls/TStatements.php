<?php
/**
 * TStatements class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TStatements class
 *
 * TStatements executes one or several PHP statements and renders the display
 * generated during the execution. The execution happens during the rendering stage.
 * The PHP statements being executed can be set via the property
 * {@see setStatements Statements}. The context of the statemenets executed
 * is the TStatements object itself.
 *
 * Note, since TStatements allows execution of arbitrary PHP statements,
 * make sure {@see setStatements Statements} does not come directly from user input.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->_s !== '') {
			$writer->write($this->evaluateStatements($this->_s));
		}
	}
}
