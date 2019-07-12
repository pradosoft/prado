<?php
/**
 * TJuiAutoComplete class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Web\UI\ActiveControls\TCallbackEventParameter;

/**
 * TJuiAutoCompleteEventParameter contains the {@link getToken Token} requested by
 * the user for a partial match of the suggestions.
 *
 * The {@link getSelectedIndex SelectedIndex} is a zero-based index of the
 * suggestion selected by the user, -1 if not suggestion is selected.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\JuiControls
 * @since 3.1
 */
class TJuiAutoCompleteEventParameter extends TCallbackEventParameter
{
	private $_selectedIndex = -1;

	/**
	 * Creates a new TCallbackEventParameter.
	 * @param mixed $response
	 * @param mixed $parameter
	 * @param mixed $index
	 */
	public function __construct($response, $parameter, $index = -1)
	{
		parent::__construct($response, $parameter);
		$this->_selectedIndex = $index;
	}

	/**
	 * @return int selected suggestion zero-based index, -1 if not selected.
	 */
	public function getSelectedIndex()
	{
		return $this->_selectedIndex;
	}

	/**
	 * @return string token for matching a list of suggestions.
	 */
	public function getToken()
	{
		return $this->getCallbackParameter();
	}
}
