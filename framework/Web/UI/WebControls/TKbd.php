<?php

/**
 * TKbd class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TKbd class
 * TKbd represents the HTML `<kbd>` element, user input: a key, a key
 * combination, or a spoken command. A key combination nests one TKbd per key
 * inside an outer TKbd.
 * ```html
 * <com:TKbd><com:TKbd>Ctrl</com:TKbd>+<com:TKbd>C</com:TKbd></com:TKbd>
 * ```
 * Accessibility: `<kbd>` has no ARIA role. Screen readers read the key names as
 * text. They read a symbol such as ⌘ or ⇧ inconsistently; the key's name, such
 * as `Command`, is read the same in each.
 * The default tag is `kbd`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TKbd extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'kbd';
	}
}
