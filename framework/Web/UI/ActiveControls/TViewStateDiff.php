<?php
/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Calculates the viewstate changes during the request.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
abstract class TViewStateDiff
{
	/**
	 * @var mixed updated viewstate
	 */
	protected $_new;
	/**
	 * @var mixed viewstate value at the begining of the request.
	 */
	protected $_old;
	/**
	 * @var object null value.
	 */
	protected $_null;

	/**
	 * Constructor.
	 * @param mixed $new updated viewstate value.
	 * @param mixed $old viewstate value at the begining of the request.
	 * @param object $null representing the null value.
	 */
	public function __construct($new, $old, $null)
	{
		$this->_new = $new;
		$this->_old = $old;
		$this->_null = $null;
	}

	/**
	 * @return mixed view state changes, nullObject if no difference.
	 */
	abstract public function getDifference();
}
