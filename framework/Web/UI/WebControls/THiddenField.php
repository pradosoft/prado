<?php
/**
 * THiddenField class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\TPropertyValue;

/**
 * THiddenField class
 *
 * THiddenField displays a hidden input field on a Web page.
 * The value of the input field can be accessed via {@see getValue Value} property.
 * If upon postback the value is changed, a {@see onValueChanged OnValueChanged}
 * event will be raised.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THiddenField extends \Prado\Web\UI\TControl implements \Prado\Web\UI\IPostBackDataHandler, \Prado\Web\UI\IValidatable, \Prado\IDataRenderer
{
	private $_dataChanged = false;
	private $_isValid = true;

	/**
	 * @return string tag name of the hidden field.
	 */
	protected function getTagName()
	{
		return 'input';
	}

	/**
	 * Sets focus to this control.
	 * This method overrides the parent implementation by forbidding setting focus to this control.
	 */
	public function focus()
	{
		throw new TNotSupportedException('hiddenfield_focus_unsupported');
	}

	/**
	 * Renders the control.
	 * This method overrides the parent implementation by rendering
	 * the hidden field input element.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		$uniqueID = $this->getUniqueID();
		$this->getPage()->ensureRenderInForm($this);
		$writer->addAttribute('type', 'hidden');
		if ($uniqueID !== '') {
			$writer->addAttribute('name', $uniqueID);
		}
		if ($this->getID() !== '') {
			$writer->addAttribute('id', $this->getClientID());
		}
		if (($value = $this->getValue()) !== '') {
			$writer->addAttribute('value', $value);
		}

		if ($this->getHasAttributes()) {
			foreach ($this->getAttributes() as $name => $value) {
				$writer->addAttribute($name, $value);
			}
		}

		$writer->renderBeginTag('input');
		$writer->renderEndTag();
	}

	/**
	 * Loads hidden field data.
	 * This method is primarly used by framework developers.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the data of the component has been changed
	 */
	public function loadPostData($key, $values)
	{
		$value = $values[$key];
		if ($value === $this->getValue()) {
			return false;
		} else {
			$this->setValue($value);
			return $this->_dataChanged = true;
		}
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
	 * Returns the value to be validated.
	 * This methid is required by \Prado\Web\UI\IValidatable interface.
	 * @return string the value of the property to be validated.
	 */
	public function getValidationPropertyValue()
	{
		return $this->getValue();
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

	/**
	 * Raises postdata changed event.
	 * This method calls {@see onValueChanged} method.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		$this->onValueChanged(null);
	}

	/**
	 * This method is invoked when the value of the {@see getValue Value} property changes between posts to the server.
	 * The method raises 'OnValueChanged' event to fire up the event delegates.
	 * If you override this method, be sure to call the parent implementation
	 * so that the attached event handlers can be invoked.
	 * @param \Prado\TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onValueChanged($param)
	{
		$this->raiseEvent('OnValueChanged', $this, $param);
	}

	/**
	 * @return string the value of the THiddenField
	 */
	public function getValue()
	{
		return $this->getViewState('Value', '');
	}

	/**
	 * Sets the value of the THiddenField
	 * @param string $value the value to be set
	 */
	public function setValue($value)
	{
		$this->setViewState('Value', $value, '');
	}

	/**
	 * Returns the value of the hidden field.
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see getValue()}.
	 * @return string value of the hidden field
	 * @see getValue
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->getValue();
	}

	/**
	 * Sets the value of the hidden field.
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see setValue()}.
	 * @param string $value value of the hidden field
	 * @see setValue
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setValue($value);
	}


	/**
	 * @return bool whether theming is enabled for this control. Defaults to false.
	 */
	public function getEnableTheming()
	{
		return false;
	}

	/**
	 * @param bool $value whether theming is enabled for this control.
	 * @throws TNotSupportedException This method is always thrown when calling this method.
	 */
	public function setEnableTheming($value)
	{
		throw new TNotSupportedException('hiddenfield_theming_unsupported');
	}

	/**
	 * @param string $value Skin ID
	 * @throws TNotSupportedException This method is always thrown when calling this method.
	 */
	public function setSkinID($value)
	{
		throw new TNotSupportedException('hiddenfield_skinid_unsupported');
	}
}
