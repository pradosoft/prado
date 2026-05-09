<?php

/**
 * TStyle class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TStyle class
 *
 * TStyle encapsulates the CSS style applied to a web control. It maintains five
 * distinct stores:
 * - `_fields` — named CSS property → value pairs set explicitly via setters or
 *   {@see setStyleField}.
 * - `_font` — a lazy-created {@see TFont} object for font-related CSS.
 * - `_class` — a CSS class name (`null` = never set; `''` = explicitly cleared).
 * - `_customStyle` — a raw semicolon-separated CSS string, set via
 *   {@see setCustomStyle}.
 * - `_displayStyle` — a logical {@see TDisplayStyle} constant that writes its
 *   CSS effect directly into `_fields`.
 *
 * ## Render order
 *
 * {@see addAttributesToRender} emits in this order:
 * 1. Custom style string — parsed property by property; rendered first (lowest
 *    priority).
 * 2. Named `_fields` — added after custom style, so they win on conflicts.
 * 3. Font CSS from {@see TFont::addAttributesToRender}.
 * 4. CSS class as `class=` attribute.
 *
 * ## copyFrom vs mergeWith
 *
 * - {@see copyFrom}: **source wins**. Each source field overwrites the same-named
 *   target field; source `_class` and `_customStyle` replace target values when
 *   non-null in source.
 * - {@see mergeWith}: **target wins**. Source fields fill in only what the target
 *   lacks; target keeps its own `_class` and `_customStyle` when already set.
 *
 * ## DisplayStyle
 *
 * {@see setDisplayStyle} writes CSS directly into `_fields` but does **not**
 * clean up fields set by a previous display state. Transition through
 * {@see TDisplayStyle::Dynamic} to clear both `display` and `visibility` fields
 * before switching to a new visible state. {@see reset} restores all state to
 * the constructor default, including `_displayStyle` → {@see DEFAULT_DISPLAY_STYLE}.
 *
 * ## ArrayAccess
 *
 * CSS properties may be accessed via array syntax (maps to
 * {@see getStyleField}/{@see setStyleField}/{@see clearStyleField}):
 * ```php
 * $style['margin'] = '10px';
 * echo $style['color'];        // '' if not set
 * unset($style['border']);
 * isset($style['padding']);     // true only if explicitly set
 * ```
 *
 * ## Magic method/property access
 *
 * Any CSS property expressed in PascalCase or camelCase maps to its kebab-case
 * CSS counterpart. Underscores also map to dashes. Explicit getter/setter methods
 * take precedence over the magic fallback.
 * ```php
 * $style->FontSize = '14px';       // → setStyleField('font-size', '14px')
 * echo $style->BackgroundColor;    // → getStyleField('background-color')
 * $style->setLineHeight('1.5');    // → setStyleField('line-height', '1.5')
 * echo $style->getLetterSpacing(); // → getStyleField('letter-spacing')
 * $style->Padding_Top = '8px';     // → setStyleField('padding-top', '8px')
 * $style->__WebColor= 'blue';     // → setStyleField('--web-color', 'blue')
 * ```
 *
 * ## Template usage
 *
 * Style properties on {@see TWebControl} subclasses can be set in templates.
 * The `Style` attribute accepts a raw CSS string; named convenience properties
 * (delegated from TWebControl to TStyle) can be set individually:
 * ```xml
 * <!-- Named convenience attributes -->
 * <com:TPanel BackColor="#f5f5f5" Width="300px" CssClass="sidebar"
 *             BorderStyle="solid" BorderWidth="1px" BorderRadius="4px" />
 *
 * <!-- Raw CSS string via the Style attribute -->
 * <com:TLabel Text="Hello" Style="color:red;font-weight:bold" />
 *
 * <!-- Sub-tag form -->
 * <com:TPanel CssClass="card">
 *     <prop:Style>padding:16px;margin-bottom:8px</prop:Style>
 * </com:TPanel>
 * ```
 *
 * ## Style sub-property access (`Style.AttributeName`)
 *
 * Any CSS property that lacks a named convenience method can be set directly
 * from a template using dot-notation on the `Style` sub-property.  The
 * template engine converts hyphens to underscores before resolving the name,
 * which maps cleanly onto {@see methodToAttributeName}'s underscore→dash rule:
 *
 * ```xml
 * <!-- PascalCase → kebab-case via magic access -->
 * <com:TPanel Style.FontSize="14px" Style.LineHeight="1.5" />
 *
 * <!-- Hyphenated CSS name (hyphen becomes underscore in the attribute;
 *      methodToAttributeName restores the dash) -->
 * <com:TPanel Style.font-size="14px" Style.border-radius="4px" />
 *
 * <!-- Single leading dash — vendor prefix shorthand -->
 * <com:TPanel Style.-webkit-transform="translateX(10px)" />
 *
 * <!-- Double leading dash — CSS custom property -->
 * <com:TPanel Style.--brand-color="#005fcc" Style.--safari-transform="none" />
 * ```
 *
 * The same forms work from PHP via {@see \Prado\TComponent::setSubProperty}
 * (which is exactly what the template engine calls internally).  The template
 * engine first runs `str_replace('-', '_', $attr)` on the attribute name, so
 * the PHP-equivalent calls use the already-converted names:
 * ```php
 * $panel->setSubProperty('Style.FontSize',           '14px');
 * $panel->setSubProperty('Style.font_size',          '14px');   // font-size → font_size
 * $panel->setSubProperty('Style._webkit_transform',  'translateX(10px)'); // -webkit-transform → _webkit_transform
 * $panel->setSubProperty('Style.__brand_color',      '#005fcc'); // --brand-color → __brand_color
 * $panel->setSubProperty('Style.__safari_transform', 'none');    // --safari-transform → __safari_transform
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> direct field array access, dash support
 * @since 3.0
 * @see TFont
 * @see TDisplayStyle
 * @see TWebControl
 */
class TStyle extends \Prado\TComponent implements \ArrayAccess
{
	/**
	 * Default display style used by new instances and {@see reset()}.
	 * Subclasses may override this constant to change the out-of-the-box default;
	 * the override takes effect because {@see __construct} and {@see reset} use
	 * `static::DEFAULT_DISPLAY_STYLE` for late-static binding.
	 * @since 4.3.3
	 */
	public const DEFAULT_DISPLAY_STYLE = TDisplayStyle::Fixed;
	/**
	 * @var array<string,string> explicitly set CSS property name-value pairs
	 */
	protected $_fields = [];
	/**
	 * @var null|TFont font object, null until first accessed
	 */
	protected $_font;
	/**
	 * @var null|string CSS class name; null means never set, '' means explicitly cleared
	 */
	protected $_class;
	/**
	 * @var null|string raw semicolon-separated CSS string; null means never set
	 */
	protected $_customStyle;
	/**
	 * @var null|string logical display state constant from {@see TDisplayStyle};
	 *                  null only between `unserialize()` and `__wakeup()` when the
	 *                  property was omitted from serialized data
	 */
	protected $_displayStyle;

	/**
	 * Constructor.
	 * @param null|TStyle $style optional style to copy from via {@see copyFrom}
	 */
	public function __construct($style = null)
	{
		parent::__construct();
		$this->_displayStyle = static::DEFAULT_DISPLAY_STYLE;
		if ($style !== null) {
			$this->copyFrom($style);
		}
	}

	/**
	 * Excludes default/empty fields from serialization to keep view state lean.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if (!$this->getHasStyleFields()) {
			$exprops[] = "\0*\0_fields";
		}
		if (!$this->getHasFont()) {
			$exprops[] = "\0*\0_font";
		}
		if (!$this->getHasCssClass()) {
			$exprops[] = "\0*\0_class";
		}
		if (!$this->getHasCustomStyle()) {
			$exprops[] = "\0*\0_customStyle";
		}
		if ($this->getDisplayStyle() === static::DEFAULT_DISPLAY_STYLE) {
			$exprops[] = "\0*\0_displayStyle";
		}
	}

	/**
	 * After deserialization, re-initialises `_displayStyle` to
	 * `static::DEFAULT_DISPLAY_STYLE` when it was omitted from the serialized
	 * data by {@see _getZappableSleepProps}.  Using `static::` means each
	 * subclass restores its own default, not TStyle's `TDisplayStyle::Fixed`.
	 * @since 4.3.3
	 */
	public function __wakeup(): void
	{
		parent::__wakeup();
		if ($this->_displayStyle === null) {
			$this->_displayStyle = static::DEFAULT_DISPLAY_STYLE;
		}
	}

	/**
	 * Deep-clones the font object so clone and original share no state.
	 */
	public function __clone()
	{
		if ($this->getHasFont()) {
			$this->setFont(clone($this->getFont()));
		}
		parent::__clone();
	}

	/**
	 * @return string CSS `width` value, or empty string if not set
	 */
	public function getWidth()
	{
		return $this->getStyleField('width');
	}

	/**
	 * @param string $value CSS width (e.g. `'200px'`, `'50%'`, `'auto'`); empty clears the property
	 */
	public function setWidth($value)
	{
		$this->setStyleField('width', $value);
	}

	/**
	 * @return string CSS `height` value, or empty string if not set
	 */
	public function getHeight()
	{
		return $this->getStyleField('height');
	}

	/**
	 * @param string $value CSS height (e.g. `'100px'`, `'auto'`); empty clears the property
	 */
	public function setHeight($value)
	{
		$this->setStyleField('height', $value);
	}

	/**
	 * @return string CSS `color` (foreground/text) value, or empty string if not set
	 */
	public function getForeColor()
	{
		return $this->getStyleField('color');
	}

	/**
	 * @param string $value CSS color value (e.g. `'#333'`, `'red'`, `'rgb(0,0,255)'`); empty clears the property
	 */
	public function setForeColor($value)
	{
		$this->setStyleField('color', $value);
	}

	/**
	 * @return string CSS `background-color` value, or empty string if not set
	 */
	public function getBackColor()
	{
		return $this->getStyleField('background-color');
	}

	/**
	 * @param string $value CSS color value (e.g. `'#FF0000'`, `'blue'`); empty clears the property
	 */
	public function setBackColor($value)
	{
		$this->setStyleField('background-color', $value);
	}

	/**
	 * @return string CSS `border-color` value, or empty string if not set
	 */
	public function getBorderColor()
	{
		return $this->getStyleField('border-color');
	}

	/**
	 * @param string $value CSS color value; empty clears the property
	 */
	public function setBorderColor($value)
	{
		$this->setStyleField('border-color', $value);
	}

	/**
	 * @return string CSS `border-style` value, or empty string if not set
	 */
	public function getBorderStyle()
	{
		return $this->getStyleField('border-style');
	}

	/**
	 * @param string $value CSS border-style (e.g. `'solid'`, `'dashed'`, `'none'`); empty clears the property
	 */
	public function setBorderStyle($value)
	{
		$this->setStyleField('border-style', $value);
	}

	/**
	 * @return string CSS `border-width` value, or empty string if not set
	 */
	public function getBorderWidth()
	{
		return $this->getStyleField('border-width');
	}

	/**
	 * @param string $value CSS border-width (e.g. `'1px'`, `'thin'`, `'medium'`); empty clears the property
	 */
	public function setBorderWidth($value)
	{
		$this->setStyleField('border-width', $value);
	}

	/**
	 * @return string CSS `border-radius` value, or empty string if not set
	 * @since 4.2.0
	 */
	public function getBorderRadius()
	{
		return $this->getStyleField('border-radius');
	}

	/**
	 * @param string $value CSS border-radius (e.g. `'5px'`, `'50%'`); empty clears the property
	 * @since 4.2.0
	 */
	public function setBorderRadius($value)
	{
		$this->setStyleField('border-radius', $value);
	}

	/**
	 * @return bool true if the CSS class has been explicitly set (even if set to empty string)
	 * @since 4.3.3
	 */
	public function getHasCssClass(): bool
	{
		return ($this->_class !== null);
	}

	/**
	 * @return bool true if the CSS class has been explicitly set (even if set to empty string)
	 * @deprecated 4.3.3, to be removed in 4.4
	 */
	public function hasCssClass()
	{
		return $this->getHasCssClass();
	}

	/**
	 * @return string CSS class name, or empty string if not set
	 */
	public function getCssClass()
	{
		return $this->_class ?? '';
	}

	/**
	 * @param string $value CSS class name; leading/trailing whitespace is trimmed
	 */
	public function setCssClass($value)
	{
		$value = TPropertyValue::ensureString($value);
		$this->_class = trim($value);
	}

	/**
	 * Returns the font object, creating it on first call.
	 * @return TFont the font object
	 */
	public function getFont()
	{
		if ($this->_font === null) {
			$this->setFont($this->newFont());
		}
		return $this->_font;
	}

	/**
	 * Sets the font object.
	 * @param TFont $font The font object.
	 * @since 4.3.3
	 */
	protected function setFont($font): void
	{
		$this->_font = $font;
	}

	/**
	 * Creates and returns a new {@see TFont} instance.
	 * Subclasses may override to return a custom TFont subclass.
	 * @return TFont
	 * @since 4.3.3
	 */
	protected function newFont(): TFont
	{
		return new TFont();
	}

	/**
	 * @return bool true if a TFont instance has been set or created via {@see getFont}
	 * @since 4.3.3
	 */
	public function getHasFont(): bool
	{
		return $this->_font !== null;
	}

	/**
	 * @return bool true if a TFont instance has been set or created via {@see getFont}
	 * @deprecated 4.3.3, to be removed in 4.4
	 */
	public function hasFont()
	{
		return $this->getHasFont();
	}

	/**
	 * @return string current {@see TDisplayStyle} constant value
	 */
	public function getDisplayStyle()
	{
		return $this->_displayStyle;
	}

	/**
	 * Sets the logical display state and writes the corresponding CSS into `_fields`.
	 *
	 * - `TDisplayStyle::Fixed` — sets `visibility:visible`
	 * - `TDisplayStyle::None` — sets `display:none`
	 * - `TDisplayStyle::Dynamic` — clears both `display` and `visibility` fields
	 * - `TDisplayStyle::Hidden` — sets `visibility:hidden`
	 *
	 * Note: switching from `None` to `Fixed` leaves `display:none` in `_fields`
	 * because only `visibility:visible` is written. Pass `Dynamic` first to clear
	 * both fields before transitioning to a visible state.
	 *
	 * @param string $value {@see TDisplayStyle} constant
	 */
	public function setDisplayStyle($value)
	{
		$displayStyle = TPropertyValue::ensureEnum($value, TDisplayStyle::class);
		$this->_displayStyle = $displayStyle;
		switch ($displayStyle) {
			case TDisplayStyle::None:
				$this->setStyleField('display', 'none');
				break;
			case TDisplayStyle::Dynamic:
				$this->clearStyleField('display');
				$this->clearStyleField('visibility');
				break;
			case TDisplayStyle::Fixed:
				$this->setStyleField('visibility', 'visible');
				break;
			case TDisplayStyle::Hidden:
				$this->setStyleField('visibility', 'hidden');
				break;
		}
	}

	/**
	 * @return string the custom style string, or empty string if not set
	 */
	public function getCustomStyle()
	{
		return $this->_customStyle ?? '';
	}

	/**
	 * Sets a raw CSS string that is parsed and emitted before named `_fields`.
	 * Named fields added via {@see setStyleField} override any same-named property
	 * in the custom style string at render time.
	 * @param string $value semicolon-separated CSS string (e.g. `'color:red;padding:5px'`); trimmed
	 */
	public function setCustomStyle($value)
	{
		$value = TPropertyValue::ensureString($value);
		$this->_customStyle = trim($value);
	}

	/**
	 * @return bool true if a custom style string has been set (i.e. {@see setCustomStyle} has been called)
	 * @since 4.3.3
	 */
	public function getHasCustomStyle()
	{
		return $this->_customStyle !== null;
	}

	/**
	 * @param string $name CSS property name
	 * @return bool true if the property has been explicitly set via {@see setStyleField}
	 */
	public function hasStyleField($name)
	{
		return isset($this->_fields[trim((string) $name)]);
	}

	/**
	 * @param string $name CSS property name
	 * @return string the property value, or empty string if not set
	 */
	public function getStyleField($name)
	{
		return $this->_fields[trim((string) $name)] ?? '';
	}

	/**
	 * Sets a named CSS property. Both name and value are trimmed.
	 * Passing an empty value removes the property (equivalent to {@see clearStyleField}).
	 * @param string $name CSS property name
	 * @param string $value CSS property value; empty string removes the property
	 */
	public function setStyleField($name, $value)
	{
		$name = TPropertyValue::ensureString($name);
		$value = TPropertyValue::ensureString($value);
		$name = trim($name);
		$value = trim($value);
		if ($value === '') {
			$this->clearStyleField($name);
		} else {
			$this->_fields[$name] = $value;
		}
	}

	/**
	 * Removes a named CSS property from the explicit field map.
	 * No-op if the property is not set.
	 * @param string $name CSS property name; trimmed before lookup
	 */
	public function clearStyleField($name)
	{
		unset($this->_fields[trim((string) $name)]);
	}

	/**
	 * @return bool true if at least one CSS property has been explicitly set via
	 *              {@see setStyleField} or a named setter
	 * @since 4.3.3
	 */
	public function getHasStyleFields(): bool
	{
		return $this->_fields !== [];
	}

	/**
	 * @return array<string,string> all explicitly set CSS property name-value pairs
	 */
	public function getStyleFields()
	{
		return $this->_fields;
	}

	/**
	 * Resets all style data to the constructor default.
	 * All fields, font, CSS class, and custom style are cleared.
	 * `_displayStyle` is restored to {@see DEFAULT_DISPLAY_STYLE}.
	 */
	public function reset()
	{
		$this->_fields = [];
		$this->_font = null;
		$this->_class = null;
		$this->_customStyle = null;
		$this->_displayStyle = static::DEFAULT_DISPLAY_STYLE;
	}

	/**
	 * Copies style from a source, with **source winning** on any conflict.
	 *
	 * - CSS fields: source values overwrite target values for duplicate keys.
	 * - CSS class: replaced by source value when source class has been set.
	 * - Custom style: replaced by source value when source custom style has been set.
	 * - Font: if source has a font, it is merged into this font via
	 *   {@see TFont::copyFrom} (source font properties overwrite target font properties).
	 * - Display style: **not copied** — only the CSS fields it wrote are.
	 *
	 * @param TStyle $style source style; non-TStyle values are silently ignored
	 */
	public function copyFrom($style)
	{
		if ($style instanceof TStyle) {
			if ($style->getHasStyleFields()) {
				$this->_fields = array_merge($this->getStyleFields(), $style->getStyleFields());
			}
			if ($style->getHasCssClass()) {
				$this->setCssClass($style->getCssClass());
			}
			if ($style->getHasCustomStyle()) {
				$this->setCustomStyle($style->getCustomStyle());
			}
			if ($style->getHasFont()) {
				$this->getFont()->copyFrom($style->getFont());
			}
		}
	}

	/**
	 * Merges a base style into this one, with **this style winning** on any conflict.
	 *
	 * - CSS fields: this style's values overwrite base values for duplicate keys.
	 * - CSS class: taken from base only when this style's class has not been set.
	 * - Custom style: taken from base only when this style's custom style has not been set.
	 * - Font: if base has a font, it is merged into this font via
	 *   {@see TFont::mergeWith} (this font properties take precedence).
	 * - Display style: **not copied** — only the CSS fields it wrote are.
	 *
	 * @param TStyle $style base style to merge from; non-TStyle values are silently ignored
	 */
	public function mergeWith($style)
	{
		if ($style instanceof TStyle) {
			if ($style->getHasStyleFields()) {
				$this->_fields = array_merge($style->getStyleFields(), $this->getStyleFields());
			}
			if (!$this->getHasCssClass() && $style->getHasCssClass()) {
				$this->setCssClass($style->getCssClass());
			}
			if (!$this->getHasCustomStyle() && $style->getHasCustomStyle()) {
				$this->setCustomStyle($style->getCustomStyle());
			}
			if ($style->getHasFont()) {
				$this->getFont()->mergeWith($style->getFont());
			}
		}
	}

	/**
	 * Writes all style data to an HTML writer.
	 *
	 * Render order: custom style string → named fields → font attributes → CSS class.
	 * Named fields override any same-named property in the custom style string.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for rendering
	 */
	public function addAttributesToRender($writer)
	{
		$customStyle = $this->getCustomStyle();
		if (!empty($customStyle)) {
			foreach (explode(';', $customStyle) as $style) {
				$arr = explode(':', $style, 2);
				if (isset($arr[1]) && trim($arr[0]) !== '') {
					$writer->addStyleAttribute(trim($arr[0]), trim($arr[1]));
				}
			}
		}
		if ($this->getHasStyleFields()) {
			$writer->addStyleAttributes($this->getStyleFields());
		}
		if ($this->getHasFont()) {
			$this->getFont()->addAttributesToRender($writer);
		}
		if ($this->getHasCssClass()) {
			$writer->addAttribute('class', $this->getCssClass());
		}
	}

	// ArrayAccess implementation

	/**
	 * @param string $offset CSS property name
	 * @return bool true if the property is explicitly set
	 */
	public function offsetExists($offset): bool
	{
		return $this->hasStyleField($offset);
	}

	/**
	 * @param string $offset CSS property name
	 * @return string the property value, or empty string if not set
	 */
	public function offsetGet($offset): mixed
	{
		return $this->getStyleField($offset);
	}

	/**
	 * @param string $offset CSS property name
	 * @param string $item CSS property value; empty string removes the property
	 */
	public function offsetSet($offset, $item): void
	{
		$this->setStyleField($offset, $item);
	}

	/**
	 * @param string $offset CSS property name to remove
	 */
	public function offsetUnset($offset): void
	{
		$this->clearStyleField($offset);
	}

	// Magic method/property access

	/**
	 * Maps `getXxx()` → {@see getStyleField}(`kebab-xxx`) and
	 * `setXxx($v)` → {@see setStyleField}(`kebab-xxx`, `$v`).
	 *
	 * The method name suffix is converted to a CSS property name via
	 * {@see methodToAttributeName}. Leading underscores in the suffix map to
	 * leading dashes in the CSS name, so `get_WebkitTransform()` reads the
	 * `-webkit-transform` field and `get__WebColor()` reads `--web-color`.
	 * `set` calls require exactly one argument; any other prefix or wrong arity
	 * falls through to the parent (which throws).
	 *
	 * @param string $method method name
	 * @param array $args arguments
	 * @return mixed style field value for getters; void for setters
	 */
	public function __call($method, $args)
	{
		$getset = substr($method, 0, 3);
		if ($getset == 'get') {
			$propname = $this->methodToAttributeName(substr($method, 3));
			return $this->getStyleField($propname);
		} elseif ($getset == 'set' && count($args) === 1) {
			$propname = $this->methodToAttributeName(substr($method, 3));
			return $this->setStyleField($propname, $args[0]);
		}
		parent::__call($method, $args);
	}

	/**
	 * Property read access. Checks for a real `getXxx()` method first; falls back
	 * to reading the CSS field named by {@see methodToAttributeName}(`$name`).
	 * @param string $name property name (PascalCase or camelCase)
	 * @return mixed
	 */
	public function __get($name)
	{
		if (Prado::method_visible($this, $getter = 'get' . $name)) {
			return $this->$getter();
		}
		$name = $this->methodToAttributeName($name);
		return $this->getStyleField($name);
	}

	/**
	 * Property write access. Checks for a real `setXxx()` method first; falls back
	 * to writing the CSS field named by {@see methodToAttributeName}(`$name`).
	 * @param string $name property name (PascalCase or camelCase)
	 * @param mixed $value value to set
	 */
	public function __set($name, $value)
	{
		if (Prado::method_visible($this, $setter = 'set' . $name)) {
			return $this->$setter($value);
		}
		$name = $this->methodToAttributeName($name);
		return $this->setStyleField($name, $value);
	}

	/**
	 * Converts a PascalCase or camelCase name to a kebab-case CSS property name.
	 *
	 * Rules applied in order:
	 * 1. Underscores → dashes
	 * 2. Each uppercase letter not at position 0 and not already preceded by a dash is prefixed with a dash
	 * 3. Result is lowercased
	 *
	 * Examples: `BackgroundColor` → `background-color`,
	 * `FontSize` → `font-size`, `Padding_Top` → `padding-top`.
	 *
	 * @param string $name PascalCase or camelCase identifier
	 * @return string kebab-case CSS property name
	 */
	protected function methodToAttributeName($name)
	{
		// Replace underscores with dashes
		$name = str_replace('_', '-', $name);

		// Insert dash before uppercase letters, except at start or after an existing dash
		$name = preg_replace('/(?<!^)(?<!-)[A-Z]/', '-$0', $name);

		// Lowercase everything
		return strtolower($name);
	}

	/**
	 * Always returns true: every CSS property is readable via magic access.
	 * @param string $name property name
	 * @return bool
	 */
	public function canGetProperty($name)
	{
		return true;
	}

	/**
	 * Always returns true: every CSS property is writable via magic access.
	 * @param string $name property name
	 * @return bool
	 */
	public function canSetProperty($name)
	{
		return true;
	}

	/**
	 * Returns true for any method whose name begins with `get` or `set`, in
	 * addition to methods defined on this class and its parents.
	 * @param string $name method name
	 * @return bool
	 */
	public function hasMethod($name)
	{
		if (parent::hasMethod($name)) {
			return true;
		}
		if ((strncasecmp($name, 'get', 3) === 0) || (strncasecmp($name, 'set', 3) === 0)) {
			return true;
		}
		return false;
	}
}
