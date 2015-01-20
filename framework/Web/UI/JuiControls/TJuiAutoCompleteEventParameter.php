<?php
/**
 * TJuiAutoComplete class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.ActiveControls
 */

/**
 * TJuiAutoCompleteEventParameter contains the {@link getToken Token} requested by
 * the user for a partial match of the suggestions.
 *
 * The {@link getSelectedIndex SelectedIndex} is a zero-based index of the
 * suggestion selected by the user, -1 if not suggestion is selected.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TJuiAutoCompleteEventParameter extends TCallbackEventParameter
{
	private $_selectedIndex=-1;

	/**
	 * Creates a new TCallbackEventParameter.
	 */
	public function __construct($response, $parameter, $index=-1)
	{
		parent::__construct($response, $parameter);
		$this->_selectedIndex=$index;
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