<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
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
class TWizardNavigationContainer extends TControl implements INamingContainer
{
	private $_previousButton=null;
	private $_nextButton=null;
	private $_cancelButton=null;
	private $_completeButton=null;

	/**
	 * @return mixed the previous button
	 */
	public function getPreviousButton()
	{
		return $this->_previousButton;
	}

	/**
	 * @param mixed the previous button
	 */
	public function setPreviousButton($value)
	{
		$this->_previousButton=$value;
	}

	/**
	 * @return mixed the next button
	 */
	public function getNextButton()
	{
		return $this->_nextButton;
	}

	/**
	 * @param mixed the next button
	 */
	public function setNextButton($value)
	{
		$this->_nextButton=$value;
	}

	/**
	 * @return mixed the cancel button
	 */
	public function getCancelButton()
	{
		return $this->_cancelButton;
	}

	/**
	 * @param mixed the cancel button
	 */
	public function setCancelButton($value)
	{
		$this->_cancelButton=$value;
	}

	/**
	 * @return mixed the complete button
	 */
	public function getCompleteButton()
	{
		return $this->_completeButton;
	}

	/**
	 * @param mixed the complete button
	 */
	public function setCompleteButton($value)
	{
		$this->_completeButton=$value;
	}
}