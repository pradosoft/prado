<?php

/**
 * TWebControlAttribute class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TWebControlAttribute defines the bitmask constants for HTML attributes rendered by
 * web controls and selectable when copying attributes via {@see TWebControl::copyBaseAttributes}.
 *
 * Constants combine with bitwise OR to select attribute groups:
 * ```php
 * $target->copyBaseAttributes($source, TWebControlAttribute::Role | TWebControlAttribute::ARIA);
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TWebControlAttribute extends \Prado\TEnumerable
{
	/** All attribute groups. */
	public const All = -1;

	// PRADO Implementation

	/** The `id` HTML attribute. */
	public const Id = (1 << 0);

	/** All `aria-*` attributes, managed via {@see TWebControl::getAria}. */
	public const ARIA = (1 << 1);

	/** All `data-*` attributes, managed via {@see TWebControl::getDataset}. */
	public const Dataset = (1 << 2);

	/** Custom attributes stored via {@see TWebControl::getAttributes}. */
	public const CustomAttributes = (1 << 3);

	// HTML 5 Attributes

	/** The `accesskey` HTML attribute; a keyboard shortcut to activate or focus the element. */
	public const AccessKey = (1 << 4);

	/** The `role` HTML attribute; the ARIA role for assistive technologies. */
	public const Role = (1 << 5);

	/** The `disabled` HTML attribute; prevents user interaction with the element. */
	public const Disabled = (1 << 6);

	/** The `tabindex` HTML attribute; the element's position in the tab order. */
	public const TabIndex = (1 << 7);

	/** The `title` HTML attribute; advisory text shown as a tooltip. */
	public const Title = (1 << 8);

	/** The `translate` HTML attribute; whether the browser translates the element's text. */
	public const Translate = (1 << 9);

	/** The `lang` HTML attribute; the natural language of the element's content. */
	public const Lang = (1 << 10);

	/** The `dir` HTML attribute; text direction ('ltr', 'rtl', or 'auto'). */
	public const Dir = (1 << 11);

	/** The `hidden` HTML attribute; removes the element from rendering while keeping it in the DOM. */
	public const Hidden = (1 << 12);

	/** The `spellcheck` HTML attribute; enables or disables spelling and grammar checking. */
	public const SpellCheck = (1 << 13);

	/** The `draggable` HTML attribute; whether the element is draggable. */
	public const Draggable = (1 << 14);

	/** The `contenteditable` HTML attribute; whether the element's content is user-editable. */
	public const ContentEditable = (1 << 15);

	/** The `inputmode` HTML attribute; the virtual keyboard type hint for input elements. */
	public const InputMode = (1 << 16);

	/** The `enterkeyhint` HTML attribute; the action label for the Enter key on virtual keyboards. */
	public const EnterKeyHint = (1 << 17);

	/** The `inert` HTML attribute; makes the element and its descendants non-interactive. */
	public const Inert = (1 << 18);

	/** The `popover` HTML attribute; enrolls the element in the Popover API. */
	public const Popover = (1 << 19);

}
