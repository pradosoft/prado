<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TWizardNavigationContainer class.
 *
 * TWizardNavigationContainer represents a control containing
 * a wizard navigation. The navigation may contain a few buttons, including
 * {@link getPreviousButton PreviousButton}, {@link getNextButton NextButton},
 * {@link getCancelButton CancelButton}, {@link getCompleteButton CompleteButton}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TWizardNavigationContainer extends \Prado\Web\UI\TControl implements \Prado\Web\UI\INamingContainer
{
	private $_previousButton;
	private $_nextButton;
	private $_cancelButton;
	private $_completeButton;

	/**
	 * @return mixed the previous button
	 */
	public function getPreviousButton()
	{
		return $this->_previousButton;
	}

	/**
	 * @param mixed $value the previous button
	 */
	public function setPreviousButton($value)
	{
		$this->_previousButton = $value;
	}

	/**
	 * @return mixed the next button
	 */
	public function getNextButton()
	{
		return $this->_nextButton;
	}

	/**
	 * @param mixed $value the next button
	 */
	public function setNextButton($value)
	{
		$this->_nextButton = $value;
	}

	/**
	 * @return mixed the cancel button
	 */
	public function getCancelButton()
	{
		return $this->_cancelButton;
	}

	/**
	 * @param mixed $value the cancel button
	 */
	public function setCancelButton($value)
	{
		$this->_cancelButton = $value;
	}

	/**
	 * @return mixed the complete button
	 */
	public function getCompleteButton()
	{
		return $this->_completeButton;
	}

	/**
	 * @param mixed $value the complete button
	 */
	public function setCompleteButton($value)
	{
		$this->_completeButton = $value;
	}
}
