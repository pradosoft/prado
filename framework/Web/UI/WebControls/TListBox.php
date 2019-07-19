<?php
/**
 * TListBox class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TListBox class
 *
 * TListBox displays a list box on a Web page that allows single or multiple selection.
 * The list box allows multiple selections if {@link setSelectionMode SelectionMode}
 * is TListSelectionMode::Multiple. It takes single selection only if Single.
 * The property {@link setRows Rows} specifies how many rows of options are visible
 * at a time. See {@link TListControl} for inherited properties.
 *
 * Since v3.0.3, TListBox starts to support optgroup. To specify an option group for
 * a list item, set a Group attribute with it,
 * <code>
 *  $listitem->Attributes->Group="Group Name";
 *  // or <com:TListItem Attributes.Group="Group Name" .../> in template
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TListBox extends TListControl implements \Prado\Web\UI\IPostBackDataHandler, \Prado\Web\UI\IValidatable
{
	private $_dataChanged = false;
	private $_isValid = true;

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This method overrides the parent implementation with additional list box specific attributes.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$rows = $this->getRows();
		$writer->addAttribute('size', "$rows");
		if ($this->getSelectionMode() === TListSelectionMode::Multiple) {
			$writer->addAttribute('name', $this->getUniqueID() . '[]');
		} else {
			$writer->addAttribute('name', $this->getUniqueID());
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TListBox';
	}

	/**
	 * Registers the list control to load post data on postback.
	 * This method overrides the parent implementation.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getEnabled(true)) {
			$this->getPage()->registerRequiresPostData($this);
		}
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
		$selections = $values[$key] ?? null;
		if ($selections !== null) {
			$items = $this->getItems();
			if ($this->getSelectionMode() === TListSelectionMode::Single) {
				$selection = is_array($selections) ? $selections[0] : $selections;
				$index = $items->findIndexByValue($selection, false);
				if ($this->getSelectedIndex() !== $index) {
					$this->setSelectedIndex($index);
					return $this->_dataChanged = true;
				} else {
					return false;
				}
			}
			if (!is_array($selections)) {
				$selections = [$selections];
			}
			$list = [];
			foreach ($selections as $selection) {
				$list[] = $items->findIndexByValue($selection, false);
			}
			$list2 = $this->getSelectedIndices();
			$n = count($list);
			$flag = false;
			if ($n === count($list2)) {
				sort($list, SORT_NUMERIC);
				for ($i = 0; $i < $n; ++$i) {
					if ($list[$i] !== $list2[$i]) {
						$flag = true;
						break;
					}
				}
			} else {
				$flag = true;
			}
			if ($flag) {
				$this->setSelectedIndices($list);
				$this->_dataChanged = true;
			}
			return $flag;
		} elseif ($this->getSelectedIndex() !== -1) {
			$this->clearSelection();
			return $this->_dataChanged = true;
		} else {
			return false;
		}
	}

	/**
	 * Raises postdata changed event.
	 * This method is required by {@link \Prado\Web\UI\IPostBackDataHandler} interface.
	 * It is invoked by the framework when {@link getSelectedIndices SelectedIndices} property
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
	 * @return bool whether this control allows multiple selection
	 */
	protected function getIsMultiSelect()
	{
		return $this->getSelectionMode() === TListSelectionMode::Multiple;
	}

	/**
	 * @return int the number of rows to be displayed in the list control
	 */
	public function getRows()
	{
		return $this->getViewState('Rows', 4);
	}

	/**
	 * @param int $value the number of rows to be displayed in the list control
	 */
	public function setRows($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value <= 0) {
			$value = 4;
		}
		$this->setViewState('Rows', $value, 4);
	}

	/**
	 * @return TListSelectionMode the selection mode (Single, Multiple). Defaults to TListSelectionMode::Single.
	 */
	public function getSelectionMode()
	{
		return $this->getViewState('SelectionMode', TListSelectionMode::Single);
	}

	/**
	 * @param TListSelectionMode $value the selection mode
	 */
	public function setSelectionMode($value)
	{
		$this->setViewState('SelectionMode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TListSelectionMode'), TListSelectionMode::Single);
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
