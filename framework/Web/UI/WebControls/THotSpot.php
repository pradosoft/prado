<?php
/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Collections\TAttributeCollection;
use Prado\TPropertyValue;

/**
 * THotSpot class.
 *
 * THotSpot implements the basic functionality common to all hot spot shapes.
 * Derived classes include {@link TCircleHotSpot}, {@link TPolygonHotSpot}
 * and {@link TRectangleHotSpot}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
abstract class THotSpot extends \Prado\TComponent
{
	private $_viewState = [];

	/**
	 * Returns a viewstate value.
	 *
	 * This function is very useful in defining getter functions for component properties
	 * that must be kept in viewstate.
	 * @param string $key the name of the viewstate value to be returned
	 * @param mixed $defaultValue the default value. If $key is not found in viewstate, $defaultValue will be returned
	 * @return mixed the viewstate value corresponding to $key
	 */
	protected function getViewState($key, $defaultValue = null)
	{
		return isset($this->_viewState[$key]) ? $this->_viewState[$key] : $defaultValue;
	}

	/**
	 * Sets a viewstate value.
	 *
	 * This function is very useful in defining setter functions for control properties
	 * that must be kept in viewstate.
	 * Make sure that the viewstate value must be serializable and unserializable.
	 * @param string $key the name of the viewstate value
	 * @param mixed $value the viewstate value to be set
	 * @param null|mixed $defaultValue default value. If $value===$defaultValue, the item will be cleared from the viewstate.
	 */
	protected function setViewState($key, $value, $defaultValue = null)
	{
		if ($value === $defaultValue) {
			unset($this->_viewState[$key]);
		} else {
			$this->_viewState[$key] = $value;
		}
	}

	/**
	 * @return string shape of the hotspot, can be 'circle', 'rect', 'poly', etc.
	 */
	abstract public function getShape();
	/**
	 * @return string coordinates defining the hotspot shape.
	 */
	abstract public function getCoordinates();

	/**
	 * @return string the access key that allows you to quickly navigate to the HotSpot region. Defaults to ''.
	 */
	public function getAccessKey()
	{
		return $this->getViewState('AccessKey', '');
	}

	/**
	 * @param string $value the access key that allows you to quickly navigate to the HotSpot region.
	 */
	public function setAccessKey($value)
	{
		$this->setViewState('AccessKey', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the alternate text to display for a HotSpot object. Defaults to ''.
	 */
	public function getAlternateText()
	{
		return $this->getViewState('AlternateText', '');
	}

	/**
	 * @param string $value the alternate text to display for a HotSpot object.
	 */
	public function setAlternateText($value)
	{
		$this->setViewState('AlternateText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return THotSpotMode the behavior of a HotSpot object when it is clicked. Defaults to THotSpotMode::NotSet.
	 */
	public function getHotSpotMode()
	{
		return $this->getViewState('HotSpotMode', THotSpotMode::NotSet);
	}

	/**
	 * @param THotSpotMode $value the behavior of a HotSpot object when it is clicked.
	 */
	public function setHotSpotMode($value)
	{
		$this->setViewState('HotSpotMode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\THotSpotMode'), THotSpotMode::NotSet);
	}

	/**
	 * @return string the URL to navigate to when a HotSpot object is clicked. Defaults to ''.
	 */
	public function getNavigateUrl()
	{
		return $this->getViewState('NavigateUrl', '');
	}

	/**
	 * @param string $value the URL to navigate to when a HotSpot object is clicked.
	 */
	public function setNavigateUrl($value)
	{
		$this->setViewState('NavigateUrl', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string a value that is post back when the HotSpot is clicked. Defaults to ''.
	 */
	public function getPostBackValue()
	{
		return $this->getViewState('PostBackValue', '');
	}

	/**
	 * @param string $value a value that is post back when the HotSpot is clicked.
	 */
	public function setPostBackValue($value)
	{
		$this->setViewState('PostBackValue', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return int the tab index of the HotSpot region. Defaults to 0.
	 */
	public function getTabIndex()
	{
		return $this->getViewState('TabIndex', 0);
	}

	/**
	 * @param int $value the tab index of the HotSpot region.
	 */
	public function setTabIndex($value)
	{
		$this->setViewState('TabIndex', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return bool whether postback event trigger by this hotspot will cause input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation', true);
	}

	/**
	 * @param bool $value whether postback event trigger by this hotspot will cause input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the group of validators which the hotspot causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup', '');
	}

	/**
	 * @param string $value the group of validators which the hotspot causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup', $value, '');
	}

	/**
	 * @return string  the target window or frame to display the new page when the HotSpot region
	 * is clicked. Defaults to ''.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target', '');
	}

	/**
	 * @param string $value the target window or frame to display the new page when the HotSpot region
	 * is clicked.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the hotspot has custom attributes
	 */
	public function getHasAttributes()
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->getCount() > 0;
		} else {
			return false;
		}
	}

	/**
	 * Returns the list of custom attributes.
	 * Custom attributes are name-value pairs that may be rendered
	 * as HTML tags' attributes.
	 * @return TAttributeCollection the list of custom attributes
	 */
	public function getAttributes()
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes;
		} else {
			$attributes = new TAttributeCollection;
			$this->setViewState('Attributes', $attributes, null);
			return $attributes;
		}
	}

	/**
	 * @param mixed $name
	 * @return bool whether the named attribute exists
	 */
	public function hasAttribute($name)
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->contains($name);
		} else {
			return false;
		}
	}

	/**
	 * @param mixed $name
	 * @return string attribute value, null if attribute does not exist
	 */
	public function getAttribute($name)
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->itemAt($name);
		} else {
			return null;
		}
	}

	/**
	 * Sets a custom hotspot attribute.
	 * @param string $name attribute name
	 * @param string $value value of the attribute
	 */
	public function setAttribute($name, $value)
	{
		$this->getAttributes()->add($name, $value);
	}

	/**
	 * Removes the named attribute.
	 * @param string $name the name of the attribute to be removed.
	 * @return string attribute value removed, null if attribute does not exist.
	 */
	public function removeAttribute($name)
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->remove($name);
		} else {
			return null;
		}
	}

	/**
	 * Renders this hotspot.
	 * @param THtmlWriter $writer
	 */
	public function render($writer)
	{
		$writer->addAttribute('shape', $this->getShape());
		$writer->addAttribute('coords', $this->getCoordinates());
		if (($mode = $this->getHotSpotMode()) === THotSpotMode::NotSet) {
			$mode = THotSpotMode::Navigate;
		}
		if ($mode === THotSpotMode::Navigate) {
			$writer->addAttribute('href', $this->getNavigateUrl());
			if (($target = $this->getTarget()) !== '') {
				$writer->addAttribute('target', $target);
			}
		}
		$text = $this->getAlternateText();
		$writer->addAttribute('title', $text);
		$writer->addAttribute('alt', $text);
		if (($accessKey = $this->getAccessKey()) !== '') {
			$writer->addAttribute('accesskey', $accessKey);
		}
		if (($tabIndex = $this->getTabIndex()) !== 0) {
			$writer->addAttribute('tabindex', "$tabIndex");
		}
		if ($this->getHasAttributes()) {
			foreach ($this->getAttributes() as $name => $value) {
				$writer->addAttribute($name, $value);
			}
		}
		$writer->renderBeginTag('area');
		$writer->renderEndTag();
	}
}
