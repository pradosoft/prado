<?php

/**
 * TWebControl class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Collections\TAttributeCollection;
use Prado\Collections\TWebAttributeCollection;

/**
 * TWebControl class
 *
 * TWebControl is the base class for controls that share a common set of UI-related
 * properties and methods. TWebControl-derived controls are usually associated with
 * HTML tags and thus have tag name, attributes, and body contents.
 *
 * ## Core Properties
 *
 * ### Tag and Rendering
 *
 * - {@see getTagName} returns the HTML tag name (default 'span'). Override in subclasses.
 * - {@see render} outputs the complete tag: opening tag, contents, closing tag
 * - {@see renderBeginTag} renders the opening tag with attributes
 * - {@see renderContents} renders child controls and text
 * - {@see renderEndTag} renders the closing tag
 *
 * ### CSS Style Properties
 *
 * Style properties are stored in a TStyle object, lazy-created on first access:
 * ```php
 * $control->setBackColor('#FF0000');
 * $control->setWidth('200px');
 * $control->setCssClass('my-style');
 * ```
 *
 * ### HTML Attributes
 *
 * Standard and custom HTML attributes:
 * ```php
 * $control->setAttribute('data-custom', 'value');
 * $control->setAttribute('role', 'button');
 * ```
 *
 * ### HTML 5 Global Attributes
 *
 * Additional HTML 5 global attributes:
 * ```php
 * $control->setLang('en-US');
 * $control->setDir('ltr');
 * $control->setHidden(true);
 * $control->setSpellCheck(true);
 * $control->setDraggable(true);
 * $control->setInert(false);
 * $control->setContentEditable(true);
 * $control->setInputMode(TWebInputMode::Email);
 * $control->setEnterKeyHint(TEnterKeyHint::Next);
 * $control->setTranslate('yes');
 * $control->setPopover(true);
 * ```
 *
 * ### ARIA Attributes
 *
 * WAI-ARIA support for accessibility:
 * ```php
 * $control->setRole('button');
 * $control->getAria()->add('label', 'Submit button');
 * $control->getAria()['describedby'] = 'help-text';
 * ```
 *
 * ### Data Attributes
 *
 * HTML5 data-* attributes:
 * ```php
 * $control->getDataset()->add('itemid', '123');
 * $control->getDataset()['value'] = 'myvalue';
 * $control->dataset['max'] = '1000';
 * ```
 *
 * - {@see getHasDataset} checks if any data attributes exist
 * - {@see getDataset} returns TAttributeCollection for data-* attributes
 *
 * ## Subclassing
 *
 * Override these methods in subclasses:
 * ```php
 * class TMyControl extends TWebControl
 * {
 *     protected function getTagName(): string
 *     {
 *         return 'article';
 *     }
 *
 *     protected function addAttributesToRender($writer)
 *     {
 *         parent::addAttributesToRender($writer);
 *         $writer->addAttribute('custom-attr', 'value');
 *     }
 *
 *     public function renderContents($writer)
 *     {
 *         $writer->write('Content here');
 *     }
 * }
 * ```
 *
 * ## Decorators
 *
 * TWebControlDecorator adds pre/post content around the control tag, used by Prado Skins:
 * ```php
 * $control->getDecorator()->setPreTagText('<div class="wrapper">');
 * $control->getDecorator()->setPostTagText('</div>');
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> TWebControlDecorator, HTML 5 Global Attributes, Dataset, ARIA (Accessibility)
 * @since 3.0
 * @see https://www.w3schools.com/tags HTML Element Reference
 * @see https://w3c.github.io/aria/ Accessible Rich Internet Applications (WAI-ARIA)
 * @see https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Roles#aria_role_types ARIA Roles
 */
class TWebControl extends \Prado\Web\UI\TControl implements IStyleable
{
	/**
	 * @var bool ensures the inclusion of id in tag rendering once enabled
	 */
	private $_ensureid = false;

	/**
	 * @var ?TWebControlDecorator decorator for pre/post tag content
	 */
	protected $_decorator;

	/**
	 * Enforces ID rendering on this control.
	 *
	 * Once set to true, it stays true for the lifetime of the control.
	 *
	 * @param bool $value true to enforce ID rendering
	 */
	public function setEnsureId($value)
	{
		$this->_ensureid |= TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether this control must have an ID rendered
	 */
	public function getEnsureId()
	{
		return (bool) $this->_ensureid;
	}

	/**
	 * Gets the decorator for this control, creating it if needed.
	 *
	 * @param bool $create if true, creates decorator if it doesn't exist
	 * @return ?TWebControlDecorator the decorator object or null if not created
	 */
	public function getDecorator($create = true)
	{
		if ($create && !$this->_decorator) {
			$this->_decorator = new TWebControlDecorator($this);
		}
		return $this->_decorator;
	}

	/**
	 * Copies base control attributes from another control.
	 *
	 * The bitmask $attrToCopy selects which attribute groups to copy; defaults to all.
	 *
	 * @param TWebControl $control source control to copy from
	 * @param int $attrToCopy bitmask of {@see TWebControlAttribute} constants
	 */
	public function copyBaseAttributes(TWebControl $control, int $attrToCopy = TWebControlAttribute::All)
	{
		if ($attrToCopy & TWebControlAttribute::AccessKey) {
			$this->setAccessKey($control->getAccessKey());
		}
		if ($attrToCopy & TWebControlAttribute::Role) {
			$this->setRole($control->getRole());
		}
		if ($attrToCopy & TWebControlAttribute::Disabled) {
			$this->setEnabled($this->getEnabled() && $control->getEnabled());
		}
		if ($attrToCopy & TWebControlAttribute::TabIndex) {
			$this->setTabIndex($control->getTabIndex());
		}
		if ($attrToCopy & TWebControlAttribute::Title) {
			$this->setToolTip($control->getToolTip());
		}
		if ($attrToCopy & TWebControlAttribute::Translate) {
			$this->setTranslate($control->getTranslate());
		}
		if ($attrToCopy & TWebControlAttribute::Lang) {
			$this->setLang($control->getLang());
		}
		if ($attrToCopy & TWebControlAttribute::Dir) {
			$this->setDir($control->getDir());
		}
		if ($attrToCopy & TWebControlAttribute::Hidden) {
			$this->setHidden($control->getHidden());
		}
		if ($attrToCopy & TWebControlAttribute::SpellCheck) {
			$this->setSpellCheck($control->getSpellCheck());
		}
		if ($attrToCopy & TWebControlAttribute::Draggable) {
			$this->setDraggable($control->getDraggable());
		}
		if ($attrToCopy & TWebControlAttribute::ContentEditable) {
			$this->setContentEditable($control->getContentEditable());
		}
		if ($attrToCopy & TWebControlAttribute::InputMode) {
			$this->setInputMode($control->getInputMode());
		}
		if ($attrToCopy & TWebControlAttribute::EnterKeyHint) {
			$this->setEnterKeyHint($control->getEnterKeyHint());
		}
		if ($attrToCopy & TWebControlAttribute::Inert) {
			$this->setInert($control->getInert());
		}
		if ($attrToCopy & TWebControlAttribute::Popover) {
			$this->setPopover($control->getPopover());
		}
		if ($attrToCopy & TWebControlAttribute::ARIA && $control->getHasAria()) {
			$this->getAria()->copyFrom($control->getAria());
		}
		if ($attrToCopy & TWebControlAttribute::Dataset && $control->getHasDataset()) {
			$this->getDataset()->copyFrom($control->getDataset());
		}
		if ($attrToCopy & TWebControlAttribute::CustomAttributes && $control->getHasAttributes()) {
			$this->getAttributes()->copyFrom($control->getAttributes());
		}
	}

	/**
	 * Returns the HTML tag name for this control; default is 'span'.
	 *
	 * @return string tag name rendered for this control
	 */
	protected function getTagName()
	{
		return 'span';
	}

	/**
	 * @return string the access key for keyboard navigation
	 */
	public function getAccessKey()
	{
		return $this->getViewState('AccessKey', '');
	}

	/**
	 * Sets the access key.
	 *
	 * Only one-character strings can be set. Use empty string to disable.
	 *
	 * @param string $value the access key (single character)
	 * @throws TInvalidDataValueException if value is longer than one character
	 */
	public function setAccessKey($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (strlen($value) > 1) {
			throw new TInvalidDataValueException('webcontrol_accesskey_invalid', $this::class, $value);
		}
		$this->setViewState('AccessKey', $value, '');
	}

	/**
	 * @return string CSS color (foreground/text) value, or empty string if not set
	 */
	public function getForeColor()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getForeColor();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS foreground color property.
	 *
	 * @param string $value CSS color value (e.g., '#333333', 'red')
	 */
	public function setForeColor($value)
	{
		$this->getStyle()->setForeColor($value);
	}

	/**
	 * @return string CSS background-color value, or empty string if not set
	 */
	public function getBackColor()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getBackColor();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS background-color property.
	 *
	 * @param string $value CSS color value (e.g., '#FF0000', 'red')
	 */
	public function setBackColor($value)
	{
		$this->getStyle()->setBackColor($value);
	}

	/**
	 * @return string CSS border-color value, or empty string if not set
	 */
	public function getBorderColor()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getBorderColor();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS border-color property.
	 *
	 * @param string $value CSS color value
	 */
	public function setBorderColor($value)
	{
		$this->getStyle()->setBorderColor($value);
	}

	/**
	 * @return string CSS border-style value, or empty string if not set
	 */
	public function getBorderStyle()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getBorderStyle();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS border-style property.
	 *
	 * @param string $value border style (e.g., 'solid', 'dashed', 'dotted', 'none')
	 */
	public function setBorderStyle($value)
	{
		$this->getStyle()->setBorderStyle($value);
	}

	/**
	 * @return string CSS border-width value, or empty string if not set
	 */
	public function getBorderWidth()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getBorderWidth();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS border-width property.
	 *
	 * @param string $value border width (e.g., '1px', '2px', 'thin')
	 */
	public function setBorderWidth($value)
	{
		$this->getStyle()->setBorderWidth($value);
	}

	/**
	 * @return TFont the font object for this control
	 */
	public function getFont()
	{
		return $this->getStyle()->getFont();
	}

	/**
	 * @return string display style (TDisplayStyle constant)
	 */
	public function getDisplay()
	{
		return $this->getStyle()->getDisplayStyle();
	}

	/**
	 * Sets the display style.
	 *
	 * - TDisplayStyle::Fixed — visible (default)
	 * - TDisplayStyle::None — hidden (display:none)
	 * - TDisplayStyle::Dynamic — CSS-controlled
	 * - TDisplayStyle::Hidden — invisible but occupies space (visibility:hidden)
	 *
	 * @param string $value TDisplayStyle constant
	 */
	public function setDisplay($value)
	{
		$this->getStyle()->setDisplayStyle($value);
	}

	/**
	 * @return string CSS class name, or empty string if not set
	 */
	public function getCssClass()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getCssClass();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS class name.
	 *
	 * @param string $value CSS class name
	 */
	public function setCssClass($value)
	{
		$this->getStyle()->setCssClass($value);
	}

	/**
	 * @return string language code following BCP 47, or empty string if not set
	 * @since 4.4.0
	 */
	public function getLang()
	{
		return $this->getViewState('Lang', '');
	}

	/**
	 * Sets the language of the element's content.
	 *
	 * Values follow BCP 47 (e.g., 'en', 'en-US', 'zh-Hans').
	 *
	 * @param string $value language code
	 * @since 4.4.0
	 */
	public function setLang($value)
	{
		$this->setViewState('Lang', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string text direction ('ltr', 'rtl', 'auto'), or empty string if not set
	 * @since 4.4.0
	 */
	public function getDir()
	{
		return $this->getViewState('Dir', '');
	}

	/**
	 * Sets the text directionality of the element.
	 *
	 * @param string $value 'ltr' (left-to-right), 'rtl' (right-to-left), or 'auto'
	 * @throws TInvalidDataValueException if value is invalid
	 * @since 4.4.0
	 */
	public function setDir($value)
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if ($value === '' || in_array($value, ['ltr', 'rtl', 'auto'], true)) {
			$this->setViewState('Dir', $value, '');
		} else {
			throw new TInvalidDataValueException('webcontrol_dir_invalid', $this::class, $value);
		}
	}

	/**
	 * @return bool whether the control is hidden
	 * @since 4.4.0
	 */
	public function getHidden()
	{
		return $this->getViewState('Hidden', false);
	}

	/**
	 * Sets whether the control is hidden.
	 *
	 * @param bool $value true to hide the element
	 * @since 4.4.0
	 */
	public function setHidden($value)
	{
		$this->setViewState('Hidden', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return ?bool whether spellcheck is enabled, null for browser default
	 * @since 4.4.0
	 */
	public function getSpellCheck()
	{
		return $this->getViewState('SpellCheck', null);
	}

	/**
	 * Sets whether spellcheck is enabled.
	 *
	 * @param null|bool|string $value true, false, or null to reset
	 * @since 4.4.0
	 */
	public function setSpellCheck($value)
	{
		$this->setViewState('SpellCheck', ($value === null || $value === '') ? null : TPropertyValue::ensureBoolean($value), null);
	}

	/**
	 * Removes all style data from the control.
	 */
	public function clearStyle()
	{
		$this->clearViewState('Style');
	}

	/**
	 * @return ?int tab index, null means not in tab order
	 */
	public function getTabIndex()
	{
		return $this->getViewState('TabIndex', null);
	}

	/**
	 * Sets the tab index for keyboard navigation.
	 *
	 * - 0: natural tab order
	 * - -1: not in tab order but can be focused programmatically
	 * - null: not rendered as tabable
	 *
	 * @param null|int|string $value tab index
	 */
	public function setTabIndex($value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('TabIndex', null, null);
		} else {
			$this->setViewState('TabIndex', TPropertyValue::ensureInteger($value), null);
		}
	}

	/**
	 * @return string tooltip text, or empty string if not set
	 */
	public function getToolTip()
	{
		return $this->getViewState('ToolTip', '');
	}

	/**
	 * Sets the tooltip text shown on hover.
	 *
	 * @param string $value tooltip text, empty to disable
	 */
	public function setToolTip($value)
	{
		$this->setViewState('ToolTip', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string CSS width value, or empty string if not set
	 */
	public function getWidth()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getWidth();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS width property.
	 *
	 * @param string $value CSS width (e.g., '200px', '50%', 'auto')
	 */
	public function setWidth($value)
	{
		$this->getStyle()->setWidth($value);
	}

	/**
	 * @return string CSS height value, or empty string if not set
	 */
	public function getHeight()
	{
		if ($style = $this->getViewState('Style', null)) {
			return $style->getHeight();
		} else {
			return '';
		}
	}

	/**
	 * Sets the CSS height property.
	 *
	 * @param string $value CSS height (e.g., '100px', '50%', 'auto')
	 */
	public function setHeight($value)
	{
		$this->getStyle()->setHeight($value);
	}

	/**
	 * @return null|bool|string draggable state, null for browser default
	 * @since 4.4.0
	 */
	public function getDraggable()
	{
		return $this->getViewState('Draggable', null);
	}

	/**
	 * Sets whether the element is draggable.
	 *
	 * @param null|bool|string $value true, false, or null for default
	 * @since 4.4.0
	 */
	public function setDraggable($value)
	{
		if ($value === null || $value === '') {
			$value = null;
		} else {
			$value = TPropertyValue::ensureBoolean($value);
		}
		$this->setViewState('Draggable', $value, null);
	}

	/**
	 * @return bool whether the element is inert (prevents interaction)
	 * @since 4.4.0
	 */
	public function getInert()
	{
		return $this->getViewState('Inert', false);
	}

	/**
	 * Sets whether the element is inert (prevents user interaction).
	 *
	 * @param bool $value true to make inert
	 * @since 4.4.0
	 */
	public function setInert($value)
	{
		$this->setViewState('Inert', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return null|bool|string contenteditable state, null for browser default
	 * @since 4.4.0
	 */
	public function getContentEditable()
	{
		return $this->getViewState('ContentEditable', null);
	}

	/**
	 * Sets whether the element's content is editable.
	 *
	 * @param null|bool|string $value true, false, 'plaintext-only', or null for default
	 * @since 4.4.0
	 */
	public function setContentEditable($value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('ContentEditable', null, null);
		} elseif (is_string($value) && strtolower(trim($value)) === 'plaintext-only') {
			$this->setViewState('ContentEditable', 'plaintext-only', null);
		} else {
			$this->setViewState('ContentEditable', TPropertyValue::ensureBoolean($value), null);
		}
	}

	/**
	 * @return ?string input mode hint, null if not set
	 * @since 4.4.0
	 */
	public function getInputMode()
	{
		return $this->getViewState('InputMode', null);
	}

	/**
	 * Sets the input mode hint for virtual keyboards.
	 *
	 * @param ?string $value TWebInputMode constant (e.g., TWebInputMode::Email, TWebInputMode::Url)
	 * @since 4.4.0
	 */
	public function setInputMode($value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('InputMode', null, null);
		} else {
			$this->setViewState('InputMode', TPropertyValue::ensureEnum($value, TWebInputMode::class), null);
		}
	}

	/**
	 * @return ?string enter key hint, null if not set
	 * @since 4.4.0
	 */
	public function getEnterKeyHint()
	{
		return $this->getViewState('EnterKeyHint', null);
	}

	/**
	 * Sets the enter key hint for virtual keyboards.
	 *
	 * @param ?string $value TEnterKeyHint constant (e.g., TEnterKeyHint::Next, TEnterKeyHint::Done)
	 * @since 4.4.0
	 */
	public function setEnterKeyHint($value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('EnterKeyHint', null, null);
		} else {
			$this->setViewState('EnterKeyHint', TPropertyValue::ensureEnum($value, TEnterKeyHint::class), null);
		}
	}

	/**
	 * @return ?string translation hint ('yes', 'no'), null if not set
	 * @since 4.4.0
	 */
	public function getTranslate()
	{
		return $this->getViewState('Translate', null);
	}

	/**
	 * Sets whether the content should be translated.
	 *
	 * @param null|bool|string $value 'yes', 'no', or boolean
	 * @since 4.4.0
	 */
	public function setTranslate($value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('Translate', null, null);
			return;
		}
		if (is_string($value)) {
			$lowerString = strtolower(trim($value));
			if ($lowerString === 'yes') {
				$this->setViewState('Translate', 'yes', null);
				return;
			}
			if ($lowerString === 'no') {
				$this->setViewState('Translate', 'no', null);
				return;
			}
		}
		$this->setViewState('Translate', TPropertyValue::ensureBoolean($value) ? 'yes' : 'no', null);
	}

	/**
	 * @return bool whether popover behavior is enabled
	 * @since 4.4.0
	 */
	public function getPopover()
	{
		return $this->getViewState('Popover', false);
	}

	/**
	 * Sets whether popover behavior is enabled.
	 *
	 * @param bool $value true to enable popover
	 * @since 4.4.0
	 */
	public function setPopover($value)
	{
		$this->setViewState('Popover', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return ?string ARIA role, null if not set
	 * @since 4.4.0
	 */
	public function getRole()
	{
		return $this->getViewState('Role', null);
	}

	/**
	 * Sets the ARIA role for accessibility.
	 *
	 * @param ?string $value ARIA role (e.g., 'button', 'menu', 'tooltip')
	 * @since 4.4.0
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Roles
	 */
	public function setRole($value)
	{
		if (!empty($value)) {
			$value = trim(TPropertyValue::ensureString($value));
		} else {
			$value = null;
		}
		$this->setViewState('Role', $value, null);
	}

	/**
	 * Creates the ARIA attribute collection for this control.
	 *
	 * Override in subclasses to provide a custom TWebAttributeCollection subclass.
	 *
	 * @return TWebAttributeCollection the ARIA attribute collection
	 * @since 4.4.0
	 */
	protected function createARIA()
	{
		return new TWebAttributeCollection('aria');
	}

	/**
	 * @return bool whether any ARIA attributes have been set
	 * @since 4.4.0
	 */
	public function getHasAria()
	{
		$aria = $this->getViewState('Aria', null);
		return $aria && $aria->getCount() > 0;
	}

	/**
	 * Returns the ARIA attribute collection, creating it if absent.
	 *
	 * Common keys: label, describedby, labelledby, hidden.
	 *
	 * @return TWebAttributeCollection collection of aria-* attributes
	 * @since 4.4.0
	 */
	public function getAria()
	{
		$aria = $this->getViewState('Aria', null);
		if (!$aria) {
			$aria = $this->createARIA();
			$this->setViewState('Aria', $aria, null);
		}
		return $aria;
	}

	/**
	 * Creates the dataset attribute collection for this control.
	 *
	 * Override in subclasses to provide a custom TWebAttributeCollection subclass.
	 *
	 * @return TWebAttributeCollection the data-* attribute collection
	 * @since 4.4.0
	 */
	protected function createDataset()
	{
		return new TWebAttributeCollection('data');
	}

	/**
	 * @return bool whether any data-* attributes have been set
	 * @since 4.4.0
	 */
	public function getHasDataset()
	{
		$dataset = $this->getViewState('Dataset', null);
		return $dataset && $dataset->getCount() > 0;
	}

	/**
	 * Returns the data-* attribute collection, creating it if absent.
	 *
	 * @return TWebAttributeCollection collection of data-* attributes
	 * @since 4.4.0
	 */
	public function getDataset()
	{
		$dataset = $this->getViewState('Dataset', null);
		if (!$dataset) {
			$dataset = $this->createDataset();
			$this->setViewState('Dataset', $dataset, null);
		}
		return $dataset;
	}

	/**
	 * Sets a custom attribute, routing `data-*` to the dataset and `aria-*` to ARIA.
	 *
	 * @param string $name attribute name
	 * @param string $value attribute value
	 * @since 4.4.0
	 */
	public function setAttribute($name, $value)
	{
		if (strncasecmp($name, 'data-', 5) === 0) {
			$this->getDataset()->add($name, $value);
		} elseif (strncasecmp($name, 'aria-', 5) === 0) {
			$this->getAria()->add($name, $value);
		} else {
			parent::setAttribute($name, $value);
		}
	}

	/**
	 * Returns a custom attribute value, checking dataset and ARIA collections for `data-*` and `aria-*` names.
	 *
	 * @param string $name attribute name
	 * @return ?string attribute value, null if not found
	 * @since 4.4.0
	 */
	public function getAttribute($name)
	{
		if (strncasecmp($name, 'data-', 5) === 0) {
			return $this->getDataset()->itemAt($name);
		} elseif (strncasecmp($name, 'aria-', 5) === 0) {
			return $this->getAria()->itemAt($name);
		}
		return parent::getAttribute($name);
	}

	/**
	 * Removes a custom attribute, routing `data-*` to the dataset and `aria-*` to ARIA.
	 *
	 * @param string $name attribute name
	 * @return ?string removed value, null if not found
	 * @since 4.4.0
	 */
	public function removeAttribute($name)
	{
		if (strncasecmp($name, 'data-', 5) === 0) {
			return $this->getDataset()->remove($name);
		} elseif (strncasecmp($name, 'aria-', 5) === 0) {
			return $this->getAria()->remove($name);
		}
		return parent::removeAttribute($name);
	}

	/**
	 * Creates the default TStyle object for this control.
	 *
	 * Override in subclasses to provide custom TStyle subclasses.
	 *
	 * @return TStyle the style object
	 */
	protected function createStyle()
	{
		return new TStyle();
	}

	/**
	 * @return bool whether any style properties have been set
	 */
	public function getHasStyle()
	{
		return $this->getViewState('Style', null) !== null;
	}

	/**
	 * Gets the style object for this control, creating it if necessary.
	 *
	 * Style properties (BackColor, Width, CssClass, etc.) are delegated to this object.
	 *
	 * @return TStyle the style object
	 */
	public function getStyle()
	{
		$style = $this->getViewState('Style', null);
		if (!$style) {
			$style = $this->createStyle();
			$this->setViewState('Style', $style, null);
		}
		return $style;
	}

	/**
	 * Sets the CSS style string directly.
	 *
	 * The style string is prefixed to styles set via individual properties.
	 *
	 * @param string $value CSS style string (e.g., 'color:red;margin:10px')
	 * @throws TInvalidDataValueException if value is not a string
	 */
	public function setStyle($value)
	{
		if (is_string($value)) {
			$this->getStyle()->setCustomStyle($value);
		} else {
			throw new TInvalidDataValueException('webcontrol_style_invalid', $this::class);
		}
	}

	/**
	 * Instantiates any attached decorator before the render phase.
	 *
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onPreRender($param)
	{
		if ($decorator = $this->getDecorator(false)) {
			$decorator->instantiate();
		}
		parent::onPreRender($param);
	}

	/**
	 * Adds attribute name-value pairs to the writer.
	 *
	 * Renders in order: id, accesskey, role, disabled, tabindex, title, translate, lang,
	 * dir, hidden, spellcheck, draggable, contenteditable, inputmode, enterkeyhint, inert,
	 * popover, aria-*, data-*, style, custom attributes.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the HTML writer
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getID() !== '' || $this->getEnsureId()) {
			$writer->addAttribute('id', $this->getClientID());
		}
		if (($accessKey = $this->getAccessKey()) !== '') {
			$writer->addAttribute('accesskey', $accessKey);
		}
		if (($role = $this->getRole()) !== null) {
			$writer->addAttribute('role', $role);
		}
		if (!$this->getEnabled()) {
			$writer->addAttribute('disabled', 'disabled');
		}
		if (($tabIndex = $this->getTabIndex()) !== null) {
			$writer->addAttribute('tabindex', (string) $tabIndex);
		}
		if (($toolTip = $this->getToolTip()) !== '') {
			$writer->addAttribute('title', $toolTip);
		}
		if (($translate = $this->getTranslate()) !== null) {
			$writer->addAttribute('translate', $translate);
		}
		if (($lang = $this->getLang()) !== '') {
			$writer->addAttribute('lang', $lang);
		}
		if (($dir = $this->getDir()) !== '') {
			$writer->addAttribute('dir', $dir);
		}
		if ($this->getHidden()) {
			$writer->addAttribute('hidden', 'hidden');
		}
		if (($spellCheck = $this->getSpellCheck()) !== null) {
			$writer->addAttribute('spellcheck', $spellCheck ? 'true' : 'false');
		}
		if (($draggable = $this->getDraggable()) !== null) {
			$writer->addAttribute('draggable', $draggable ? 'true' : 'false');
		}
		if (($ce = $this->getContentEditable()) !== null) {
			$writer->addAttribute('contenteditable', ($ce === 'plaintext-only') ? $ce : ($ce ? 'true' : 'false'));
		}
		if (($inputMode = $this->getInputMode()) !== null) {
			$writer->addAttribute('inputmode', strtolower($inputMode));
		}
		if (($keyHint = $this->getEnterKeyHint()) !== null) {
			$writer->addAttribute('enterkeyhint', strtolower($keyHint));
		}
		if ($this->getInert()) {
			$writer->addAttribute('inert', 'inert');
		}
		if ($this->getPopover()) {
			$writer->addAttribute('popover', 'popover');
		}
		if ($aria = $this->getViewState('Aria', null)) {
			$aria->addAttributesToRender($writer);
		}
		if ($dataset = $this->getViewState('Dataset', null)) {
			$dataset->addAttributesToRender($writer);
		}
		if ($style = $this->getViewState('Style', null)) {
			$style->addAttributesToRender($writer);
		}
		if ($this->getHasAttributes()) {
			foreach ($this->getAttributes() as $name => $value) {
				$writer->addAttribute($name, $value);
			}
		}
	}

	/**
	 * Renders the complete control: opening tag, contents, and closing tag.
	 *
	 * When a {@see TWebControlDecorator} is attached, the full output sequence is:
	 * - PreTagTemplate (inserted as a sibling before this control by {@see TWebControlDecorator::ensureTemplateDecoration})
	 * - PreTagText
	 * - open tag
	 * - PreContentsText
	 * - PreContentsTemplate (inserted as first child by {@see TWebControlDecorator::ensureTemplateDecoration})
	 * - child controls
	 * - PostContentsTemplate (inserted as last child by {@see TWebControlDecorator::ensureTemplateDecoration})
	 * - PostContentsText
	 * - close tag
	 * - PostTagText
	 * - PostTagTemplate (inserted as a sibling after this control by {@see TWebControlDecorator::ensureTemplateDecoration})
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the HTML writer
	 */
	public function render($writer)
	{
		$this->renderBeginTag($writer);
		$this->renderContents($writer);
		$this->renderEndTag($writer);
	}

	/**
	 * Renders the opening tag with all attributes.
	 *
	 * When a {@see TWebControlDecorator} is attached, the output sequence is:
	 * {@see TWebControlDecorator::renderPreTagText} → open tag → {@see TWebControlDecorator::renderPreContentsText}.
	 * Without a decorator, only the open tag is written.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the HTML writer
	 */
	public function renderBeginTag($writer)
	{
		if ($decorator = $this->getDecorator(false)) {
			$decorator->renderPreTagText($writer);
			$this->addAttributesToRender($writer);
			$writer->renderBeginTag($this->getTagName());
			$decorator->renderPreContentsText($writer);
		} else {
			$this->addAttributesToRender($writer);
			$writer->renderBeginTag($this->getTagName());
		}
	}

	/**
	 * Renders the body content enclosed within the HTML tag.
	 *
	 * By default renders child controls and text strings.
	 * Override in subclasses to customize content.
	 * When a {@see TWebControlDecorator} uses templates, PreContentsTemplate and
	 * PostContentsTemplate controls are already present as the first and last children,
	 * so this method renders them as part of the normal child traversal.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the HTML writer
	 */
	public function renderContents($writer)
	{
		parent::renderChildren($writer);
	}

	/**
	 * Renders the closing tag.
	 *
	 * When a {@see TWebControlDecorator} is attached, the output sequence is:
	 * {@see TWebControlDecorator::renderPostContentsText} → close tag → {@see TWebControlDecorator::renderPostTagText}.
	 * Without a decorator, only the close tag is written.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the HTML writer
	 */
	public function renderEndTag($writer)
	{
		if ($decorator = $this->getDecorator(false)) {
			$decorator->renderPostContentsText($writer);
			$writer->renderEndTag();
			$decorator->renderPostTagText($writer);
		} else {
			$writer->renderEndTag();
		}
	}
}
