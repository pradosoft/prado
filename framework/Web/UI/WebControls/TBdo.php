<?php

/**
 * TBdo class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TBdo class
 * TBdo represents the HTML `<bdo>` element, text displayed in the direction given
 * by {@see setDir() Dir}, overriding the direction of its characters. Text of
 * unknown direction is a {@see TBdi}.
 * `<bdo>` requires `dir`, so Dir is `ltr` or `rtl` and defaults to `ltr`; an empty
 * Dir restores the default.
 * Accessibility: `<bdo>` has no ARIA role. Screen readers read the text in its
 * logical order, so a TBdo changes only how it looks.
 * The default tag is `bdo`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBdo extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'bdo';
	}

	/**
	 * @return string the direction of the text, 'ltr' or 'rtl'. Defaults to 'ltr'.
	 */
	public function getDir()
	{
		return $this->getViewState('Dir', 'ltr');
	}

	/**
	 * @param string $value the direction of the text, 'ltr' or 'rtl'; '' restores 'ltr'.
	 * @throws TInvalidDataValueException if the value is not 'ltr', 'rtl', or ''.
	 */
	public function setDir($value)
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if ($value === '') {
			$value = 'ltr';
		} elseif ($value !== 'ltr' && $value !== 'rtl') {
			throw new TInvalidDataValueException('bdo_dir_invalid', $this::class, $value);
		}
		$this->setViewState('Dir', $value, 'ltr');
	}
}
