<?php
/**
 * TDropDownList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\TPropertyValue;

/**
 * TDropDownList class
 *
 * TDropDownList displays a dropdown list on a Web page.
 * It inherits all properties and events from {@see \Prado\Web\UI\WebControls\TListControl}.
 *
 * Since v3.0.3, TDropDownList starts to support optgroup. To specify an option group for
 * a list item, set a Group attribute with it,
 * ```php
 *  $listitem->Attributes->Group="Group Name";
 *  // or <com:TListItem Attributes.Group="Group Name" .../> in template
 * ```
 *
 * Since v3.1.1, TDropDownList starts to support prompt text. That is, a prompt item can be
 * displayed as the first list item by specifying either {@see setPromptText PromptText} or
 * {@see setPromptValue PromptValue}, or both. Choosing the prompt item will unselect the TDropDownList.
 *
 * When a prompt item is set, its index in the list is set to -1. So, the {@see getSelectedIndex SelectedIndex}
 * property is not affected by a prompt item: the items list will still be zero-based.
 *
 * The {@see clearSelection clearSelection} method will select the prompt item if existing, otherway the first
 * available item in the dropdown list will be selected.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDropDownList extends TListControl implements \Prado\Web\UI\IPostBackDataHandler, \Prado\Web\UI\IValidatable
{
	private $_dataChanged = false;
	private $_isValid = true;

	/**
	 * Adds attributes to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('name', $this->getUniqueID());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TDropDownList';
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the data of the component has been changed
	 */
	public function loadPostData($key, $values)
	{
		if (!$this->getEnabled(true)) {
			return false;
		}
		$this->ensureDataBound();
		$selection = $values[$key] ?? null;
		if ($selection !== null) {
			$index = $this->getItems()->findIndexByValue($selection, false);
			if ($this->getSelectedIndex() !== $index) {
				$this->setSelectedIndex($index);
				return $this->_dataChanged = true;
			}
		}
		return false;
	}

	/**
	 * Raises postdata changed event.
	 * This method is required by {@see \Prado\Web\UI\IPostBackDataHandler} interface.
	 * It is invoked by the framework when {@see getSelectedIndex SelectedIndex} property
	 * is changed on postback.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		if ($this->getAutoPostBack() && $this->getCausesValidation()) {
			$this->getPage()->validate($this->getValidationGroup());
		}
		$this->onSelectedIndexChanged(null);
	}

	/**
	 * Returns a value indicating whether postback has caused the control data change.
	 * This method is required by the \Prado\Web\UI\IPostBackDataHandler interface.
	 * @return bool whether postback has caused the control data change. False if the page is not in postback mode.
	 */
	public function getDataChanged()
	{
		return $this->_dataChanged;
	}

	/**
	 * @param mixed $indices
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedIndices($indices)
	{
		throw new TNotSupportedException('dropdownlist_selectedindices_unsupported');
	}

	/**
	 * Returns the value to be validated.
	 * This methid is required by \Prado\Web\UI\IValidatable interface.
	 * @return string the value of the property to be validated.
	 */
	public function getValidationPropertyValue()
	{
		return $this->getSelectedValue();
	}

	/**
	 * Returns true if this control validated successfully.
	 * Defaults to true.
	 * @return bool wether this control validated successfully.
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}
	/**
	 * @param bool $value wether this control is valid.
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}
}
